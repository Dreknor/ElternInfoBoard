<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Resources\V1\ChildResource;
use App\Model\ActiveDisease;
use App\Model\Child;
use App\Model\Losung;
use App\Services\App\Family;
use App\Services\App\PostPresenter;
use App\Services\App\PostQuery;
use App\Services\App\TodoService;
use App\Services\App\TerminQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group App: Start
 */
class DashboardController extends ApiController
{
    /**
     * Startseite in einer Anfrage – B-03.
     *
     * Offene Aufgaben, Kinder heute, nächste Termine, neueste Beiträge, Losung, Erkrankungen.
     */
    public function index(Request $request, TodoService $todos): JsonResponse
    {
        $user = $request->user();
        $childIds = Family::childIds($user);

        $children = Child::query()
            ->whereIn('id', $childIds)
            ->with([
                'group', 'class',
                'checkIns' => fn ($q) => $q->whereDate('date', today()),
                'krankmeldungen' => fn ($q) => $q->whereDate('start', '<=', today())->whereDate('ende', '>=', today()),
                'schickzeiten' => fn ($q) => $q->where(fn ($w) => $w->whereDate('specific_date', today())
                    ->orWhere(fn ($r) => $r->whereNull('specific_date')->where('weekday', today()->dayOfWeekIso))),
            ])
            ->orderBy('first_name')
            ->get();

        $posts = PostQuery::current($user)
            ->select('posts.*')
            ->with(['media', 'autor:id,name', 'poll:id,post_id'])
            ->withCount('comments')
            ->orderByDesc('sticky')->orderByDesc('posts.updated_at')
            ->limit(3)
            ->get();

        $losung = Losung::whereDate('date', today())->first(['Losungstext', 'Losungsvers', 'Lehrtext', 'Lehrtextvers']);

        return response()->json(['data' => [
            'todo' => $todos->forUser($user),
            'children' => $children->map(function (Child $child) use ($request) {
                $checkIn = $child->checkIns->first();
                $specific = $child->schickzeiten->firstWhere('specific_date', '!=', null);
                $schickzeit = $specific ?? $child->schickzeiten->first();

                return (new ChildResource($child))->toArray($request) + [
                    'today' => [
                        'check_in' => $checkIn ? [
                            'id' => $checkIn->id,
                            'should_be' => $checkIn->should_be,
                            'checked_in' => (bool) $checkIn->checked_in,
                            'checked_out' => (bool) $checkIn->checked_out,
                            'checked_in_at' => $checkIn->checked_in_at?->toIso8601String(),
                            'checked_out_at' => $checkIn->checked_out_at?->toIso8601String(),
                        ] : null,
                        'schickzeit' => $schickzeit ? [
                            'type' => $schickzeit->type,
                            'time' => $schickzeit->time?->format('H:i'),
                            'time_ab' => $schickzeit->time_ab?->format('H:i'),
                            'time_spaet' => $schickzeit->time_spaet?->format('H:i'),
                        ] : null,
                        'krank_bis' => $child->krankmeldungen->max('ende')?->toDateString(),
                    ],
                ];
            })->values(),
            'termine' => TerminQuery::upcoming($user, 3),
            'posts' => (new PostPresenter($user))->list($posts),
            'losung' => $losung,
            'active_diseases' => ActiveDisease::query()->where('active', true)->whereDate('end', '>=', today())
                ->with('disease:id,name')->get()->map(fn ($d) => ['name' => $d->disease?->name, 'start' => $d->start?->toDateString()])
                ->filter(fn ($d) => $d['name'])->values(),
        ]]);
    }

    /**
     * Offene Aufgaben der Familie – B-25.
     */
    public function todo(Request $request, TodoService $todos): JsonResponse
    {
        return response()->json(['data' => $todos->forUser($request->user())]);
    }
}
