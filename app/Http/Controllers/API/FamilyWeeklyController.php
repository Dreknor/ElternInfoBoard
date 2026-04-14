<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\FamilyWeeklyService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FamilyWeeklyController extends Controller
{
    public function __construct(
        private FamilyWeeklyService $service,
    ) {}

    /**
     * GET /api/family/weekly?week=2026-W15
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'week' => ['nullable', 'regex:/^\d{4}-W\d{1,2}$/'],
        ]);

        $weekStart = $this->parseWeek($request->input('week'));
        $data      = $this->service->getWeeklyData($request->user(), $weekStart);

        return response()->json([
            'week_label' => $data['week_label'],
            'week_start' => $data['week_start']->format('Y-m-d'),
            'week_end'   => $data['week_end']->format('Y-m-d'),
            'holidays'   => $data['holidays']->map(fn ($h) => [
                'name'  => $h->name,
                'start' => $h->start->format('Y-m-d'),
                'end'   => $h->end->format('Y-m-d'),
            ]),
            'children'   => $data['children']->map(fn ($c) => $this->transformChild($c)),
        ]);
    }

    /**
     * GET /api/family/weekly/{child_id}?week=2026-W15
     */
    public function show(Request $request, int $childId): JsonResponse
    {
        $request->validate([
            'week' => ['nullable', 'regex:/^\d{4}-W\d{1,2}$/'],
        ]);

        $user     = $request->user();
        $children = $user->children();
        $child    = $children?->firstWhere('id', $childId);

        if (! $child) {
            return response()->json(['error' => 'Kind nicht gefunden oder kein Zugriff.'], 403);
        }

        $weekStart = $this->parseWeek($request->input('week'));
        $data      = $this->service->getWeeklyData($user, $weekStart);

        $childData = $data['children']->first(fn ($c) => $c['child']->id === $childId);

        if (! $childData) {
            return response()->json(['error' => 'Kind nicht gefunden.'], 404);
        }

        return response()->json($this->transformChild($childData));
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function transformChild(array $c): array
    {
        return [
            'child_id'   => $c['child']->id,
            'child_name' => $c['child']->first_name . ' ' . $c['child']->last_name,
            'klasse'     => $c['klasse'],
            'summary'    => $c['summary'],
            'days'       => collect($c['days'])->map(fn ($d, $day) => [
                'day'          => $day,
                'date'         => $d['date']->format('Y-m-d'),
                'is_holiday'   => $d['is_holiday'],
                'holiday_name' => $d['holiday_name'],
                'stundenplan'  => $d['stundenplan'],
                'vertretungen' => $d['vertretungen']->toArray(),
                'gtas'         => $d['gtas']->map(fn ($g) => [
                    'name'  => $g->name,
                    'start' => $g->start_time?->format('H:i'),
                    'end'   => $g->end_time?->format('H:i'),
                ]),
                'schickzeiten'  => $d['schickzeiten']->map(fn ($s) => [
                    'time'      => $s->time?->format('H:i'),
                    'type'      => $s->type,
                    'time_ab'   => $s->time_ab?->format('H:i'),
                    'time_spaet' => $s->time_spaet?->format('H:i'),
                ]),
                'krankmeldung' => $d['krankmeldung'] ? true : false,
                'checkIn'      => $d['checkIn'] ? [
                    'checked_in' => $d['checkIn']->checked_in,
                    'should_be'  => $d['checkIn']->should_be,
                    'lock_at'    => $d['checkIn']->lock_at?->toDateString(),
                    'comment'    => $d['checkIn']->comment,
                ] : null,
            ]),
            'termine' => $c['termine']->map(fn ($t) => [
                'name'    => $t->terminname,
                'start'   => $t->start->format('Y-m-d H:i'),
                'end'     => $t->ende?->format('Y-m-d H:i'),
                'fullDay' => $t->fullDay,
            ]),
        ];
    }

    private function parseWeek(?string $week): ?Carbon
    {
        if (! $week) {
            return null;
        }

        $parts = explode('-W', $week);
        if (count($parts) !== 2) {
            return null;
        }

        return Carbon::now()
            ->setISODate((int) $parts[0], (int) $parts[1])
            ->startOfWeek();
    }
}

