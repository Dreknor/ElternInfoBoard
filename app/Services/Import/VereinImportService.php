<?php

namespace App\Services\Import;

use App\Mail\NewUserPasswordMail;
use App\Model\Group;
use App\Model\User;
use App\Settings\EmailSetting;
use App\Support\PasswordGenerator;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Import der Vereinsmitglieder (eine Zeile pro Person).
 *
 * - Bestehende Konten werden über alle E-Mail-Adressen der Zeile gefunden
 *   (Groß-/Kleinschreibung egal, mehrere Adressen per „;“ oder „,“).
 * - Passen mehrere Adressen zu verschiedenen Konten, liegt das Konto im
 *   Papierkorb oder gibt es nur einen Namenstreffer mit anderer E-Mail, wird
 *   nichts verknüpft oder angelegt, sondern ein Klärungsfall gemeldet.
 * - Neue Konten werden nur angelegt, wenn weder E-Mail noch Name passen.
 * - Verändert werden ausschließlich die Gruppe und die Rolle
 *   „Vereinsmitglied“; alle anderen Gruppen, Rollen und Kontodaten bleiben
 *   unangetastet.
 * - Optional werden Mitglieder, die nicht mehr in der Datei stehen, aus
 *   Vereinsgruppe und -rolle entfernt (Abgleich).
 * - Dry-Run: es wird nur geplant, nichts geschrieben, keine Mails.
 */
class VereinImportService
{
    public const GROUP_NAME = 'Vereinsmitglied';

    public const ROLE_NAME = 'Vereinsmitglied';

