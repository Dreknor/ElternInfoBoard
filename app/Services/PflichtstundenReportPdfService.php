<?php

namespace App\Services;

use App\Model\Pflichtstunde;
use App\Model\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PflichtstundenReportPdfService
{
    public function __construct(private readonly PflichtstundenFamilyService $familyService)
    {
    }

    public function buildReport(Carbon $periodStart, Carbon $periodEnd, string $sort = 'family_name', bool $anonymized = false): array
    {
        $entries = Pflichtstunde::withoutGlobalScope('aktuellerZeitraum')
            ->whereBetween('start', [$periodStart->copy()->startOfDay(), $periodEnd->copy()->endOfDay()])
            ->with(['user'])
            ->orderBy('start')
            ->get();

        $approvedEntries = $entries->filter(fn (Pflichtstunde $entry) => $entry->approved && ! $entry->rejected);
        $pendingEntries = $entries->filter(fn (Pflichtstunde $entry) => ! $entry->approved && ! $entry->rejected);
        $rejectedEntries = $entries->filter(fn (Pflichtstunde $entry) => $entry->rejected);

        $familyRows = $this->buildFamilyRows($periodStart, $periodEnd, $anonymized, $sort);

        $totalApprovedMinutes = $approvedEntries->sum(fn (Pflichtstunde $entry) => $this->durationMinutes($entry));
        $pendingEntriesCount = $pendingEntries->count();
        $highRiskEntries = $entries
            ->filter(fn (Pflichtstunde $entry) =>
                $entry->approved
                && ! $entry->rejected
                && ! $entry->trashed()
                && $this->durationMinutes($entry) > 12 * 60
            )
            ->values();

        $areas = $this->collectAreaDistribution($approvedEntries);
        $calendarDays = $this->collectWeekdayDistribution($approvedEntries);
        $dayTimes = $this->collectTimeDistribution($approvedEntries);
        $monthly = $this->collectMonthlyDistribution($periodStart, $periodEnd, $approvedEntries);
        $processMetrics = $this->collectProcessMetrics($entries);
        $topHelpers = $this->buildTopHelpers($familyRows);

        return [
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'sort' => $sort,
            'anonymized' => $anonymized,
            'summary' => [
                'total_approved_minutes' => $totalApprovedMinutes,
                'total_approved_hours' => round($totalApprovedMinutes / 60, 2),
                'pending_entries_count' => $pendingEntriesCount,
                'rejected_entries_count' => $rejectedEntries->count(),
            ],
            'error_entries' => $highRiskEntries->map(function (Pflichtstunde $entry) {
                return [
                    'id' => $entry->id,
                    'family_name' => $entry->user?->name ?? 'Unbekannt',
                    'duration_hours' => round($this->durationMinutes($entry) / 60, 2),
                    'start' => $entry->start,
                    'end' => $entry->end,
                    'bereich' => $entry->bereich ?? 'Ohne Zuordnung',
                    'description' => $entry->description,
                ];
            })->values(),
            'family_rows' => $familyRows,
            'top_helpers' => $topHelpers,
            'areas' => $areas,
            'weekday_distribution' => $calendarDays,
            'time_distribution' => $dayTimes,
            'monthly_distribution' => $monthly,
            'process_metrics' => $processMetrics,
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function buildFamilyRows(Carbon $periodStart, Carbon $periodEnd, bool $anonymized, string $sort): Collection
    {
        $summaries = $this->familyService->buildFamilySummaries($periodStart, $periodEnd, false)
            ->map(function (array $summary, int $index) use ($anonymized) {
                $displayName = $anonymized ? 'Familie '.($index + 1) : $summary['family_name'];
                $approvedMinutes = (int) ($summary['totalMinutes'] ?? 0);
                $allMinutes = (int) ($summary['allMinutes'] ?? 0);
                $pendingMinutes = max(0, $allMinutes - $approvedMinutes);
                $differenceMinutes = $approvedMinutes - (int) ($summary['required_minutes'] ?? 0);

                return [
                    'family_key' => $summary['family_key'],
                    'family_name' => $displayName,
                    'raw_family_name' => $summary['family_name'],
                    'required_hours' => round(((int) ($summary['required_minutes'] ?? 0)) / 60, 2),
                    'approved_hours' => round($approvedMinutes / 60, 2),
                    'pending_hours' => round($pendingMinutes / 60, 2),
                    'difference_hours' => round($differenceMinutes / 60, 2),
                    'difference_minutes' => $differenceMinutes,
                    'required_minutes' => (int) ($summary['required_minutes'] ?? 0),
                    'approved_minutes' => $approvedMinutes,
                    'pending_minutes' => $pendingMinutes,
                    'open_minutes' => (int) ($summary['openMinutes'] ?? 0),
                    'percent' => (float) ($summary['percent'] ?? 0),
                    'sort_key' => $this->familySortKey($summary['family_name']),
                ];
            });

        return match ($sort) {
            'highest_debt' => $summaries->sortByDesc('open_minutes')->values(),
            default => $summaries->sortBy('sort_key')->values(),
        };
    }

    protected function buildTopHelpers(Collection $familyRows): Collection
    {
        return $familyRows
            ->filter(fn (array $row) => $row['approved_minutes'] >= $row['required_minutes'])
            ->sortByDesc('approved_minutes')
            ->take(5)
            ->values()
            ->map(fn (array $row) => [
                'family_name' => $row['family_name'],
                'approved_hours' => $row['approved_hours'],
                'required_hours' => $row['required_hours'],
                'extra_hours' => round(($row['approved_minutes'] - $row['required_minutes']) / 60, 2),
            ]);
    }

    /**
     * @param Collection<int, Pflichtstunde> $entries
     * @return array<string, float>
     */
    protected function collectAreaDistribution(Collection $entries): array
    {
        $data = [
            'Grundschule' => 0.0,
            'Oberschule' => 0.0,
            'Sonstiges' => 0.0,
            'Ohne Zuordnung' => 0.0,
        ];

        foreach ($entries as $entry) {
            if (! $entry->approved || $entry->rejected) {
                continue;
            }

            $area = $this->normalizeArea($entry->bereich ?? null);
            $data[$area] = ($data[$area] ?? 0.0) + ($this->durationMinutes($entry) / 60);
        }

        return $data;
    }

    /**
     * @param Collection<int, Pflichtstunde> $entries
     * @return array<string, float>
     */
    protected function collectWeekdayDistribution(Collection $entries): array
    {
        $days = ['Mo' => 0.0, 'Di' => 0.0, 'Mi' => 0.0, 'Do' => 0.0, 'Fr' => 0.0, 'Sa' => 0.0, 'So' => 0.0];

        foreach ($entries as $entry) {
            $key = match ((int) $entry->start->dayOfWeekIso) {
                1 => 'Mo',
                2 => 'Di',
                3 => 'Mi',
                4 => 'Do',
                5 => 'Fr',
                6 => 'Sa',
                default => 'So',
            };

            $days[$key] += $this->durationMinutes($entry) / 60;
        }

        return $days;
    }

    /**
     * @param Collection<int, Pflichtstunde> $entries
     * @return array<string, float>
     */
    protected function collectTimeDistribution(Collection $entries): array
    {
        $bands = [
            'Vormittag (06:00-12:00)' => 0.0,
            'Nachmittag (12:00-18:00)' => 0.0,
            'Abend/Nacht (ab 18:00)' => 0.0,
        ];

        foreach ($entries as $entry) {
            $hour = (int) $entry->start->format('H');
            $band = match (true) {
                $hour >= 6 && $hour < 12 => 'Vormittag (06:00-12:00)',
                $hour >= 12 && $hour < 18 => 'Nachmittag (12:00-18:00)',
                default => 'Abend/Nacht (ab 18:00)',
            };

            $bands[$band] += $this->durationMinutes($entry) / 60;
        }

        return $bands;
    }

    /**
     * @param Collection<int, Pflichtstunde> $entries
     * @return array<string, float>
     */
    protected function collectMonthlyDistribution(Carbon $periodStart, Carbon $periodEnd, Collection $entries): array
    {
        $monthCursor = $periodStart->copy()->startOfMonth();
        $endMonth = $periodEnd->copy()->endOfMonth();
        $result = [];

        while ($monthCursor->lte($endMonth)) {
            $key = $monthCursor->format('Y-m');
            $label = $monthCursor->translatedFormat('M Y');
            $result[$key] = [
                'label' => $label,
                'hours' => 0.0,
            ];
            $monthCursor->addMonth();
        }

        foreach ($entries as $entry) {
            $key = $entry->start->format('Y-m');
            if (! isset($result[$key])) {
                continue;
            }

            $result[$key]['hours'] += $this->durationMinutes($entry) / 60;
        }

        return $result;
    }

    /**
     * @param Collection<int, Pflichtstunde> $entries
     * @return array<string, mixed>
     */
    protected function collectProcessMetrics(Collection $entries): array
    {
        $approvedEntries = $entries->filter(fn (Pflichtstunde $entry) => $entry->approved && $entry->approved_at && $entry->created_at);
        $durations = $approvedEntries
            ->map(function (Pflichtstunde $entry) {
                if ($entry->approved_at->lessThan($entry->created_at)) {
                    return 0.0;
                }

                return $entry->created_at->diffInDays($entry->approved_at, false) + ($entry->created_at->diffInHours($entry->approved_at) % 24) / 24;
            })
            ->filter(fn ($value) => $value > 0)
            ->values();

        $workload = [];
        foreach ($entries as $entry) {
            if ($entry->approved_by) {
                $user = User::find($entry->approved_by);
                $key = $user?->name ?? 'Unbekannt';
                $workload[$key]['approved'] = ($workload[$key]['approved'] ?? 0) + 1;
            }

            if ($entry->rejected_by) {
                $user = User::find($entry->rejected_by);
                $key = $user?->name ?? 'Unbekannt';
                $workload[$key]['rejected'] = ($workload[$key]['rejected'] ?? 0) + 1;
            }
        }

        $workload = collect($workload);

        $rejectionReasons = $entries
            ->filter(fn (Pflichtstunde $entry) => $entry->rejected && ! empty(trim((string) ($entry->rejection_reason ?? ''))))
            ->map(fn (Pflichtstunde $entry) => trim((string) $entry->rejection_reason))
            ->countBy()
            ->sortDesc()
            ->map(fn ($count, $reason) => ['reason' => $reason, 'count' => $count])
            ->values();

        $mostCommonReason = $rejectionReasons->first() ?? ['reason' => 'Keine Angabe', 'count' => 0];

        return [
            'avg_approval_days' => $durations->isEmpty() ? 0.0 : round((float) $durations->avg(), 2),
            'workload' => $workload
                ->map(fn (array $item, string $name) => [
                    'admin_name' => $name,
                    'approved' => (int) ($item['approved'] ?? 0),
                    'rejected' => (int) ($item['rejected'] ?? 0),
                    'total' => (int) (($item['approved'] ?? 0) + ($item['rejected'] ?? 0)),
                ])
                ->sortByDesc('total')
                ->values(),
            'rejection_count' => $entries->filter(fn (Pflichtstunde $entry) => $entry->rejected)->count(),
            'rejection_reasons' => $rejectionReasons,
            'most_common_rejection_reason' => $mostCommonReason,
        ];
    }

    protected function normalizeArea(?string $bereich): string
    {
        if ($bereich === null || trim($bereich) === '') {
            return 'Ohne Zuordnung';
        }

        $normalized = strtolower(trim($bereich));

        if (str_contains($normalized, 'grund')) {
            return 'Grundschule';
        }

        if (str_contains($normalized, 'ober')) {
            return 'Oberschule';
        }

        return 'Sonstiges';
    }

    protected function durationMinutes(Pflichtstunde $entry): int
    {
        if (! $entry->start || ! $entry->end) {
            return 0;
        }

        return (int) $entry->start->diffInMinutes($entry->end);
    }

    protected function familySortKey(string $familyName): string
    {
        $clean = trim($familyName);
        $beforeSlash = explode('/', $clean)[0];
        $pieces = preg_split('/\s+/', trim($beforeSlash));

        if (empty($pieces)) {
            return strtolower($clean);
        }

        return strtolower(end($pieces));
    }
}
