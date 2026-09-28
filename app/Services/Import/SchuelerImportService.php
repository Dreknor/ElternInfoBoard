<?php

namespace App\Services\Import;

use App\Enums\GuardianRelation;
use App\Mail\NewUserPasswordMail;
use App\Model\Child;
use App\Model\ChildGuardian;
use App\Model\Family;
use App\Model\Group;
use App\Model\User;
use App\Services\Family\FamilyService;
use App\Services\Family\GroupMembershipService;
use App\Services\Family\GuardianshipService;
use App\Settings\EmailSetting;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Kind-zentrierter Import: eine Zeile pro Kind mit Schüler-ID (E4) und bis
 * zu drei Bezugspersonen (Konzept §8.1).
 *
 * - Kinder werden ausschließlich über die Schüler-ID abgeglichen. Nur solange
 *   Bestandskinder noch keine ID haben, wird einmalig über Name + Klasse
 *   zugeordnet (eindeutig, sonst Report).
 * - UCS-Kinder werden nur verknüpft, nicht überschrieben.
 * - Bezugspersonen per E-Mail; Beziehungen mit Standardrechten, Sorgerecht
 *   optional aus der Datei.
 * - Familie je Zeile; Konflikte mit bestehenden/gesperrten Familien werden
 *   gemeldet statt geändert.
 * - Gruppen der Eltern werden aus den Kindern abgeleitet; „Weitere Gruppen“
 *   werden als manuelle Mitgliedschaft gesetzt.
 * - Dry-Run: alles in einer Transaktion, die zurückgerollt wird; keine Mails.
 */
class SchuelerImportService
{
    /** @var Collection<string, Group> */
    private Collection $groups;

    /** @var array<int, true> */
    private array $touchedUserIds = [];

    public function __construct(
        private readonly GuardianshipService $guardianship,
        private readonly FamilyService $families,
        private readonly GroupMembershipService $groupMembership,
    ) {}

