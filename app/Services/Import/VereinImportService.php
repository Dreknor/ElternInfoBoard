<?php

namespace App\Services\Import;

use App\Mail\NewUserPasswordMail;
use App\Model\Group;
use App\Model\Notification;
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
 *   Papierkorb, fehlt eine gültige E-Mail oder gibt es nur einen Namenstreffer
 *   mit anderer E-Mail, entsteht ein Klärungsfall. Für ihn kann bei der
 *   Bestätigung eine Entscheidung mitgegeben werden (Konto verknüpfen,
 *   wiederherstellen, neu anlegen oder per E-Mail zuordnen).
 * - Neue Konten werden nur angelegt, wenn weder E-Mail noch Name passen.
 * - Jede einzelne Änderung kann bei der Bestätigung ab- oder angewählt werden.
 * - Verändert werden ausschließlich die Gruppe und die Rolle
 *   „Vereinsmitglied“; alle anderen Gruppen, Rollen und Kontodaten bleiben
 *   unangetastet (Ausnahme: ausdrücklich gewählte Wiederherstellung aus dem
 *   Papierkorb).
 * - Optional werden Mitglieder, die nicht mehr in der Datei stehen, aus
 *   Vereinsgruppe und -rolle entfernt (Abgleich).
 * - Dry-Run: es wird nur geplant, nichts geschrieben, keine Mails.
 */
class VereinImportService
{
    public const GROUP_NAME = 'Vereinsmitglied';

    public const ROLE_NAME = 'Vereinsmitglied';

    public const NOTIFICATION_ICON = 'fas fa-users';

    /**
     * Hinweis für die Zugangsdaten-Mail bzw. das Zugangsdaten-PDF neuer
     * Vereinskonten (Einstellungen › E-Mail); leer = kein Hinweis.
     */
    public static function accessNotice(): ?string
    {
        return filled($notice = app(EmailSetting::class)->verein_access_notice) ? trim($notice) : null;
    }

