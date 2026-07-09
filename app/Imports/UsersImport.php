<?php

namespace App\Imports;

use App\Mail\NewUserPasswordMail;
use App\Model\Child;
use App\Model\Group;
use App\Model\User;
use App\Scopes\GetGroupsScope;
use App\Settings\EmailSetting;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UsersImport implements ToCollection, WithHeadingRow
{
    protected array $header;

    protected \Illuminate\Database\Eloquent\Collection $groups;

    protected bool $sendEmail;

    /** @var array<int, array{name: string, email: string, password: string}> */
    protected array $newUsers = [];

    public function __construct(array $header, bool $sendEmail = true)
    {
        $this->header = $header;
        $this->sendEmail = $sendEmail;
        $this->groups = Group::withoutGlobalScope(GetGroupsScope::class)->get();
    }

    /** Returns credentials of newly created users (only populated when sendEmail = false). */
    public function getNewUsers(): array
    {
        return $this->newUsers;
    }

    /** Liest eine Zellen-Spalte robust aus: trimmt und liefert null bei fehlendem/leerem Wert. */
    private function cellValue(array $row, string $key): ?string
    {
        if (! isset($this->header[$key])) {
            return null;
        }

        $value = $row[$this->header[$key]] ?? null;
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? '' : $value;
    }

    /** Versendet die Willkommens-E-Mail oder sammelt die Zugangsdaten für den PDF-Export. */
    private function handleNewUserCredentials(User $user, string $password): void
    {
        if ($this->sendEmail) {
            try {
                $emailSettings = app(EmailSetting::class);
                Mail::to($user->email)->queue(
                    new NewUserPasswordMail($user, $password, $emailSettings->new_user_welcome_text)
                );
                Log::info('Willkommens-E-Mail an ' . $user->email . ' versendet');
            } catch (\Exception $mailException) {
                Log::error('Fehler beim Versenden der Willkommens-E-Mail an ' . $user->email . ': ' . $mailException->getMessage());
            }
        } else {
            $this->newUsers[] = ['name' => $user->name, 'email' => $user->email, 'password' => $password];
        }
    }

    /**
     * Legt einen Sorgeberechtigten an bzw. aktualisiert ihn, weist die Eltern-Rolle sowie die
     * ermittelten Gruppen zu und sammelt ggf. die Zugangsdaten. Wird identisch für Sorg1 und
     * Sorg2 verwendet, damit beide gleich behandelt werden.
     */
    private function importGuardian(?string $email, ?string $vorname, ?string $nachname, array $gruppen): ?User
    {
        if ($email === null || $email === '') {
            return null;
        }

        $email = trim(explode(';', $email)[0]);
        if ($email === '') {
            return null;
        }

        $isNewUser = ! User::where('email', $email)->exists();
        $password = Str::password(12, true, true, true, false);

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name'           => trim(($vorname ?? '') . ' ' . ($nachname ?? '')),
                'changePassword' => 1,
                'password'       => Hash::make($password),
                'lastEmail'      => Carbon::now(),
            ]
        );

        $user->touch();
        $user->assignRole('Eltern');
        $user->removeRole('Aufnahme');
        $user->groups()->attach($gruppen);

        if ($isNewUser) {
            $this->handleNewUserCredentials($user, $password);
        }

        return $user;
    }

    public function collection(Collection $collection)
    {
        foreach ($collection as $row) {
            set_time_limit(20);

            $row = array_values($row->toArray());

            // ── Gruppen aus Klassenstufe / Lerngruppe ──────────────────────────
            $klassenstufeValue = $this->cellValue($row, 'klassenstufe');
            $Klassenstufe = ! empty($klassenstufeValue)
                ? $this->groups->firstWhere('name', 'Klassenstufe ' . $klassenstufeValue)
                : null;

            $lerngruppeValue = $this->cellValue($row, 'lerngruppe');
            $Lerngruppe = ! empty($lerngruppeValue)
                ? $this->groups->firstWhere('name', substr($lerngruppeValue, 1))
                : null;

            $gruppen = [];
            if (! is_null($Klassenstufe)) {
                $gruppen[$Klassenstufe->id] = $Klassenstufe->id;
            }
            if (! is_null($Lerngruppe)) {
                $gruppen[$Lerngruppe->id] = $Lerngruppe->id;
            }

            // ── Zusätzliche Gruppen aus Gruppen-Spalte ─────────────────────────
            $gruppenListe = $this->cellValue($row, 'gruppen');
            if (! empty($gruppenListe)) {
                foreach (explode(';', $gruppenListe) as $user_group) {
                    $group = $this->groups->firstWhere('name', trim($user_group));
                    if (! is_null($group)) {
                        $gruppen[$group->id] = $group->id;
                    }
                }
            }

            // ── Sorgeberechtigter 1 ──────────────────────────────────────────────
            $user1 = null;
            try {
                $user1 = $this->importGuardian(
                    $this->cellValue($row, 'S1Email'),
                    $this->cellValue($row, 'S1Vorname'),
                    $this->cellValue($row, 'S1Nachname'),
                    $gruppen
                );
            } catch (\Exception $e) {
                $vorname = $this->cellValue($row, 'S1Vorname') ?: '?';
                $nachname = $this->cellValue($row, 'S1Nachname') ?: '?';
                Log::error("Fehler beim Importieren von Sorgeberechtigtem 1 ({$vorname} {$nachname}): " . $e->getMessage());
            }

            // ── Sorgeberechtigter 2 (optional) ───────────────────────────────────
            // Wird unabhängig von Sorg1 verarbeitet, damit ein Fehler bei Sorg1 die
            // Anlage von Sorg2 nicht verhindert.
            $user2 = null;
            try {
                $user2 = $this->importGuardian(
                    $this->cellValue($row, 'S2Email'),
                    $this->cellValue($row, 'S2Vorname'),
                    $this->cellValue($row, 'S2Nachname'),
                    $gruppen
                );
            } catch (\Exception $e) {
                $vorname = $this->cellValue($row, 'S2Vorname') ?: '?';
                $nachname = $this->cellValue($row, 'S2Nachname') ?: '?';
                Log::error("Fehler beim Importieren von Sorgeberechtigtem 2 ({$vorname} {$nachname}): " . $e->getMessage());
            }

            // ── Sorg1 ↔ Sorg2-Verknüpfung ────────────────────────────────────────
            if ($user1 && $user2 && $user1->id !== $user2->id) {
                try {
                    $user1->sorg2 = $user2->id;
                    $user2->sorg2 = $user1->id;
                    $user1->save();
                    $user2->save();
                } catch (\Exception $e) {
                    Log::error("Fehler beim Verknüpfen von Sorg1 ({$user1->email}) und Sorg2 ({$user2->email}): " . $e->getMessage());
                }
            }

            // ── Kind-Verknüpfung (optional) ──────────────────────────────────────
            $kindVorname = $this->cellValue($row, 'kind_vorname');
            $kindNachname = $this->cellValue($row, 'kind_nachname');

            if (! empty($kindVorname) && ! empty($kindNachname)) {
                try {
                    $childQuery = Child::where('first_name', $kindVorname)
                        ->where('last_name', $kindNachname);

                    if ($Lerngruppe) {
                        $childQuery->where(function ($q) use ($Lerngruppe) {
                            $q->where('group_id', $Lerngruppe->id)
                                ->orWhere('class_id', $Lerngruppe->id);
                        });
                    }

                    $child = $childQuery->first();

                    if ($child) {
                        if ($user1) {
                            $child->parents()->syncWithoutDetaching([$user1->id]);
                        }
                        if ($user2) {
                            $child->parents()->syncWithoutDetaching([$user2->id]);
                        }
                        Log::info("Kind verknüpft: {$kindVorname} {$kindNachname} (ID: {$child->id})");
                    } else {
                        Log::info("Kind nicht gefunden – übersprungen: {$kindVorname} {$kindNachname}");
                    }
                } catch (\Exception $e) {
                    Log::error("Fehler bei Kind-Verknüpfung ({$kindVorname} {$kindNachname}): " . $e->getMessage());
                }
            }
        }
    }
}

