<?php

namespace App\Services;

use App\Model\Pflichtstunde;
use App\Model\PflichtstundenFamilyAccount;
use App\Model\PflichtstundenFamilyRule;
use App\Model\PflichtstundenFamilyRuleHistory;
use App\Model\User;
use App\Services\Pflichtstunden\PflichtstundenService;
use App\Services\Pflichtstunden\PflichtstundenUnit;
use App\Settings\PflichtstundenSetting;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

/**
 * Kontoführung der Pflichtstunden je Familie und Zeitraum (Sollmodell,
 * Salden, Übertrag).
 *
 * Die Familien-Einheiten und ihr Basis-Soll kommen aus dem
 * PflichtstundenService und damit aus dem FamilyResolver (legacy: sorg2,
 * child_centric: families). Regeln und Konten werden unter einem
 * family_key gespeichert (kleinste User-ID der Einheit – identisch zur
 * bisherigen sorg2-Logik). Beim Lesen wird zusätzlich über die IDs aller
 * Mitglieder gesucht, damit gespeicherte Regeln/Salden nach Änderungen der
 * Familienzusammensetzung (z. B. Umstellung auf das Familienmodell) erhalten
 * bleiben.
 */
class PflichtstundenFamilyService
{
    public function __construct(
        private readonly PflichtstundenSetting $settings,
        private readonly ?PflichtstundenService $unitService = null,
    ) {}

