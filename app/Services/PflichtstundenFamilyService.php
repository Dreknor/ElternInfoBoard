<?php

namespace App\Services;

use App\Model\Pflichtstunde;
use App\Model\PflichtstundenFamilyAccount;
use App\Model\PflichtstundenFamilyRule;
use App\Model\PflichtstundenFamilyRuleHistory;
use App\Model\User;
use App\Settings\PflichtstundenSetting;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PflichtstundenFamilyService
{
    public function __construct(private readonly PflichtstundenSetting $settings) {}

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

    public function periodStartYear(Carbon $periodStart): int
    {
        return (int) $periodStart->year;
    }

    public function determineFamilyKey(User $user, ?User $partner = null): string
    {
        $ids = [$user->id];
        if ($partner) {
            $ids[] = $partner->id;
        }

        sort($ids);

        return (string) $ids[0];
    }

    /**
     * @return Collection<int, array{
     *   family_key:string,
     *   user:User,
     *   partner:?User,
     *   user_ids:array<int,int>,
     *   family_name:string
     * }>
     */
    public function getFamilyGroups(): Collection
    {
        $users = User::query()
            ->permission('view Pflichtstunden')
            ->orderBy('id')
            ->get()
            ->keyBy('id');

        $processed = collect();
        $groups = collect();

        foreach ($users as $user) {
            if ($processed->contains($user->id)) {
                continue;
            }

            $partner = null;
            if ($user->sorg2 && $users->has($user->sorg2)) {
                $partner = $users->get($user->sorg2);
                $processed->push($partner->id);
            }

            $familyKey = $this->determineFamilyKey($user, $partner);
            $userIds = [$user->id];
            if ($partner) {
                $userIds[] = $partner->id;
            }

            $familyName = $user->name;
            if ($partner) {
                $familyName .= ' / '.$partner->name;
            }

            $groups->push([
                'family_key' => $familyKey,
                'user' => $user,
                'partner' => $partner,
                'user_ids' => $userIds,
                'family_name' => $familyName,
            ]);

            $processed->push($user->id);
        }

        return $groups;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function buildFamilySummaries(Carbon $periodStart, Carbon $periodEnd, bool $persistAccounts = true): Collection
    {
        $periodYear = $this->periodStartYear($periodStart);
        $groups = $this->getFamilyGroups();
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

        $summaries = $groups->map(function (array $group) use ($periodYear, $rules, $entriesByUser, $currentAccounts, $previousAccounts, &$accountsToUpsert) {
            $familyEntries = collect();
            foreach ($group['user_ids'] as $userId) {
                $familyEntries = $familyEntries->merge($entriesByUser->get($userId, collect()));
            }
            $familyEntries = $familyEntries->sortBy('start')->values();

            $allMinutes = $familyEntries->sum(fn (Pflichtstunde $entry) => $this->entryMinutes($entry));
            $approvedMinutes = $familyEntries
                ->where('approved', true)
                ->sum(fn (Pflichtstunde $entry) => $this->entryMinutes($entry));

            $rule = $rules->get($group['family_key']);
            $mode = $rule?->mode ?? 'standard';
            $requiredHours = $this->resolveRequiredHours($mode, $rule?->custom_required_hours);
            $hourlyRate = $this->resolveHourlyRate($mode);
            $requiredMinutes = (int) round($requiredHours * 60);

            $openingBalance = $this->resolveOpeningBalanceMinutes($group['family_key'], $currentAccounts, $previousAccounts);
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

            return [
                'family_key' => $group['family_key'],
                'family_name' => $group['family_name'],
                'user' => $group['user'],
                'partner' => $group['partner'],
                'user_ids' => $group['user_ids'],
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

    public function resolveHourlyRate(string $mode): float
    {
        return $mode === 'reduced'
            ? (float) ($this->settings->pflichtstunden_betrag_ermaessigt ?? $this->settings->pflichtstunden_betrag)
            : (float) $this->settings->pflichtstunden_betrag;
    }

    private function resolveOpeningBalanceMinutes(string $familyKey, Collection $currentAccounts, Collection $previousAccounts): int
    {
        $existing = $currentAccounts->get($familyKey);

        if ($existing) {
            return (int) $existing->opening_balance_minutes;
        }

        if (! $this->settings->konto_uebertrag_aktiv) {
            return 0;
        }

        $previous = $previousAccounts->get($familyKey);

        if (! $previous) {
            return 0;
        }

        $opening = (int) max(0, $previous->carried_to_next_minutes ?: $previous->closing_balance_minutes);
        if ($this->settings->konto_uebertrag_max_stunden !== null) {
            $opening = min($opening, (int) round($this->settings->konto_uebertrag_max_stunden * 60));
        }

        return $opening;
    }

    private function entryMinutes(Pflichtstunde $entry): int
    {
        if (! $entry->start || ! $entry->end) {
            return 0;
        }

        return (int) $entry->start->diffInMinutes($entry->end);
    }
}
