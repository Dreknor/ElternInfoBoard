<?php

namespace App\Http\Controllers;

use App\Exports\PflichtstundenExport;
use App\Http\Requests\CreatePflichtstundeRequest;
use App\Http\Requests\UpdatePflichtstundeRequest;
use App\Model\Pflichtstunde;
use App\Model\PflichtstundenFamilyRuleHistory;
use App\Services\PflichtstundenFamilyService;
use App\Services\PflichtstundenReportPdfService;
use App\Settings\PflichtstundenSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Maatwebsite\Excel\Facades\Excel;

class PflichtstundeController extends Controller implements HasMiddleware
{
    protected PflichtstundenSetting $pflichtstunden_settings;

    protected PflichtstundenFamilyService $familyService;

    protected PflichtstundenReportPdfService $reportPdfService;

    public function __construct()
    {
        $this->pflichtstunden_settings = new PflichtstundenSetting;
        $this->familyService = new PflichtstundenFamilyService($this->pflichtstunden_settings);
        $this->reportPdfService = new PflichtstundenReportPdfService($this->familyService);
    }

    public static function middleware(): array
    {
        return [
            'auth',
        ];
    }

    public function index(Request $request)
    {
        if (! auth()->user()->can('view Pflichtstunden')) {
            return redirect(url('/'))->with('error', 'Berechtigung fehlt');
        }

        $selectedYear = $request->filled('year') ? (int) $request->get('year') : null;
        [$periodStart, $periodEnd] = $this->familyService->resolvePeriod($selectedYear);

        $currentUser = auth()->user();
        $familyUserIds = array_filter([$currentUser->id, $currentUser->sorg2]);

        $pflichtstunden = Pflichtstunde::withoutGlobalScope('aktuellerZeitraum')
            ->whereIn('user_id', $familyUserIds)
            ->whereBetween('start', [$periodStart, $periodEnd])
            ->orderBy('start', 'desc')
            ->get();

        $familySummaries = $this->familyService->buildFamilySummaries($periodStart, $periodEnd, true);
        $parentStats = $this->calculateParentStatsFromSummaries($familySummaries, $currentUser->id);
        $currentFamilySummary = $familySummaries->first(fn (array $summary) => in_array($currentUser->id, $summary['user_ids']));

        return view('pflichtstunden.index', [
            'pflichtstunden' => $pflichtstunden,
            'pflichtstunden_settings' => $this->pflichtstunden_settings,
            'parent_stats' => $parentStats,
            'currentFamilySummary' => $currentFamilySummary,
            'selectedYear' => $selectedYear,
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
            'availableYears' => $this->availablePeriodYears(),
        ]);
    }

    public function store(CreatePflichtstundeRequest $request)
    {
        $data = $request->validated();

        if (! isset($data['user_id']) || ! auth()->user()->can('edit Pflichtstunden')) {
            $data['user_id'] = auth()->id();
        }

        Pflichtstunde::create($data);

        return redirect()->back()->with('success', 'Pflichtstunde angelegt');
    }