    /**
     * @param  iterable<array<string, mixed>>  $rows  Zeilen mit Überschriften als Schlüssel
     * @param  list<string>|null  $selection  Schlüssel der zu übernehmenden Änderungen (null = alle)
     * @param  array<int|string, string>  $decisions  Entscheidungen zu Klärungsfällen je Zeile
     *                                                 (skip, link:ID, restore:ID, create, email:ADRESSE)
     */
    public function run(iterable $rows, bool $dryRun = true, bool $removeMissing = true, bool $sendEmail = true, ?array $selection = null, array $decisions = [], bool $notify = true): VereinImportReport
    {
        $report = new VereinImportReport($dryRun);

        $group = $this->group();
        $role = Role::where('name', self::ROLE_NAME)->where('guard_name', 'web')->first();

        $currentMembers = $this->currentMembers($group, $role);

        /** @var array<int, User> $linked bestehende Konten aus der Datei */
        $linked = [];
        /** @var array<int, array{row: int, name: string, email: string}> $sources Herkunft je Konto */
        $sources = [];
        /** @var array<string, array{row: int, name: string, email: string}> $toCreate neue Konten, nach E-Mail */
        $toCreate = [];
        /** @var list<array<string, mixed>> $cases Klärungsfälle mit Optionen */
        $cases = [];

        $rowNumber = 1; // Kopfzeile
        foreach ($rows as $raw) {
            $rowNumber++;
            $row = $this->normalize(is_array($raw) ? $raw : collect($raw)->toArray());

            if ($this->isEmptyRow($row)) {
                continue;
            }

            $report->rows++;
            $this->planRow($row, $rowNumber, $report, $linked, $sources, $toCreate, $cases);
        }

        // Entscheidungen zu Klärungsfällen (nur bei der Bestätigung)
        /** @var array<int, User> $toRestore */
        $toRestore = [];
        $decided = [];
        foreach ($cases as $case) {
            $source = ['row' => $case['row'], 'name' => $case['name'], 'email' => $case['rawEmail']];

            if ($dryRun) {
                $report->cases[] = $case;
                $report->unassigned[] = $source + ['reason' => $case['message']];

                continue;
            }

            $resolution = $this->resolve($case, (string) ($decisions[$case['row']] ?? 'skip'), $toCreate);

            if ($resolution['type'] === 'skip' || $resolution['type'] === 'failed') {
                $report->unassigned[] = $source + ['reason' => $resolution['reason'] ?? $case['message']];

                continue;
            }

            if ($resolution['type'] === 'create') {
                $toCreate[$case['email']] = ['row' => $case['row'], 'name' => $case['name'], 'email' => $case['email']];
                $decided['new:'.$case['email']] = true;
            } else {
                $user = $resolution['user'];
                $linked[$user->id] = $user;
                $sources[$user->id] ??= $source;
                $decided['user:'.$user->id] = true;
                if ($resolution['type'] === 'restore') {
                    $toRestore[$user->id] = $user;
                }
            }
            $report->resolved[] = ['row' => $case['row'], 'name' => $case['name'], 'action' => $resolution['label']];
        }

        $isSelected = fn (string $key) => $selection === null || isset($decided[$key]) || in_array($key, $selection, true);

        $apply = [];
        foreach ($linked as $id => $user) {
            $key = 'user:'.$id;
            if (! isset($currentMembers[$id])) {
                $list = 'linked';
            } elseif (! $this->isComplete($user, $group, $role)) {
                $list = 'completed';
            } else {
                $report->unchanged++;
                $apply[$id] = $user;

                continue;
            }

            if (! $isSelected($key)) {
                if ($list === 'linked') {
                    $source = $sources[$id] ?? ['row' => 0, 'name' => $user->name, 'email' => (string) $user->email];
                    $report->unassigned[] = $source + ['reason' => 'Bei der Bestätigung abgewählt (Konto '.$this->label($user).').'];
                }

                continue;
            }

            $report->{$list}[] = $this->entry($user) + ['key' => $key, 'row' => $sources[$id]['row'] ?? null];
            $apply[$id] = $user;
        }

        $create = [];
        foreach ($toCreate as $email => $data) {
            $key = 'new:'.$email;
            if (! $isSelected($key)) {
                $report->unassigned[] = $data + ['reason' => 'Bei der Bestätigung abgewählt (kein Konto angelegt).'];

                continue;
            }
            $report->created[] = ['name' => $data['name'], 'email' => $email, 'key' => $key, 'row' => $data['row']];
            $create[$email] = $data;
        }

        $toRemove = [];
        foreach ($removeMissing ? array_diff_key($currentMembers, $linked) : [] as $id => $user) {
            if ($isSelected('remove:'.$id)) {
                $toRemove[$id] = $user;
                $report->removed[] = $this->entry($user) + ['key' => 'remove:'.$id, 'row' => null];
            }
        }

        $remaining = array_diff_key($currentMembers, $linked, $toRemove);
        $report->kept = count($remaining);
        $report->membersAfter = count($apply) + count($create) + count($remaining);

        // Erreichbarkeit der (künftigen) Mitglieder mit bestehendem Konto
        foreach ($apply + $remaining as $user) {
            if ($reason = $this->unreachableReason($user)) {
                $report->unreachable[] = $this->entry($user) + ['reason' => $reason];
            }
        }

        if ($dryRun) {
            return $report;
        }

        DB::transaction(function () use (&$group, &$role, $apply, $create, $toRemove, $toRestore, $sendEmail, $report) {
            $group ??= Group::create(['name' => self::GROUP_NAME, 'protected' => 1, 'bereich' => 'Verein']);
            $role ??= Role::create(['name' => self::ROLE_NAME, 'guard_name' => 'web']);

            foreach ($toRestore as $user) {
                $user->restore();
            }

            foreach ($apply as $user) {
                $this->attach($user, $group, $role);
            }

            $welcomeText = $sendEmail && $create !== [] ? app(EmailSetting::class)->new_user_welcome_text : null;
            $notice = $create !== [] ? self::accessNotice() : null;
            foreach ($create as $data) {
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
                        Mail::to($user->email)->queue(new NewUserPasswordMail($user, $password, $welcomeText, $notice));
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

        if ($notify) {
            $settings = app(EmailSetting::class);
            $added = array_filter($apply, fn (User $user) => ! isset($currentMembers[$user->id]));
            $report->notifiedAdded = $this->notify($added, 'Willkommen im Vereinsbereich', $settings->verein_added_text);
            $report->notifiedRemoved = $this->notify($toRemove, 'Änderung Ihrer Vereinsmitgliedschaft', $settings->verein_removed_text);
        }

        return $report;
    }

    /**
     * Bestehende Konten per Benachrichtigung in der Anwendung (Glocke, Push nach
     * Nutzereinstellung) über die geänderte Vereinszugehörigkeit informieren.
     *
     * @param  array<int, User>  $users
     */
    private function notify(array $users, string $title, ?string $text): int
    {
        if (blank($text)) {
            return 0;
        }

        foreach ($users as $user) {
            Notification::create([
                'user_id' => $user->id,
                'type' => 'Verein',
                'title' => $title,
                'message' => Str::limit(trim($text), 250),
                'icon' => self::NOTIFICATION_ICON,
                'url' => url('/'),
            ]);
        }

        return count($users);
    }

    /**
     * Entscheidung zu einem Klärungsfall auswerten. Konten dürfen nur aus den
     * Kandidaten des Falls oder per eindeutiger E-Mail-Adresse gewählt werden.
     *
     * @param  array<string, mixed>  $toCreate
     * @return array{type: string, label?: string, user?: User, reason?: string}
     */
    private function resolve(array $case, string $decision, array $toCreate): array
    {
        [$type, $value] = array_pad(explode(':', $decision, 2), 2, '');

        switch ($type) {
            case 'link':
                $user = User::find((int) $value);
                if ($user === null || ! in_array($user->id, $case['candidates'], true)) {
                    return ['type' => 'failed', 'reason' => 'Gewähltes Konto ist nicht mehr vorhanden.'];
                }

                return ['type' => 'link', 'user' => $user, 'label' => 'verknüpft mit '.$this->label($user)];

            case 'restore':
                $user = User::onlyTrashed()->find((int) $value);
                if ($user === null || ! in_array($user->id, $case['trashed'], true)) {
                    return ['type' => 'failed', 'reason' => 'Konto liegt nicht mehr im Papierkorb.'];
                }

                return ['type' => 'restore', 'user' => $user, 'label' => 'wiederhergestellt und verknüpft: '.$this->label($user)];

            case 'create':
                if (! $case['canCreate'] || isset($toCreate[$case['email']])
                    || User::withTrashed()->whereRaw('LOWER(email) = ?', [$case['email']])->exists()) {
                    return ['type' => 'failed', 'reason' => 'Neues Konto nicht möglich – E-Mail-Adresse fehlt oder ist bereits vergeben.'];
                }

                return ['type' => 'create', 'label' => 'neues Konto für '.$case['email']];

            case 'email':
                $email = mb_strtolower(trim($value));
                $user = filter_var($email, FILTER_VALIDATE_EMAIL)
                    ? User::query()->whereRaw('LOWER(email) = ?', [$email])
                        ->orderByRaw("CASE WHEN ucs_source = 'local' THEN 0 ELSE 1 END")->first()
                    : null;
                if ($user === null) {
                    return ['type' => 'failed', 'reason' => "Kein Konto mit der E-Mail-Adresse „{$value}“ gefunden."];
                }

                return ['type' => 'link', 'user' => $user, 'label' => 'verknüpft mit '.$this->label($user)];

            default:
                return ['type' => 'skip', 'label' => 'übersprungen'];
        }
    }

    /**
     * @param  array<int, User>  $linked
     * @param  array<int, array{row: int, name: string, email: string}>  $sources
     * @param  array<string, array{row: int, name: string, email: string}>  $toCreate
     * @param  list<array<string, mixed>>  $cases
     */
    private function planRow(array $row, int $rowNumber, VereinImportReport $report, array &$linked, array &$sources, array &$toCreate, array &$cases): void
    {
        $name = $this->name($row);
        $rawEmails = trim((string) ($row['email'] ?? ''));
        $emails = $this->emails($rawEmails);
        $label = $name !== '' ? $name : 'Zeile ohne Namen';
        $source = ['row' => $rowNumber, 'name' => $label, 'email' => $rawEmails];

        $case = function (string $message, Collection $candidates, ?Collection $trashed = null, bool $canCreate = false) use (&$cases, $rowNumber, $label, $rawEmails, $emails): void {
            $trashed ??= collect();
            $cases[] = [
                'row' => $rowNumber,
                'name' => $label,
                'rawEmail' => $rawEmails,
                'email' => $emails[0] ?? null,
                'message' => $message,
                'candidates' => $candidates->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
                'trashed' => $trashed->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
                'canCreate' => $canCreate,
                'options' => $this->options($candidates, $trashed, $canCreate ? ($emails[0] ?? null) : null),
            ];
        };

        foreach ($this->invalidEmails($rawEmails) as $invalid) {
            $report->note($rowNumber, $label, "„{$invalid}“ ist keine gültige E-Mail-Adresse und wird ignoriert.");
        }

        if ($emails === []) {
            $candidates = $name !== '' ? $this->usersByName($name) : collect();
            $case('Keine gültige E-Mail-Adresse in der Datei.'.($candidates->isNotEmpty() ? ' Es gibt gleichnamige Konten.' : ''), $candidates);

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
        $people = $byEmail->unique('id')->values();

        if ($people->count() > 1) {
            $case('Die E-Mail-Adressen gehören zu verschiedenen Konten.', $people);

            return;
        }

        if ($people->count() === 1) {
            $user = $people->first();
            if (isset($linked[$user->id])) {
                $report->note($rowNumber, $label, "Konto {$this->label($user)} steht bereits in Zeile {$sources[$user->id]['row']}.");
            }
            $linked[$user->id] = $user;
            $sources[$user->id] ??= $source;
            $report->matchedRows++;

            return;
        }

        if ($trashed->isNotEmpty()) {
            $case('Das passende Konto liegt im Papierkorb.', collect(), $trashed->values());

            return;
        }

        // Kein Konto mit dieser Adresse: gleichnamige Konten nicht doppelt anlegen.
        $sameName = $name !== '' ? $this->usersByName($name) : collect();
        if ($sameName->isNotEmpty()) {
            $case("Kein Konto mit {$emails[0]}, aber gleichnamige Konten mit anderer E-Mail-Adresse.", $sameName, canCreate: true);

            return;
        }

        if (isset($toCreate[$emails[0]])) {
            $report->note($rowNumber, $label, "E-Mail {$emails[0]} steht bereits in Zeile {$toCreate[$emails[0]]['row']}.");

            return;
        }

        $toCreate[$emails[0]] = ['row' => $rowNumber, 'name' => $name !== '' ? $name : $emails[0], 'email' => $emails[0]];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function options(Collection $candidates, Collection $trashed, ?string $createEmail): array
    {
        $options = [];
        foreach ($candidates as $user) {
            $options[] = ['value' => 'link:'.$user->id, 'label' => 'Mit Konto '.$this->label($user).' verknüpfen'];
        }
        foreach ($trashed as $user) {
            $options[] = ['value' => 'restore:'.$user->id, 'label' => 'Konto '.$this->label($user).' wiederherstellen und verknüpfen'];
        }
        if ($createEmail !== null) {
            $options[] = ['value' => 'create', 'label' => "Neues Konto für {$createEmail} anlegen"];
        }

        return $options;
    }

    /**
     * Warum ein Konto über das System voraussichtlich nicht erreicht wird.
     */
    private function unreachableReason(User $user): ?string
    {
        if (! filter_var((string) $user->email, FILTER_VALIDATE_EMAIL)) {
            return 'Keine gültige E-Mail-Adresse im Konto – keine Benachrichtigungen möglich.';
        }
        if ($user->changePassword) {
            return 'Noch nie angemeldet (Startpasswort nicht geändert).';
        }

        return null;
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