    /**
     * @param  iterable<array<string, mixed>>  $rows  Zeilen mit Überschriften als Schlüssel
     */
    public function run(iterable $rows, bool $dryRun = true, bool $removeMissing = true, bool $sendEmail = true): VereinImportReport
    {
        $report = new VereinImportReport($dryRun);

        $group = $this->group();
        $role = Role::where('name', self::ROLE_NAME)->where('guard_name', 'web')->first();

        $currentMembers = $this->currentMembers($group, $role);

        /** @var array<int, User> $linked bestehende Konten aus der Datei */
        $linked = [];
        /** @var array<string, array{name: string, email: string}> $toCreate neue Konten, nach E-Mail */
        $toCreate = [];

        $rowNumber = 1; // Kopfzeile
        foreach ($rows as $raw) {
            $rowNumber++;
            $row = $this->normalize(is_array($raw) ? $raw : collect($raw)->toArray());

            if ($this->isEmptyRow($row)) {
                continue;
            }

            $report->rows++;
            $this->planRow($row, $rowNumber, $report, $linked, $toCreate);
        }

        foreach ($linked as $user) {
            if (! isset($currentMembers[$user->id])) {
                $report->linked[] = $this->entry($user);
            } elseif (! $this->isComplete($user, $group, $role)) {
                $report->completed[] = $this->entry($user);
            } else {
                $report->unchanged++;
            }
        }

        foreach ($toCreate as $data) {
            $report->created[] = $data;
        }

        $toRemove = $removeMissing
            ? array_diff_key($currentMembers, $linked)
            : [];

        foreach ($toRemove as $user) {
            $report->removed[] = $this->entry($user);
        }

        if (! $removeMissing) {
            $report->kept = count(array_diff_key($currentMembers, $linked));
        }

        if ($dryRun) {
            return $report;
        }

        DB::transaction(function () use (&$group, &$role, $linked, $toCreate, $toRemove, $sendEmail, $report) {
            $group ??= Group::create(['name' => self::GROUP_NAME, 'protected' => 1, 'bereich' => 'Verein']);
            $role ??= Role::create(['name' => self::ROLE_NAME, 'guard_name' => 'web']);

            foreach ($linked as $user) {
                $this->attach($user, $group, $role);
            }

            $welcomeText = $sendEmail ? app(EmailSetting::class)->new_user_welcome_text : null;
            foreach ($toCreate as $data) {
                $password = PasswordGenerator::generate();
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make($password),
                    'changePassword' => 1,
                    'lastEmail' => Carbon::now(),
                ]);
                $this->attach($user, $group, $role);

                if ($sendEmail) {
                    try {
                        Mail::to($user->email)->queue(new NewUserPasswordMail($user, $password, $welcomeText));
                    } catch (\Throwable $e) {
                        Log::error('Vereins-Import: Willkommens-Mail an '.$user->email.' fehlgeschlagen: '.$e->getMessage());
                    }
                } else {
                    $report->newUsers[] = ['name' => $user->name, 'email' => $user->email, 'password' => $password];
                }
            }

            foreach ($toRemove as $user) {
                DB::table('group_user')->where('group_id', $group->id)->where('user_id', $user->id)->delete();
                if ($user->hasRole($role)) {
                    $user->removeRole($role);
                }
            }
        });

        return $report;
    }

    /**
     * @param  array<int, User>  $linked
     * @param  array<string, array{name: string, email: string}>  $toCreate
     */
    private function planRow(array $row, int $rowNumber, VereinImportReport $report, array &$linked, array &$toCreate): void
    {
        $name = $this->name($row);
        $rawEmails = (string) ($row['email'] ?? '');
        $emails = $this->emails($rawEmails);
        $label = $name !== '' ? $name : 'Zeile ohne Namen';

        foreach ($this->invalidEmails($rawEmails) as $invalid) {
            $report->review($rowNumber, $label, "„{$invalid}“ ist keine gültige E-Mail-Adresse und wird ignoriert.");
        }

        if ($emails === []) {
            $candidates = $name !== '' ? $this->usersByName($name) : collect();
            $hint = $candidates->isNotEmpty() ? ' Mögliche Konten: '.$candidates->map(fn (User $u) => $this->label($u))->implode(', ').'.' : '';
            $report->review($rowNumber, $label, "Keine gültige E-Mail-Adresse – nicht zugeordnet.{$hint}");

            return;
        }

        $matches = User::withTrashed()
            ->whereIn(DB::raw('LOWER(email)'), $emails)
            ->get();

        $active = $matches->filter(fn (User $u) => ! $u->trashed());
        $trashed = $matches->filter(fn (User $u) => $u->trashed());

        // Mehrere Konten mit derselben Adresse (lokal + UCS) gelten als eine Person;
        // je Adresse wird wie im Schüler-Import das lokale Konto bevorzugt.
        $byEmail = $active
            ->sortBy(fn (User $u) => $u->ucs_source === 'local' || $u->ucs_source === null ? 0 : 1)
            ->unique(fn (User $u) => mb_strtolower($u->email));
        $people = $byEmail->unique('id');

        if ($people->count() > 1) {
            $report->review($rowNumber, $label, "Die E-Mail-Adressen gehören zu verschiedenen Konten (".$people->map(fn (User $u) => $this->label($u))->implode(', ').') – nicht zugeordnet.');

            return;
        }

        if ($people->count() === 1) {
            $user = $people->first();
            if (isset($linked[$user->id])) {
                $report->review($rowNumber, $label, "Konto {$this->label($user)} steht bereits in einer anderen Zeile.");
            }
            $linked[$user->id] = $user;
            $report->matchedRows++;

            return;
        }

        if ($trashed->isNotEmpty()) {
            $report->review($rowNumber, $label, "Konto ".$trashed->map(fn (User $u) => $this->label($u))->implode(', ').' liegt im Papierkorb – bitte erst wiederherstellen.');

            return;
        }

        // Kein Konto mit dieser Adresse: gleichnamige Konten nicht doppelt anlegen.
        $sameName = $name !== '' ? $this->usersByName($name) : collect();
        if ($sameName->isNotEmpty()) {
            $report->review($rowNumber, "{$label} <{$emails[0]}>", "Kein Konto mit dieser E-Mail, aber gleichnamige Konten vorhanden (".$sameName->map(fn (User $u) => $this->label($u))->implode(', ').') – kein neues Konto angelegt. Bitte E-Mail-Adresse prüfen oder das Konto über „Nicht-Vereinsmitglieder“ hinzufügen.');

            return;
        }

        if (isset($toCreate[$emails[0]])) {
            $report->review($rowNumber, $label, "E-Mail {$emails[0]} steht bereits in einer anderen Zeile.");

            return;
        }

        $toCreate[$emails[0]] = ['name' => $name !== '' ? $name : $emails[0], 'email' => $emails[0]];
    }

    private function attach(User $user, Group $group, Role $role): void
    {
        $user->groups()->syncWithoutDetaching([$group->id]);
        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }
    }

    private function isComplete(User $user, ?Group $group, ?Role $role): bool
    {
        return $group !== null && $role !== null
            && $user->groups()->whereKey($group->id)->exists()
            && $user->hasRole($role);
    }

    /**
     * Aktuelle Mitglieder: in der Vereinsgruppe oder mit der Vereinsrolle.
     *
     * @return array<int, User>
     */
    private function currentMembers(?Group $group, ?Role $role): array
    {
        $members = collect();
        if ($group !== null) {
            $members = $members->merge($group->users()->get());
        }
        if ($role !== null) {
            $members = $members->merge(User::role($role)->get());
        }

        return $members->keyBy('id')->all();
    }

    private function group(): ?Group
    {
        return Group::withoutGlobalScopes()
            ->where('name', self::GROUP_NAME)
            ->orderByRaw('CASE WHEN owner_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('id')
            ->first();
    }

    /**
     * @return Collection<int, User>
     */
    private function usersByName(string $name): Collection
    {
        return User::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->get();
    }

    /**
     * @return list<string> gültige, kleingeschriebene Adressen in Datei-Reihenfolge
     */
    private function emails(string $value): array
    {
        $emails = [];
        foreach (preg_split('/[;,\s]+/', $value, -1, PREG_SPLIT_NO_EMPTY) as $part) {
            $part = mb_strtolower(trim($part));
            if (filter_var($part, FILTER_VALIDATE_EMAIL)) {
                $emails[$part] = $part;
            }
        }

        return array_values($emails);
    }

    /**
     * @return list<string>
     */
    private function invalidEmails(string $value): array
    {
        return array_values(array_filter(
            preg_split('/[;,\s]+/', $value, -1, PREG_SPLIT_NO_EMPTY),
            fn (string $part) => ! filter_var(trim($part), FILTER_VALIDATE_EMAIL),
        ));
    }

    private function name(array $row): string
    {
        $name = trim((string) ($row['name'] ?? ''));
        if ($name === '') {
            $name = trim(($row['vorname'] ?? '').' '.($row['nachname'] ?? ''));
        }

        return preg_replace('/\s+/', ' ', $name);
    }

    /**
     * @return array{name: string, email: string}
     */
    private function entry(User $user): array
    {
        return ['name' => $user->name, 'email' => (string) $user->email];
    }

    private function label(User $user): string
    {
        return $user->name.' <'.$user->email.'>';
    }

    /**
     * Überschriften vereinheitlichen: „Person: Vorname Nachname“ / „Person: E-Mail Privat“
     * aus der Schulsoftware sowie einfache Spalten „Name“, „Vorname“, „Nachname“, „E-Mail“.
     */
    private function normalize(array $row): array
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            $key = Str::of((string) $key)->lower()->ascii()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->toString();
            $key = match (true) {
                in_array($key, ['person_vorname_nachname', 'vorname_nachname', 'name'], true) => 'name',
                in_array($key, ['person_vorname', 'vorname'], true) => 'vorname',
                in_array($key, ['person_nachname', 'nachname'], true) => 'nachname',
                in_array($key, ['person_e_mail_privat', 'e_mail_privat', 'e_mail', 'email', 'mail'], true) => 'email',
                default => $key,
            };
            if (! isset($normalized[$key]) || ! filled($normalized[$key])) {
                $normalized[$key] = is_string($value) ? trim($value) : $value;
            }
        }

        return $normalized;
    }

    private function isEmptyRow(array $row): bool
    {
        return collect($row)->filter(fn ($value) => filled($value))->isEmpty();
    }
}