    public function verwaltungIndex(Request $request)
    {
        if (! auth()->user()->can('edit Pflichtstunden')) {
            return redirect(url('/'))->with('error', 'Berechtigung fehlt');
        }

        $selectedYear = $request->filled('year') ? (int) $request->get('year') : null;
        [$periodStart, $periodEnd] = $this->familyService->resolvePeriod($selectedYear);
        $periodYear = $this->familyService->periodStartYear($periodStart);

        $pflichtstunden = Pflichtstunde::query()
            ->where('approved', false)
            ->where('rejected', false)
            ->orderBy('end', 'desc')
            ->get();

        $groupedUsers = $this->familyService->buildFamilySummaries($periodStart, $periodEnd, true);
        $groupedUsers = $groupedUsers->map(function (array $group) use ($periodStart, $periodEnd) {
            $entries = Pflichtstunde::withoutGlobalScope('aktuellerZeitraum')
                ->whereBetween('start', [$periodStart->copy()->startOfDay(), $periodEnd->copy()->endOfDay()])
                ->whereIn('user_id', $group['user_ids'])
                ->where('rejected', false)
                ->with('user')
                ->orderBy('start')
                ->get()
                ->map(function (Pflichtstunde $entry) {
                    return [
                        'id' => $entry->id,
                        'user' => $entry->user?->name ?? 'Unbekannt',
                        'start' => $entry->start?->format('d.m.Y H:i'),
                        'end' => $entry->end?->format('d.m.Y H:i'),
                        'duration' => $entry->start && $entry->end
                            ? ($entry->start->diffInMinutes($entry->end) >= 60
                                ? floor($entry->start->diffInMinutes($entry->end) / 60).'h '.($entry->start->diffInMinutes($entry->end) % 60).'m'
                                : $entry->start->diffInMinutes($entry->end).'m')
                            : '0m',
                        'description' => $entry->description ?? '-',
                        'bereich' => $entry->bereich ?: 'Ohne Bereich',
                        'approved' => (bool) $entry->approved,
                    ];
                })
                ->values();

            return array_merge($group, ['entries' => $entries]);
        });

        $stats = [
            'totalFamilies' => $groupedUsers->count(),
            'completed' => $groupedUsers->where('percent', '>=', 100)->count(),
            'partial' => $groupedUsers->whereBetween('percent', [0.01, 99.99])->count(),
            'notStarted' => $groupedUsers->where('percent', '<=', 0)->count(),
            'totalHoursCompleted' => round($groupedUsers->sum('totalMinutes') / 60, 2),
            'totalHoursMissing' => round($groupedUsers->sum('openMinutes') / 60, 2),
            'totalHoursRequired' => round($groupedUsers->sum('required_minutes') / 60, 2),
            'totalBeitrag' => round($groupedUsers->sum('beitrag'), 2),
            'avgPercent' => round((float) $groupedUsers->avg('percent'), 2),
            'expectedAvgPercent' => round((float) $groupedUsers->avg('expected_percent'), 2),
        ];

        [$overlappingIds, $overlapGroups, $entryGroupMap] = $this->buildOverlapGroups($groupedUsers);

        $asOfDate = $request->filled('as_of_date') ? \Carbon\Carbon::parse($request->input('as_of_date')) : now();
        $closingPreview = $groupedUsers
            ->map(function (array $group) use ($asOfDate, $periodStart, $periodEnd) {
                $targetDate = $asOfDate->copy();
                if ($targetDate->lt($periodStart)) {
                    $targetDate = $periodStart->copy();
                }
                if ($targetDate->gt($periodEnd)) {
                    $targetDate = $periodEnd->copy();
                }

                $referenceDate = now()->lt($periodEnd) ? now() : $periodEnd;
                $elapsedDays = max(1, $periodStart->diffInDays($referenceDate) + 1);
                $avgApprovedPerDay = $group['totalMinutes'] / $elapsedDays;
                $targetDays = max(1, $periodStart->diffInDays($targetDate) + 1);
                $projectedApproved = (int) round($avgApprovedPerDay * $targetDays);
                $projectedClosing = (int) $group['opening_balance_minutes'] + $projectedApproved - (int) $group['required_minutes'];

                return [
                    'family_name' => $group['family_name'],
                    'mode' => $this->familyService->modeLabel($group),
                    'current_closing_minutes' => $group['closing_balance_minutes'],
                    'projected_closing_minutes' => $projectedClosing,
                ];
            })
            ->sortBy('family_name')
            ->values();

        $familyNameMap = $groupedUsers->mapWithKeys(function (array $group) {
            return [
                (string) $group['family_key'] => $group['family_name'],
            ];
        })->all();

        $ruleHistoryEntries = PflichtstundenFamilyRuleHistory::query()
            ->with('changedBy')
            ->where('period_year', $periodYear)
            ->latest()
            ->limit(20)
            ->get();

        $ruleHistoryEntries = $ruleHistoryEntries->map(function (PflichtstundenFamilyRuleHistory $entry) use ($familyNameMap) {
            return [
                'id' => $entry->id,
                'family_name' => $familyNameMap[$entry->family_key] ?? $entry->family_key,
                'from_mode' => $entry->from_mode,
                'to_mode' => $entry->to_mode,
                'from_custom_required_hours' => $entry->from_custom_required_hours,
                'to_custom_required_hours' => $entry->to_custom_required_hours,
                'reason' => $entry->reason,
                'changed_by_name' => $entry->changedBy?->name ?? 'Unbekannt',
                'created_at' => $entry->created_at,
            ];
        });

        return view('pflichtstunden.indexVerwaltung', [
            'pflichtstunden' => $pflichtstunden,
            'pflichtstunden_settings' => $this->pflichtstunden_settings,
            'groupedUsers' => $groupedUsers,
            'allGroupedUsers' => $groupedUsers,
            'stats' => $stats,
            'overlappingIds' => $overlappingIds,
            'overlapGroups' => $overlapGroups,
            'entryGroupMap' => $entryGroupMap,
            'selectedYear' => $selectedYear,
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
            'periodYear' => $periodYear,
            'closingPreview' => $closingPreview,
            'asOfDate' => $asOfDate,
            'ruleHistoryEntries' => $ruleHistoryEntries,
            'availableYears' => $this->availablePeriodYears(),
        ]);
    }