    /**
     * @param  iterable<array<string, mixed>>  $rows  Zeilen mit Überschriften als Schlüssel
     */
    public function run(iterable $rows, bool $dryRun = false, bool $markLeavers = false): SchuelerImportReport
    {
        $report = new SchuelerImportReport($dryRun);
        $this->groups = Group::withoutGlobalScopes()->get()->keyBy(fn (Group $g) => mb_strtolower(trim($g->name)));
        $this->touchedUserIds = [];

        DB::beginTransaction();

        try {
            $seenExternalIds = [];
            $rowNumber = 1; // Kopfzeile

            foreach ($rows as $raw) {
                $rowNumber++;
                $row = $this->normalize(is_array($raw) ? $raw : collect($raw)->toArray());

                if ($this->isEmptyRow($row)) {
                    continue;
                }

                $report->rows++;
                $externalId = trim((string) ($row['schueler_id'] ?? ''));

                if ($externalId === '') {
                    $report->error($rowNumber, 'Schüler-ID fehlt – Zeile übersprungen.');

                    continue;
                }

                if (isset($seenExternalIds[$externalId])) {
                    $report->error($rowNumber, "Schüler-ID {$externalId} ist mehrfach in der Datei.");

                    continue;
                }
                $seenExternalIds[$externalId] = true;

                $this->importRow($row, $externalId, $rowNumber, $dryRun, $report);
            }

            if ($markLeavers && $seenExternalIds !== []) {
                $this->markLeavers(array_keys($seenExternalIds), $report);
            }

            if (! $dryRun) {
                $this->groupMembership->syncUsers(array_keys($this->touchedUserIds));
            }

            $dryRun ? DB::rollBack() : DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $report;
    }

    private function importRow(array $row, string $externalId, int $rowNumber, bool $dryRun, SchuelerImportReport $report): void
    {
        $firstName = trim((string) ($row['kind_vorname'] ?? ''));
        $lastName = trim((string) ($row['kind_nachname'] ?? ''));

        if ($firstName === '' || $lastName === '') {
            $report->error($rowNumber, "Schüler-ID {$externalId}: Vor- und Nachname des Kindes sind Pflicht.");

            return;
        }

        $class = $this->group($row['klasse'] ?? null);
        $careGroup = $this->group($row['gruppe'] ?? null);
        $gradeGroup = filled($row['klassenstufe'] ?? null) ? $this->group('Klassenstufe '.trim((string) $row['klassenstufe'])) : null;

        if (filled($row['klasse'] ?? null) && $class === null) {
            $report->error($rowNumber, "Schüler-ID {$externalId}: Klasse „{$row['klasse']}“ existiert nicht als Gruppe.");
        }

        $child = $this->upsertChild($externalId, $firstName, $lastName, $class, $careGroup, $rowNumber, $report);
        if ($child === null) {
            return;
        }

        if ($gradeGroup !== null) {
            $child->additionalGroups()->syncWithoutDetaching([$gradeGroup->id => ['source' => 'import']]);
        }

        // Bezugspersonen B1..B3
        $guardians = collect();
        foreach ([1, 2, 3] as $n) {
            $email = $this->firstEmail($row["b{$n}_email"] ?? null);
            if ($email === null) {
                continue;
            }

            $user = $this->upsertGuardian($email, trim(($row["b{$n}_vorname"] ?? '').' '.($row["b{$n}_nachname"] ?? '')), $dryRun, $report);
            $relation = GuardianRelation::fromInput($row["b{$n}_beziehung"] ?? null);
            $rights = $this->custodyOverride($row["b{$n}_sorgerecht"] ?? null);

            if ($this->guardianship->pivot($child, $user) === null) {
                $report->linksCreated++;
            }
            $this->guardianship->link($child, $user, $relation, $rights, ChildGuardian::SOURCE_IMPORT, syncGroups: false);

            $guardians->push($user);
            $this->touchedUserIds[$user->id] = true;
        }

        if ($guardians->isEmpty()) {
            $report->review($rowNumber, "Schüler-ID {$externalId}: keine Bezugsperson mit E-Mail angegeben.");

            return;
        }

        $this->assignFamily($guardians, $rowNumber, $externalId, $report);

        // Weitere (manuelle) Gruppen der Bezugspersonen
        foreach (preg_split('/\s*;\s*/', (string) ($row['weitere_gruppen'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) as $name) {
            $group = $this->group($name);
            if ($group === null) {
                $report->error($rowNumber, "Gruppe „{$name}“ existiert nicht.");

                continue;
            }
            foreach ($guardians as $guardian) {
                $guardian->groups()->syncWithoutDetaching([$group->id]);
            }
        }
    }

    private function upsertChild(string $externalId, string $firstName, string $lastName, ?Group $class, ?Group $careGroup, int $rowNumber, SchuelerImportReport $report): ?Child
    {
        $child = Child::withTrashed()->where('external_id', $externalId)->first();

        if ($child === null) {
            // Erst-Import: Bestandskind ohne ID über Name (+ Klasse) zuordnen
            $candidates = Child::query()
                ->whereNull('external_id')
                ->where('first_name', $firstName)
                ->where('last_name', $lastName)
                ->when($class, fn ($q) => $q->where(fn ($c) => $c->where('class_id', $class->id)->orWhereNull('class_id')))
                ->get();

            if ($candidates->count() > 1) {
                $report->review($rowNumber, "Schüler-ID {$externalId}: mehrere Bestandskinder „{$firstName} {$lastName}“ – bitte manuell zuordnen, Zeile übersprungen.");

                return null;
            }

            if ($candidates->count() === 1) {
                $child = $candidates->first();
                $child->update(['external_id' => $externalId]);
                $report->childrenMatched++;
            }
        }

        if ($child === null) {
            $report->childrenCreated++;

            return Child::create([
                'external_id' => $externalId,
                'status' => Child::STATUS_ACTIVE,
                'entry_date' => today(),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'class_id' => $class?->id,
                'group_id' => $careGroup?->id,
                'ucs_source' => 'local',
            ]);
        }

        if ($child->trashed()) {
            $child->restore();
            $child->update(['status' => Child::STATUS_ACTIVE, 'exit_date' => null]);
        }

        if ($child->ucs_source === 'kelvin') {
            // UCS pflegt Namen und Klasse – nur die Betreuungsgruppe übernehmen
            if ($careGroup && $child->group_id !== $careGroup->id) {
                $child->update(['group_id' => $careGroup->id]);
            }

            return $child;
        }

        $child->fill(array_filter([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'class_id' => $class?->id,
            'group_id' => $careGroup?->id,
        ], fn ($value) => $value !== null));

        if ($child->isDirty()) {
            $child->save();
            $report->childrenUpdated++;
        }

        return $child;
    }

    private function upsertGuardian(string $email, string $name, bool $dryRun, SchuelerImportReport $report): User
    {
        $user = User::query()->where('email', $email)->orderByRaw("CASE WHEN ucs_source = 'local' THEN 0 ELSE 1 END")->first();

        if ($user === null) {
            $password = Str::password(12, true, true, true, false);
            $user = User::create([
                'email' => $email,
                'name' => $name !== '' ? $name : $email,
                'password' => Hash::make($password),
                'changePassword' => 1,
                'lastEmail' => Carbon::now(),
            ]);
            $report->usersCreated++;

            if (! $dryRun) {
                try {
                    Mail::to($user->email)->queue(new NewUserPasswordMail($user, $password, app(EmailSetting::class)->new_user_welcome_text));
                } catch (\Throwable $e) {
                    Log::error('Schüler-Import: Willkommens-Mail an '.$user->email.' fehlgeschlagen: '.$e->getMessage());
                }
            }
        }

        if (Role::where('name', 'Eltern')->where('guard_name', 'web')->exists()) {
            $user->assignRole('Eltern');
        }
        if (Role::where('name', 'Aufnahme')->where('guard_name', 'web')->exists()) {
            $user->removeRole('Aufnahme');
        }

        return $user;
    }

    /**
     * @param  Collection<int, User>  $guardians
     */
    private function assignFamily(Collection $guardians, int $rowNumber, string $externalId, SchuelerImportReport $report): void
    {
        $guardians = $guardians->map(fn (User $u) => $u->fresh())->unique('id')->values();
        $familyIds = $guardians->pluck('family_id')->filter()->unique()->values();

        if ($familyIds->isEmpty()) {
            $this->families->create($guardians, null, Family::SOURCE_IMPORT);
            $report->familiesCreated++;

            return;
        }

        if ($familyIds->count() > 1) {
            $report->review($rowNumber, "Schüler-ID {$externalId}: Bezugspersonen gehören zu verschiedenen Familien – keine Änderung.");

            return;
        }

        $family = Family::find($familyIds->first());
        $without = $guardians->whereNull('family_id');

        if ($without->isEmpty()) {
            return;
        }

        if ($family->is_locked) {
            $report->review($rowNumber, "Schüler-ID {$externalId}: {$family->name} ist gesperrt – ".$without->pluck('name')->implode(', ').' nicht hinzugefügt.');

            return;
        }

        foreach ($without as $user) {
            $this->families->addMember($family, $user);
        }
        $report->familiesExtended++;
    }

    /**
     * Kinder mit Schüler-ID, die nicht in der Datei stehen → Abgänger (weich gelöscht).
     *
     * @param  list<string>  $seenExternalIds
     */
    private function markLeavers(array $seenExternalIds, SchuelerImportReport $report): void
    {
        $leavers = Child::query()
            ->whereNotNull('external_id')
            ->whereNotIn('external_id', $seenExternalIds)
            ->where(fn ($q) => $q->whereNull('ucs_source')->orWhere('ucs_source', '!=', 'kelvin'))
            ->get();

        foreach ($leavers as $child) {
            foreach ($child->parents()->pluck('users.id') as $userId) {
                $this->touchedUserIds[(int) $userId] = true;
            }
            $report->leavers[] = trim($child->first_name.' '.$child->last_name).' ('.$child->external_id.')';
            $child->update(['status' => Child::STATUS_LEFT, 'exit_date' => today()]);
            $child->delete();
        }
    }

    private function group(?string $name): ?Group
    {
        $name = trim((string) $name);

        return $name === '' ? null : $this->groups->get(mb_strtolower($name));
    }

    private function firstEmail(mixed $value): ?string
    {
        $email = trim(explode(';', (string) $value)[0]);

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? mb_strtolower($email) : null;
    }

    /**
     * @return array{has_custody?: bool}
     */
    private function custodyOverride(mixed $value): array
    {
        $value = mb_strtolower(trim((string) $value));

        return match (true) {
            in_array($value, ['j', 'ja', 'x', '1', 'yes', 'true'], true) => ['has_custody' => true],
            in_array($value, ['n', 'nein', '0', 'no', 'false'], true) => ['has_custody' => false],
            default => [],
        };
    }

    /**
     * Überschriften vereinheitlichen (Excel-Slugs, Umlaute, „E-Mail“).
     */
    private function normalize(array $row): array
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            $key = Str::of((string) $key)->lower()->ascii()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->toString();
            $key = str_replace(['e_mail', 'schuler_id', 'schueler_nr', 'schuler_nr'], ['email', 'schueler_id', 'schueler_id', 'schueler_id'], $key);
            $normalized[$key] = is_string($value) ? trim($value) : $value;
        }

        return $normalized;
    }

    private function isEmptyRow(array $row): bool
    {
        return collect($row)->filter(fn ($value) => filled($value))->isEmpty();
    }
}