    private function unitService(): PflichtstundenService
    {
        return $this->unitService ?? app(PflichtstundenService::class);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function resolvePeriod(?int $year): array
    {
        if ($year) {
            $start = Carbon::createFromFormat('Y-m-d', $year.'-'.$this->settings->pflichtstunden_start)->startOfDay();
            $end = Carbon::createFromFormat('Y-m-d', ($year + 1).'-'.$this->settings->pflichtstunden_ende)->endOfDay();

            return [$start, $end];
        }

        $start = Carbon::createFromFormat('m-d', $this->settings->pflichtstunden_start)->startOfDay();
        if ($start->isFuture()) {
            $start->subYear();
        }

        $end = Carbon::createFromFormat('m-d', $this->settings->pflichtstunden_ende)->endOfDay();
        if ($end->isPast()) {
            $end->addYear();
        }

        return [$start, $end];
    }

    /**
     * Startjahr der Periode, zu der $periodStart gehört. Bei frei gewählten
     * Zeiträumen (z. B. 01.01.–30.06.) ist das nicht das Kalenderjahr des
     * Startdatums, sondern das Jahr des vorangegangenen Periodenbeginns.
     */
    public function periodStartYear(Carbon $periodStart): int
    {
        return $this->resolvePeriodStartYearForDate($periodStart);
    }

    /**
     * Ermittelt das Startjahr der Periode, in die das übergebene Datum fällt,
     * basierend auf den konfigurierten Perioden-Grenzen (z.B. 08-01–07-31).
     * Im Gegensatz zu resolvePeriod(null) (das immer die "laufende" Periode
     * relativ zu "jetzt" bestimmt) funktioniert dies für ein beliebiges Datum.
     */
    public function resolvePeriodStartYearForDate(Carbon $date): int
    {
        $periodStartInSameYear = Carbon::createFromFormat(
            'Y-m-d',
            $date->year.'-'.$this->settings->pflichtstunden_start
        )->startOfDay();

        return $date->lt($periodStartInSameYear) ? $date->year - 1 : $date->year;
    }

    public function determineFamilyKey(User $user, ?User $partner = null): string
    {
        $ids = [$user->id];
        if ($partner) {
            $ids[] = $partner->id;
        }

        return $this->familyKeyForUserIds($ids);
    }

    /**
     * @param  array<int, int>  $userIds
     */
    public function familyKeyForUserIds(array $userIds): string
    {
        return (string) min(array_map('intval', $userIds));
    }

    /**
     * @return Collection<int, array{
     *   family_key:string,
     *   user:User,
     *   partner:?User,
     *   members:Collection<int, User>,
     *   user_ids:array<int,int>,
     *   family_name:string,
     *   base_required_minutes:int,
     *   child_ids:array<int,int>
     * }>
     */
    public function getFamilyGroups(bool $includeTrashed = false, ?array $period = null): Collection
    {
        // Für bereits erfasste (auch rückwirkende) Zeiträume müssen endgültig
        // gelöschte (soft-deleted) Nutzer weiterhin als Familie auftauchen,
        // damit ihre freigegebenen Pflichtstunden korrekt abgerechnet werden
        // können. Für die laufende Übersicht bleiben sie standardmäßig
        // ausgeblendet.
        $query = $includeTrashed ? User::withTrashed() : User::query();

        try {
            $users = $query->permission('view Pflichtstunden')->orderBy('id')->get();
        } catch (PermissionDoesNotExist) {
            return collect();
        }

        if ($users->isEmpty()) {
            return collect();
        }

        return $this->unitService()
            ->units($period ?? $this->resolvePeriod(null), $users)
            ->map(function (PflichtstundenUnit $unit) {
                $members = $unit->members->sortBy('id')->values();
                $user = $members->first();

                return [
                    'family_key' => $this->familyKeyForUserIds($unit->userIds),
                    'user' => $user,
                    'partner' => $members->get(1),
                    'members' => $members,
                    'user_ids' => $unit->userIds,
                    'family_name' => $members
                        ->map(fn (User $member) => $member->name.($member->trashed() ? ' (gelöscht)' : ''))
                        ->implode(' / '),
                    'base_required_minutes' => $unit->requiredMinutes,
                    'child_ids' => $unit->childIds,
                ];
            })
            ->sortBy('family_key', SORT_NUMERIC)
            ->values();
    }

    /**
     * Sucht einen gespeicherten Eintrag (Regel/Konto) der Einheit: zuerst unter
     * dem aktuellen Schlüssel, sonst unter dem Schlüssel eines Mitglieds (ältere
     * Zusammensetzung der Familie, z. B. vor der Umstellung auf Familien).
     *
     * @param  Collection<string, mixed>  $byKey
     * @param  array<string, mixed>  $group
     */
    private function lookupForGroup(Collection $byKey, array $group): mixed
    {
        if ($byKey->has($group['family_key'])) {
            return $byKey->get($group['family_key']);
        }

        foreach ($group['user_ids'] as $userId) {
            if ($byKey->has((string) $userId)) {
                return $byKey->get((string) $userId);
            }
        }

        return null;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    /**
     * Bei abgelaufenen Zeiträumen werden vorhandene Konten nur mit
     * $overwriteClosedAccounts überschrieben (Jahresabschluss, Versiegeln vor
     * dem endgültigen Löschen). Sonst würde bereits das Ansehen eines alten
     * Zeitraums abgerechnete Salden neu berechnen – etwa nach der Umstellung
     * auf das Familienmodell mit anderer Familienzusammensetzung.
     */
    public function buildFamilySummaries(Carbon $periodStart, Carbon $periodEnd, bool $persistAccounts = true, bool $includeTrashed = false, bool $overwriteClosedAccounts = false): Collection
    {
        $protectExisting = ! $overwriteClosedAccounts && $periodEnd->isPast();
        $periodYear = $this->periodStartYear($periodStart);
        $groups = $this->getFamilyGroups($includeTrashed, [$periodStart, $periodEnd]);
        $rules = PflichtstundenFamilyRule::query()
            ->where('period_year', $periodYear)
            ->get()
            ->keyBy('family_key');

        $allUserIds = $groups
            ->flatMap(fn (array $group) => $group['user_ids'])
            ->unique()
            ->values();

        $entries = Pflichtstunde::withoutGlobalScope('aktuellerZeitraum')
            ->whereIn('user_id', $allUserIds)
            ->whereBetween('start', [$periodStart, $periodEnd])
            ->where('rejected', false)
            ->with('user')
            ->get();

        $entriesByUser = $entries->groupBy('user_id');

        // Bulk-load existing accounts for the current (and, if needed, previous)
        // period once instead of issuing two lookup queries per family below.
        $currentAccounts = PflichtstundenFamilyAccount::query()
            ->where('period_year', $periodYear)
            ->get()
            ->keyBy('family_key');

        $previousAccounts = $this->settings->konto_uebertrag_aktiv
            ? PflichtstundenFamilyAccount::query()
                ->where('period_year', $periodYear - 1)
                ->get()
                ->keyBy('family_key')
            : collect();

        $accountsToUpsert = [];

        $summaries = $groups->map(function (array $group) use ($periodYear, $rules, $entriesByUser, $currentAccounts, $previousAccounts, $protectExisting, &$accountsToUpsert) {
            $familyEntries = collect();
            foreach ($group['user_ids'] as $userId) {
                $familyEntries = $familyEntries->merge($entriesByUser->get($userId, collect()));
            }
            $familyEntries = $familyEntries->sortBy('start')->values();

            $allMinutes = $familyEntries->sum(fn (Pflichtstunde $entry) => $this->entryMinutes($entry));
            $approvedMinutes = $familyEntries
                ->where('approved', true)
                ->sum(fn (Pflichtstunde $entry) => $this->entryMinutes($entry));

            $rule = $this->lookupForGroup($rules, $group);
            $mode = $rule?->mode ?? 'standard';
            $requiredMinutes = $this->resolveRequiredMinutesForGroup($mode, $rule?->custom_required_hours, $group);
            $requiredHours = round($requiredMinutes / 60, 2);
            $hourlyRate = $this->resolveHourlyRate($mode);

            $openingBalance = $this->resolveOpeningBalanceMinutes($group, $currentAccounts, $previousAccounts);
            $creditedMinutes = $openingBalance + $approvedMinutes;
            $closingBalance = $creditedMinutes - $requiredMinutes;
            $openMinutes = max(0, -$closingBalance);
            $beitrag = round(($openMinutes / 60) * $hourlyRate, 2);
            $percent = $requiredMinutes > 0
                ? round(min(100, max(0, ($creditedMinutes / $requiredMinutes) * 100)), 2)
                : 100.0;
            $expectedPercent = $requiredMinutes > 0
                ? round(min(100, max(0, (($openingBalance + $allMinutes) / $requiredMinutes) * 100)), 2)
                : 100.0;

            $carryoverMinutes = 0;
            if ($this->settings->konto_uebertrag_aktiv) {
                $carryoverMinutes = max(0, $closingBalance);
                if ($this->settings->konto_uebertrag_max_stunden !== null) {
                    $carryoverMinutes = min($carryoverMinutes, (int) round($this->settings->konto_uebertrag_max_stunden * 60));
                }
            }

            if (! $protectExisting || $this->lookupForGroup($currentAccounts, $group) === null) {
                $accountsToUpsert[] = [
                    'family_key' => $group['family_key'],
                    'period_year' => $periodYear,
                    'opening_balance_minutes' => $openingBalance,
                    'earned_minutes' => $approvedMinutes,
                    'required_minutes' => $requiredMinutes,
                    'closing_balance_minutes' => $closingBalance,
                    'carried_to_next_minutes' => $carryoverMinutes,
                    'carryover_applied' => $this->settings->konto_uebertrag_aktiv,
                    'last_calculated_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            return [
                'family_key' => $group['family_key'],
                'family_name' => $group['family_name'],
                'user' => $group['user'],
                'partner' => $group['partner'],
                'members' => $group['members'],
                'user_ids' => $group['user_ids'],
                'child_ids' => $group['child_ids'],
                'rule_mode' => $mode,
                'rule_reason' => $rule?->reason,
                'custom_required_hours' => $rule?->custom_required_hours,
                'required_hours' => $requiredHours,
                'hourly_rate' => $hourlyRate,
                'required_minutes' => $requiredMinutes,
                'opening_balance_minutes' => $openingBalance,
                'totalMinutes' => $approvedMinutes,
                'allMinutes' => $allMinutes,
                'openMinutes' => $openMinutes,
                'closing_balance_minutes' => $closingBalance,
                'carryover_preview_minutes' => $carryoverMinutes,
                'beitrag' => $beitrag,
                'percent' => $percent,
                'expected_percent' => $expectedPercent,
                'entries' => $familyEntries,
            ];
        });

        if ($persistAccounts && $accountsToUpsert !== []) {
            // Single bulk upsert instead of one updateOrCreate() call (select + write)
            // per family, which previously caused 2×N queries on every dashboard load.
            PflichtstundenFamilyAccount::query()->upsert(
                $accountsToUpsert,
                ['family_key', 'period_year'],
                [
                    'opening_balance_minutes',
                    'earned_minutes',
                    'required_minutes',
                    'closing_balance_minutes',
                    'carried_to_next_minutes',
                    'carryover_applied',
                    'last_calculated_at',
                    'updated_at',
                ]
            );
        }

        return $summaries->values();
    }

    /**
     * @param array<string,mixed> $summary
     */
    public function modeLabel(array $summary): string
    {
        return match ($summary['rule_mode']) {
            'reduced' => 'Ermäßigt',
            'custom' => 'Individuell',
            default => 'Standard',
        };
    }

    public function upsertFamilyRule(
        string $familyKey,
        int $periodYear,
        string $mode,
        ?float $customRequiredHours,
        ?string $reason,
        ?int $changedBy
    ): PflichtstundenFamilyRule {
        $existing = PflichtstundenFamilyRule::query()
            ->where('family_key', $familyKey)
            ->where('period_year', $periodYear)
            ->first();

        $rule = PflichtstundenFamilyRule::updateOrCreate(
            [
                'family_key' => $familyKey,
                'period_year' => $periodYear,
            ],
            [
                'mode' => $mode,
                'custom_required_hours' => $mode === 'custom' ? $customRequiredHours : null,
                'reason' => $reason,
                'updated_by' => $changedBy,
                'created_by' => $existing?->created_by ?? $changedBy,
            ]
        );

        if (! $existing || $existing->mode !== $rule->mode || (float) $existing->custom_required_hours !== (float) $rule->custom_required_hours || $existing->reason !== $rule->reason) {
            PflichtstundenFamilyRuleHistory::create([
                'pflichtstunden_family_rule_id' => $rule->id,
                'family_key' => $rule->family_key,
                'period_year' => $rule->period_year,
                'from_mode' => $existing?->mode,
                'to_mode' => $rule->mode,
                'from_custom_required_hours' => $existing?->custom_required_hours,
                'to_custom_required_hours' => $rule->custom_required_hours,
                'reason' => $rule->reason,
                'changed_by' => $changedBy,
            ]);
        }

        return $rule;
    }

    public function resolveRequiredHours(string $mode, ?float $customRequiredHours = null): float
    {
        return match ($mode) {
            'reduced' => (float) ($this->settings->pflichtstunden_anzahl_ermaessigt ?? $this->settings->pflichtstunden_anzahl),
            'custom' => (float) ($customRequiredHours ?? $this->settings->pflichtstunden_anzahl),
            default => (float) $this->settings->pflichtstunden_anzahl,
        };
    }

    /**
     * Soll-Minuten einer Einheit. Standard/ermäßigt richten sich nach der
     * Berechnungsgrundlage (Familie/Kind, geteilte Kinder) aus den Settings,
     * „individuell“ ist ein fester Wert für die Einheit.
     *
     * @param  array<string, mixed>  $group
     */
    public function resolveRequiredMinutesForGroup(string $mode, ?float $customRequiredHours, array $group): int
    {
        if ($mode === 'custom') {
            return (int) round($this->resolveRequiredHours('custom', $customRequiredHours) * 60);
        }

        $baseRequired = (int) $group['base_required_minutes'];
        if ($mode !== 'reduced') {
            return $baseRequired;
        }

        $standardHours = (float) $this->settings->pflichtstunden_anzahl;
        if ($standardHours <= 0) {
            return (int) round($this->resolveRequiredHours('reduced') * 60);
        }

        // Ermäßigung skaliert das Basis-Soll (z. B. pro Kind) im Verhältnis ermäßigt/standard
        return (int) round($baseRequired * $this->resolveRequiredHours('reduced') / $standardHours);
    }

    public function resolveHourlyRate(string $mode): float
    {
        return $mode === 'reduced'
            ? (float) ($this->settings->pflichtstunden_betrag_ermaessigt ?? $this->settings->pflichtstunden_betrag)
            : (float) $this->settings->pflichtstunden_betrag;
    }

    /**
     * @param  array<string, mixed>  $group
     */
    private function resolveOpeningBalanceMinutes(array $group, Collection $currentAccounts, Collection $previousAccounts): int
    {
        $existing = $this->lookupForGroup($currentAccounts, $group);

        if ($existing) {
            return (int) $existing->opening_balance_minutes;
        }

        if (! $this->settings->konto_uebertrag_aktiv) {
            return 0;
        }

        $previous = $this->lookupForGroup($previousAccounts, $group);

        if (! $previous) {
            return 0;
        }

        $opening = (int) max(0, $previous->carried_to_next_minutes ?: $previous->closing_balance_minutes);
        if ($this->settings->konto_uebertrag_max_stunden !== null) {
            $opening = min($opening, (int) round($this->settings->konto_uebertrag_max_stunden * 60));
        }

        return $opening;
    }

    /**
     * Rechnet alle Perioden ab, in denen der Nutzer Pflichtstunden erfasst hat,
     * und schreibt die Familienkonten final fest (inkl. bereits gelöschter
     * Partner). Dadurch bleiben abgerechnete Beträge und Salden dauerhaft in
     * pflichtstunden_family_accounts erhalten, auch wenn der Nutzer und seine
     * Rohdaten (Pflichtstunden-Einträge) danach endgültig gelöscht werden.
     *
     * Muss vor User::forceDelete() aufgerufen werden, da der Fremdschlüssel
     * auf pflichtstunden.user_id per Cascade löscht.
     */
    public function sealHistoryForUser(User $user): void
    {
        $firstStart = Pflichtstunde::withoutGlobalScope('aktuellerZeitraum')
            ->withTrashed()
            ->where('user_id', $user->id)
            ->min('start');

        if (! $firstStart) {
            return;
        }

        $firstYear = $this->resolvePeriodStartYearForDate(Carbon::parse($firstStart));

        [$currentPeriodStart] = $this->resolvePeriod(null);
        $lastYear = $this->periodStartYear($currentPeriodStart);

        for ($year = $firstYear; $year <= $lastYear; $year++) {
            [$periodStart, $periodEnd] = $this->resolvePeriod($year);
            $this->buildFamilySummaries($periodStart, $periodEnd, true, true, true);
        }
    }

    private function entryMinutes(Pflichtstunde $entry): int
    {
        if (! $entry->start || ! $entry->end) {
            return 0;
        }

        return (int) $entry->start->diffInMinutes($entry->end);
    }
}