    public function updateFamilyRule(Request $request)
    {
        if (! auth()->user()->can('edit Pflichtstunden')) {
            return redirect(url('/'))->with('error', 'Berechtigung fehlt');
        }

        $validated = $request->validate([
            'family_key' => 'required|string|max:64',
            'period_year' => 'required|integer|min:2000|max:2100',
            'mode' => 'required|string|in:standard,reduced,custom',
            'custom_required_hours' => 'nullable|numeric|min:0|max:999.99',
            'reason' => 'nullable|string|max:1000',
        ]);

        if ($validated['mode'] === 'custom' && $validated['custom_required_hours'] === null) {
            return redirect()->back()->with('error', 'Für den Modus "Individuell" muss eine Stundenanzahl angegeben werden.');
        }

        if ($validated['mode'] === 'custom' && empty(trim((string) ($validated['reason'] ?? '')))) {
            return redirect()->back()->with('error', 'Für individuelle Sollstunden ist eine Begründung erforderlich.');
        }

        $this->familyService->upsertFamilyRule(
            $validated['family_key'],
            (int) $validated['period_year'],
            $validated['mode'],
            isset($validated['custom_required_hours']) ? (float) $validated['custom_required_hours'] : null,
            $validated['reason'] ?? null,
            auth()->id()
        );

        return redirect()->back()->with('success', 'Familienregel gespeichert');
    }

    public function bulkUpdateFamilyRule(Request $request)
    {
        if (! auth()->user()->can('edit Pflichtstunden')) {
            return redirect(url('/'))->with('error', 'Berechtigung fehlt');
        }

        $validated = $request->validate([
            'period_year' => 'required|integer|min:2000|max:2100',
            'mode' => 'required|string|in:standard,reduced,custom',
            'custom_required_hours' => 'nullable|numeric|min:0|max:999.99',
            'reason' => 'nullable|string|max:1000',
            'family_keys' => 'required|array|min:1',
            'family_keys.*' => 'string|max:64',
        ]);

        if ($validated['mode'] === 'custom' && $validated['custom_required_hours'] === null) {
            return redirect()->back()->with('error', 'Für den Modus "Individuell" muss eine Stundenanzahl angegeben werden.');
        }

        if ($validated['mode'] === 'custom' && empty(trim((string) ($validated['reason'] ?? '')))) {
            return redirect()->back()->with('error', 'Für individuelle Sollstunden ist eine Begründung erforderlich.');
        }

        foreach ($validated['family_keys'] as $familyKey) {
            $this->familyService->upsertFamilyRule(
                $familyKey,
                (int) $validated['period_year'],
                $validated['mode'],
                isset($validated['custom_required_hours']) ? (float) $validated['custom_required_hours'] : null,
                $validated['reason'] ?? null,
                auth()->id()
            );
        }

        return redirect()->back()->with('success', count($validated['family_keys']).' Familienregeln aktualisiert');
    }

