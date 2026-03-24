<?php

namespace App\Services;

use App\Mail\NewUserPasswordMail;
use App\Model\Discussion;
use App\Model\Group;
use App\Model\Liste;
use App\Model\Listen_Eintragungen;
use App\Model\listen_termine;
use App\Model\Poll;
use App\Model\Poll_Votes;
use App\Model\Post;
use App\Model\User;
use App\Repositories\GroupsRepository;
use App\Scopes\GetGroupsScope;
use App\Settings\EmailSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UserService
{
    public function __construct(
        private GroupsRepository $groupsRepository,
    ) {}

    /**
     * Benutzer anlegen, sicheres Passwort generieren und Willkommens-E-Mail versenden.
     *
     * @param  array  $data  Validierte Felder: name, email
     * @return array{user: User, password: string, emailSent: bool, emailStatus: string}
     */
    public function createUser(array $data): array
    {
        $password = Str::password(12, true, true, true, false);

        $user = new User($data);
        $user->password = Hash::make($password);
        $user->changePassword = true;
        $user->lastEmail = Carbon::now();
        $user->save();

        $emailSent = false;
        $emailStatus = '';

        try {
            $emailSettings = app(EmailSetting::class);
            Mail::to($user->email)->send(new NewUserPasswordMail($user, $password, $emailSettings->new_user_welcome_text));
            $emailSent = true;
            $emailStatus = 'E-Mail mit Startkennwort wurde erfolgreich versendet.';
        } catch (\Throwable $e) {
            Log::error('Willkommens-E-Mail fehlgeschlagen für '.$user->email.': '.$e->getMessage());
            $emailStatus = 'Warnung: E-Mail konnte nicht versendet werden. Bitte kontaktieren Sie den Administrator.';
        }

        return compact('user', 'password', 'emailSent', 'emailStatus');
    }

    /**
     * Benutzer-Stammdaten aktualisieren.
     * Setzt deactivated_at automatisch bei Deaktivierung/Reaktivierung.
     */
    public function updateUser(User $user, array $data): bool
    {
        // is_active: deactivated_at automatisch pflegen
        if (array_key_exists('is_active', $data)) {
            $isActive = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;
            if (! $isActive && $user->is_active !== false) {
                // Wird gerade deaktiviert
                $data['deactivated_at'] = now();
            } elseif ($isActive) {
                // Wird reaktiviert
                $data['deactivated_at'] = null;
            }
            $data['is_active'] = $isActive;
        }

        return $user->fill($data)->save();
    }

    /**
     * Gruppenzuordnungen synchronisieren.
     * Behandelt Sonderwerte 'all', 'Grundschule', 'Oberschule'.
     * Nutzt KEIN GetGroupsScope – Admin sieht alle Gruppen.
     */
    public function syncGroups(User $user, ?array $groupInput): void
    {
        if (is_null($groupInput)) {
            return;
        }

        $gruppen = $this->groupsRepository->getGroups($groupInput);
        $user->groups()->sync($gruppen);
    }

    /**
     * Rollen zuweisen mit Berechtigungsprüfung.
     * Admin mit 'edit permission' kann alle Rollen zuweisen.
     * Admin mit 'assign roles to users' nur Rollen mit 'role is assignable'.
     */
    public function syncRoles(User $user, ?array $roleNames, User $admin): void
    {
        if (is_null($roleNames)) {
            return;
        }

        if ($admin->can('edit permission')) {
            $roles = Role::whereIn('name', $roleNames)->get()->unique();
            $user->roles()->sync($roles);
        } elseif ($admin->can('assign roles to users')) {
            $assignableRoles = Role::whereIn('name', $roleNames)
                ->whereHas('permissions', fn ($q) => $q->where('name', 'role is assignable'))
                ->get()
                ->unique();

            // Nicht-zuweisbare Rollen des Users erhalten (nicht überschreiben)
            $protectedRoles = $user->roles()
                ->whereDoesntHave('permissions', fn ($q) => $q->where('name', 'role is assignable'))
                ->get();

            $user->roles()->sync($assignableRoles);
            $user->roles()->attach($protectedRoles);
        }
    }

    /**
     * Direktberechtigungen synchronisieren.
     */
    public function syncPermissions(User $user, ?array $permissions): void
    {
        $user->syncPermissions($permissions ?? []);
    }

    /**
     * Passwort setzen (Admin-initiiert).
     */
    public function setPassword(User $user, string $newPassword): void
    {
        $user->password = Hash::make($newPassword);
        $user->save();
    }

    /**
     * Sorg2-Verknüpfung bidirektional setzen.
     * Entkoppelt vorherige Partner beider Seiten.
     */
    public function linkSorgeberechtigte(User $user, int $sorg2Id): void
    {
        DB::transaction(function () use ($user, $sorg2Id) {
            // 1. Alten Partner des Users entkoppeln
            if ($user->sorg2 && $user->sorg2 !== $sorg2Id) {
                User::where('id', $user->sorg2)->update(['sorg2' => null]);
            }

            // 2. Alten Partner des neuen Sorg2 entkoppeln
            $newPartner = User::findOrFail($sorg2Id);
            if ($newPartner->sorg2 && $newPartner->sorg2 !== $user->id) {
                User::where('id', $newPartner->sorg2)->update(['sorg2' => null]);
            }

            // 3. Neue Verknüpfung bidirektional setzen
            $user->update(['sorg2' => $sorg2Id]);
            $newPartner->update(['sorg2' => $user->id]);
        });
    }

    /**
     * Sorg2-Verknüpfung bidirektional auflösen.
     */
    public function unlinkSorgeberechtigte(User $user): void
    {
        if ($user->sorg2) {
            User::where('id', $user->sorg2)->update(['sorg2' => null]);
        }
        $user->update(['sorg2' => null]);
    }

    /**
     * Benutzer und abhängige Daten atomar löschen (in DB-Transaktion).
     *
     * @return string Leerstring bei Erfolg, Fehlermeldung bei Fehler
     */
    public function deleteUser(User $user): string
    {
        try {
            DB::transaction(function () use ($user) {
                $user->groups()->detach();

                if ($user->sorg2 != null) {
                    $sorg2 = User::where('id', '=', $user->sorg2)->first();
                    if (! is_null($sorg2)) {
                        $sorg2->update(['sorg2' => null]);
                    }
                    $user->update(['sorg2' => null]);
                }

                $user->schickzeiten()->where('users_id', $user->id)->forceDelete();

                Listen_Eintragungen::where('created_by', $user->id)->delete();
                Listen_Eintragungen::where('user_id', $user->id)->update(['user_id' => null]);
                Discussion::where('owner', $user->id)->update(['owner' => null]);
                listen_termine::where('reserviert_fuer', $user->id)->delete();
                Poll::where('author_id', $user->id)->update(['author_id' => null]);
                Poll_Votes::where('author_id', $user->id)->delete();

                Liste::query()->where('besitzer', $user->id)->update(['besitzer' => null]);

                $user->listen_termine()->delete();
                $user->userRueckmeldung()->delete();
                $user->reinigung()->delete();
                $user->schickzeiten_own()->delete();
                $user->krankmeldungen()->withTrashed()->forceDelete();
                $user->comments()->delete();

                Post::query()->where('author', $user->id)->update(['author' => null]);

                $user->delete();
            });

            return '';
        } catch (\Exception $e) {
            Log::error('Fehler beim Löschen von User '.$user->id.': '.$e->getMessage());

            return $e->getMessage();
        }
    }

    /**
     * Massenlöschung mit Schutz vor geschützten Rollen.
     *
     * @param  array  $userIds
     * @return array{deleted: int, errors: string}
     */
    public function massDeleteUsers(array $userIds): array
    {
        $protectedRoles = ['Mitarbeiter', 'Vereinsmitglied', 'Administrator'];
        $deleted = 0;
        $errors = '';

        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if (! $user) {
                continue;
            }

            if ($user->roles()->whereIn('name', $protectedRoles)->exists()) {
                $errors .= ($errors ? ', ' : 'Nicht gelöscht: ').$user->name;
                continue;
            }

            $error = $this->deleteUser($user);
            if ($error !== '') {
                $errors .= ($errors ? ', ' : 'Fehler bei: ').$user->name;
            } else {
                $deleted++;
            }
        }

        return compact('deleted', 'errors');
    }
}

