<?php

namespace App\Http\Controllers\API\V1;

use App\Services\App\TerminQuery;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group App: Termine
 */
class TerminController extends ApiController
{
    /**
     * Termine im Zeitraum (Standard: heute bis +6 Monate), inkl. eigener Listen-Buchungen – B-30.
     *
     * @queryParam from date Example: 2026-09-01
     * @queryParam to date Example: 2026-12-31
     * @queryParam include string `listen` (Standard) oder leer. Example: listen
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate(['from' => 'nullable|date', 'to' => 'nullable|date|after_or_equal:from']);

        $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : today();
        $to = $request->filled('to') ? Carbon::parse($request->to)->endOfDay() : today()->addMonths(6)->endOfDay();
        if ($from->diffInDays($to) > 400) {
            return response()->json(['message' => 'Der Zeitraum darf höchstens ein Jahr umfassen.'], 422);
        }

        return response()->json([
            'data' => TerminQuery::between($request->user(), $from, $to, $request->input('include', 'listen') === 'listen'),
            'meta' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'ical_url' => $request->user()->releaseCalendar ? url($request->user()->uuid.'/ical') : null,
            ],
        ]);
    }
}