    public function approve(Request $request, Pflichtstunde $pflichtstunde)
    {
        if (! auth()->user()->can('edit Pflichtstunden')) {
            return redirect(url('/'))->with('error', 'Berechtigung fehlt');
        }

        $currentId = $pflichtstunde->id;

        $pflichtstunde->approved = true;
        $pflichtstunde->approved_at = now();
        $pflichtstunde->approved_by = auth()->id();
        $pflichtstunde->rejected = false;
        $pflichtstunde->rejected_at = null;
        $pflichtstunde->rejected_by = null;
        $pflichtstunde->rejection_reason = null;
        $pflichtstunde->save();

        $nextPflichtstunde = Pflichtstunde::query()
            ->where('approved', false)
            ->where('rejected', false)
            ->where('end', '<', now())
            ->where('id', '>', $currentId)
            ->orderBy('id', 'asc')
            ->first();

        $params = [];
        if ($nextPflichtstunde) {
            $params['scroll_to'] = $nextPflichtstunde->id;
        }
        if ($request->has('bereich_filter')) {
            $params['bereich_filter'] = $request->input('bereich_filter');
        }

        return redirect()->route('pflichtstunden.indexVerwaltung', $params)
            ->with('success', 'Pflichtstunde genehmigt');
    }

    public function approveMultiple(Request $request)
    {
        if (! auth()->user()->can('edit Pflichtstunden')) {
            return redirect(url('/'))->with('error', 'Berechtigung fehlt');
        }

        $ids = json_decode($request->input('ids'), true);
        if (empty($ids) || ! is_array($ids)) {
            return redirect()->route('pflichtstunden.indexVerwaltung')->with('error', 'Keine Pflichtstunden ausgewählt');
        }

        $count = Pflichtstunde::query()
            ->whereIn('id', $ids)
            ->where('approved', false)
            ->where('rejected', false)
            ->update([
                'approved' => true,
                'approved_at' => now(),
                'approved_by' => auth()->id(),
                'rejected' => false,
                'rejected_at' => null,
                'rejected_by' => null,
                'rejection_reason' => null,
            ]);

        return redirect()->route('pflichtstunden.indexVerwaltung')
            ->with('success', $count.' Pflichtstunde(n) wurden genehmigt');
    }

    public function reject(Request $request, Pflichtstunde $pflichtstunde)
    {
        if (! auth()->user()->can('edit Pflichtstunden')) {
            return redirect(url('/'))->with('error', 'Berechtigung fehlt');
        }

        $request->validate([
            'rejection_reason' => 'nullable|string|max:255',
        ]);

        $pflichtstunde->approved = false;
        $pflichtstunde->approved_at = null;
        $pflichtstunde->approved_by = null;
        $pflichtstunde->rejected = true;
        $pflichtstunde->rejected_at = now();
        $pflichtstunde->rejected_by = auth()->id();
        $pflichtstunde->rejection_reason = $request->input('rejection_reason');
        $pflichtstunde->save();

        $params = [];
        if ($request->has('bereich_filter')) {
            $params['bereich_filter'] = $request->input('bereich_filter');
        }

        return redirect()->route('pflichtstunden.indexVerwaltung', $params)
            ->with('success', 'Pflichtstunde abgelehnt');
    }

    public function export(Request $request)
    {
        if (! auth()->user()->can('edit Pflichtstunden')) {
            return redirect(url('/'))->with('error', 'Berechtigung fehlt');
        }

        $startInput = $request->input('start');
        $endInput = $request->input('end');

        if ($startInput || $endInput) {
            $request->validate([
                'start' => 'required|date_format:Y-m-d',
                'end' => 'required|date_format:Y-m-d|after_or_equal:start',
            ]);

            $startDate = \Carbon\Carbon::createFromFormat('Y-m-d', $startInput)->startOfDay();
            $endDate = \Carbon\Carbon::createFromFormat('Y-m-d', $endInput)->endOfDay();
            $label = $startDate->format('d.m.Y').' - '.$endDate->format('d.m.Y');

            return Excel::download(
                new PflichtstundenExport(null, $startDate, $endDate, $label),
                'pflichtstunden_abrechnung_'.$startDate->format('Y-m-d').'_'.$endDate->format('Y-m-d').'_'.date('Y-m-d').'.xlsx'
            );
        }

        $year = $request->get('year', null);

        return Excel::download(
            new PflichtstundenExport($year),
            'pflichtstunden_abrechnung_'.($year ?? 'aktuell').'_'.date('Y-m-d').'.xlsx'
        );
    }

