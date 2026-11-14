<?php

namespace App\Http\Controllers\API\V1;

use App\Model\Termin;
use App\Services\App\TerminQuery;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * @group Termine
 */
class TerminController extends ApiController
{
    /**
     * Termine im Zeitraum (Standard: heute bis +6 Monate), inkl. eigener Listen-Buchungen.
     *
     * @queryParam from date Example: 2026-09-01
     * @queryParam to date Example: 2026-12-31
     * @queryParam include string `listen` (Standard) oder leer. Example: listen
     */
    public function index(Request $request): JsonResponse
    {
        [$from, $to] = $this->range($request);
        if ($from->diffInDays($to) > 400) {
            return response()->json(['message' => 'Der Zeitraum darf höchstens ein Jahr umfassen.'], 422);
        }

        $user = $request->user();

        return response()->json([
            'data' => TerminQuery::between($user, $from, $to, $request->input('include', 'listen') === 'listen'),
            'meta' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'ical_url' => $user->releaseCalendar ? url($user->uuid.'/ical') : null,
                // B-102: Anzahl ausgeblendeter Termine im Zeitraum; die App erkennt daran auch, dass der Server Ausblenden kann.
                'hidden_count' => Termin::query()
                    ->where('ende', '>=', $from)
                    ->where('start', '<=', $to)
                    ->forRecipient($user)
                    ->hiddenFor($user)
                    ->count(),
            ],
        ]);
    }

    /**
     * Vom Nutzer ausgeblendete Termine im Zeitraum (B-102).
     *
     * @queryParam from date Example: 2026-09-01
     * @queryParam to date Example: 2026-12-31
     */
    public function hidden(Request $request): JsonResponse
    {
        [$from, $to] = $this->range($request);
        if ($from->diffInDays($to) > 400) {
            return response()->json(['message' => 'Der Zeitraum darf höchstens ein Jahr umfassen.'], 422);
        }

        return response()->json(['data' => TerminQuery::hiddenBetween($request->user(), $from, $to)]);
    }

    /**
     * Termin für sich ausblenden (B-102) – gilt in Web, App und iCal-Feed.
     */
    public function hide(Request $request, int $termin): JsonResponse
    {
        $user = $request->user();
        $model = $this->visibleTermin($user, $termin);
        $user->hiddenTermine()->syncWithoutDetaching([$model->id]);
        Cache::forget('termine'.$user->id);

        return response()->json(['data' => ['id' => 'termin-'.$model->id, 'hidden' => true]]);
    }

    /**
     * Ausgeblendeten Termin wieder anzeigen (B-102).
     */
    public function unhide(Request $request, int $termin): JsonResponse
    {
        $user = $request->user();
        $model = $this->visibleTermin($user, $termin);
        $user->hiddenTermine()->detach($model->id);
        Cache::forget('termine'.$user->id);

        return response()->json(['data' => ['id' => 'termin-'.$model->id, 'hidden' => false]]);
    }

    /** Nur Termine, die der Nutzer erhält – fremde Termine sind nicht auffindbar (404). */
    private function visibleTermin($user, int $id): Termin
    {
        return Termin::query()->whereKey($id)->forRecipient($user)->firstOrFail();
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function range(Request $request): array
    {
        $request->validate(['from' => 'nullable|date', 'to' => 'nullable|date|after_or_equal:from']);

        return [
            $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : today(),
            $request->filled('to') ? Carbon::parse($request->to)->endOfDay() : today()->addMonths(6)->endOfDay(),
        ];
    }
}