    public function reportPdf(Request $request)
    {
        if (! auth()->user()->can('edit Pflichtstunden')) {
            return redirect(url('/'))->with('error', 'Berechtigung fehlt');
        }

        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'min:2020', 'max:2100'],
            'start' => ['nullable', 'date_format:Y-m-d'],
            'end' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start'],
            'sort' => ['nullable', 'in:family_name,highest_debt'],
            'anonymized' => ['nullable', 'boolean'],
        ]);

        $sort = $validated['sort'] ?? 'family_name';
        $anonymized = (bool) ($validated['anonymized'] ?? false);

        if (! empty($validated['start']) || ! empty($validated['end'])) {
            $request->validate([
                'start' => 'required|date_format:Y-m-d',
                'end' => 'required|date_format:Y-m-d|after_or_equal:start',
            ]);

            $periodStart = \Carbon\Carbon::createFromFormat('Y-m-d', $validated['start'])->startOfDay();
            $periodEnd = \Carbon\Carbon::createFromFormat('Y-m-d', $validated['end'])->endOfDay();
        } else {
            $year = $validated['year'] ?? $this->familyService->resolvePeriod(null)[0]->year;
            [$periodStart, $periodEnd] = $this->familyService->resolvePeriod((int) $year);
        }

        $report = $this->reportPdfService->buildReport($periodStart, $periodEnd, $sort, $anonymized);

        $pdf = Pdf::loadView('pflichtstunden.report-pdf', $report)
            ->setPaper('A4', 'portrait');

        $filename = 'pflichtstunden_report_'.
            $periodStart->format('Y-m-d').'_'.$periodEnd->format('Y-m-d').
            ($anonymized ? '_anonymisiert' : '').'.pdf';

        return $pdf->download($filename);
    }

    public function update(UpdatePflichtstundeRequest $request, Pflichtstunde $pflichtstunde)
    {
        if ($pflichtstunde->approved || $pflichtstunde->rejected) {
            return redirect()->back()->with('error', 'Pflichtstunde kann nicht mehr bearbeitet werden, da sie bereits bestätigt oder abgelehnt wurde.');
        }

        $data = $request->validated();
        unset($data['user_id']);
        $pflichtstunde->update($data);

        return redirect()->back()->with('success', 'Pflichtstunde aktualisiert');
    }

    public function destroy(Pflichtstunde $pflichtstunde)
    {
        if (! auth()->user()->can('edit Pflichtstunden') &&
            ($pflichtstunde->user_id !== auth()->id() || $pflichtstunde->approved || $pflichtstunde->rejected)) {
            return redirect()->back()->with('error', 'Berechtigung fehlt oder Pflichtstunde kann nicht mehr gelöscht werden.');
        }

        if (! auth()->user()->can('edit Pflichtstunden') && ($pflichtstunde->approved || $pflichtstunde->rejected)) {
            return redirect()->back()->with('error', 'Pflichtstunde kann nicht mehr gelöscht werden, da sie bereits bestätigt oder abgelehnt wurde.');
        }

        $pflichtstunde->delete();

        return redirect()->back()->with('success', 'Pflichtstunde gelöscht');
    }

    private function availablePeriodYears(): array
    {
        $earliestStart = Pflichtstunde::withoutGlobalScope('aktuellerZeitraum')->where('approved', true)->min('start');
        $earliestYear = $earliestStart
            ? \Carbon\Carbon::parse($earliestStart)->year
            : (int) date('Y') - 1;
        $currentPeriodStartYear = (int) $this->familyService->resolvePeriod(null)[0]->year;

        return range($currentPeriodStartYear - 1, min($earliestYear, $currentPeriodStartYear - 1), -1);
    }

    /**
     * @param \Illuminate\Support\Collection<int, array<string, mixed>> $summaries
     * @return array<string, mixed>
     */
    private function calculateParentStatsFromSummaries(\Illuminate\Support\Collection $summaries, int $currentUserId): array
    {
        $sorted = $summaries->sortByDesc('percent')->values();
        $yourRank = 1;
        foreach ($sorted as $index => $summary) {
            if (in_array($currentUserId, $summary['user_ids'])) {
                $yourRank = $index + 1;
                break;
            }
        }

        return [
            'your_rank' => $yourRank,
            'total_parents' => $summaries->count(),
            'avg_progress' => round((float) $summaries->avg('percent'), 2),
            'avgPercent' => round((float) $summaries->avg('percent'), 2),
            'expectedAvgPercent' => round((float) $summaries->avg('expected_percent'), 2),
        ];
    }

    /**
     * @param \Illuminate\Support\Collection<int, array<string, mixed>> $groupedUsers
     * @return array{0:array<int,int>,1:\Illuminate\Support\Collection<int, array<string,mixed>>,2:array<int,int>}
     */
    private function buildOverlapGroups(\Illuminate\Support\Collection $groupedUsers): array
    {
        $familyUserMap = [];
        $familyNameMap = [];
        foreach ($groupedUsers as $group) {
            foreach ($group['user_ids'] as $uid) {
                $familyUserMap[$uid] = $group['family_key'];
            }
            $familyNameMap[$group['family_key']] = $group['family_name'];
        }

        $allNonRejectedPs = Pflichtstunde::query()
            ->where('rejected', false)
            ->with('user')
            ->get();

        $familyPflichtstunden = [];
        foreach ($allNonRejectedPs as $ps) {
            $familyKey = $familyUserMap[$ps->user_id] ?? (string) $ps->user_id;
            $familyPflichtstunden[$familyKey][] = $ps;
        }

        $adjacency = [];
        foreach ($familyPflichtstunden as $familyPs) {
            $count = count($familyPs);
            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $a = $familyPs[$i];
                    $b = $familyPs[$j];
                    if ($a->start < $b->end && $a->end > $b->start) {
                        $adjacency[$a->id][] = $b->id;
                        $adjacency[$b->id][] = $a->id;
                    }
                }
            }
        }

        $allPsById = $allNonRejectedPs->keyBy('id');
        $visited = [];
        $overlapGroups = collect();
        $groupCounter = 1;

        foreach ($allNonRejectedPs as $ps) {
            if (! isset($adjacency[$ps->id]) || isset($visited[$ps->id])) {
                continue;
            }

            $groupIds = [];
            $queue = [$ps->id];
            while (! empty($queue)) {
                $currentId = array_shift($queue);
                if (isset($visited[$currentId])) {
                    continue;
                }
                $visited[$currentId] = true;
                $groupIds[] = $currentId;
                foreach ($adjacency[$currentId] ?? [] as $neighborId) {
                    if (! isset($visited[$neighborId])) {
                        $queue[] = $neighborId;
                    }
                }
            }

            if (count($groupIds) < 2) {
                continue;
            }

            $groupEntries = collect($groupIds)
                ->map(fn (int $id) => $allPsById->get($id))
                ->filter()
                ->sortBy('start')
                ->values();

            $firstEntry = $groupEntries->first();
            $familyKey = $familyUserMap[$firstEntry->user_id] ?? null;
            $familyName = $familyKey ? ($familyNameMap[$familyKey] ?? null) : null;

            if (empty($familyName)) {
                $familyName = $firstEntry->user?->name ?? 'Unbekannt / gelöschter Benutzer';
            }

            $overlapGroups->push([
                'group_id' => $groupCounter++,
                'entries' => $groupEntries,
                'family_name' => $familyName,
            ]);
        }

        $overlappingIds = array_keys($adjacency);
        $entryGroupMap = [];
        foreach ($overlapGroups as $group) {
            foreach ($group['entries'] as $entry) {
                $entryGroupMap[$entry->id] = $group['group_id'];
            }
        }

        return [$overlappingIds, $overlapGroups, $entryGroupMap];
    }
}
