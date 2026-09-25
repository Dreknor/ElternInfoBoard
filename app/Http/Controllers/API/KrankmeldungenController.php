<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mail\Krankmeldung;
use App\Model\ActiveDisease;
use App\Model\Child;
use App\Model\Disease;
use App\Model\krankmeldungen;
use App\Services\App\Family;
use App\Services\App\KrankmeldungService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Class KrankmeldungenController
 *
 * Controller for handling sick leave related API requests.
 */
class KrankmeldungenController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth:sanctum',
        ];
    }

    /**
     * Get all diseases.
     *
     * Get all reportable diseases from the database.
     *
     * @group Krankmeldungen
     *
     * @responseField diseases array The diseases.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDiseses(Request $request)
    {
        $diseases = Disease::query()->get(['id', 'name']);

        return response()->json([
            'diseases' => $diseases,
        ], 200);
    }

    /**
     * Store a new Krankmeldung.
     *
     * Store a new Krankmeldung in the database.
     *
     * @group Krankmeldungen
     *
     * @bodyParam name string The name of the Krankmeldung (required if child_id is not provided).
     * @bodyParam child_id int The id of the child (required if name is not provided).
     * @bodyParam kommentar string required The comment of the Krankmeldung.
     * @bodyParam start string required The start date of the Krankmeldung (format: d.m.Y).
     * @bodyParam ende string required The end date of the Krankmeldung (format: d.m.Y).
     * @bodyParam disease_id int The id of the disease.
     *
     * @responseField message string The message.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, KrankmeldungService $service)
    {
        $request->validate([
            'name' => 'nullable|string|max:400',
            'child_id' => 'nullable|integer|exists:children,id',
            'kommentar' => 'required|string|max:2000',
            'start' => 'required|string',
            'ende' => 'required|string',
            'disease_id' => 'nullable|integer',
            'files.*' => 'file|max:10240',
        ]);

        if (! $request->name && ! $request->child_id) {
            return response()->json(['message' => 'Bitte geben Sie einen Namen oder ein Kind an'], 422);
        }

        $user = $request->user();
        $child = null;
        if ($request->child_id) {
            // Nur eigene Kinder (B-40) – vorher konnte jedes Kind krankgemeldet werden.
            if (! Family::ownsChild($user, (int) $request->child_id)) {
                return response()->json(['message' => 'Sie können nur Ihre eigenen Kinder krankmelden.'], 403);
            }
            $child = Child::find($request->child_id);
        }

        try {
            $start = KrankmeldungService::parseDate($request->start);
            $ende = KrankmeldungService::parseDate($request->ende);
        } catch (\Throwable) {
            return response()->json(['message' => 'Bitte geben Sie ein gültiges Datum an.'], 422);
        }
        if ($ende->lt($start)) {
            return response()->json(['message' => 'Das Ende darf nicht vor dem Beginn liegen.'], 422);
        }

        try {
            $service->create(
                $user,
                $child,
                $request->name,
                $start,
                $ende,
                $request->kommentar,
                $request->integer('disease_id') ?: null,
                $request->file('files', []),
            );
        } catch (\Throwable $e) {
            Log::error('Krankmeldung (API): '.$e->getMessage());

            return response()->json(['message' => 'Die Krankmeldung konnte nicht gespeichert werden.'], 500);
        }

        // Format für ältere App-Versionen unverändert.
        return response()->json('Krankmeldung gesendet.', 200);
    }

    /**
     * Get all  active reportable diseases.
     *
     * Get all active reportable diseases from the database.
     *
     * @group Krankmeldungen
     *
     * @responseField data array The active diseases.
     * @responseField data.id int The ID of the disease.
     * @responseField data.name string The name of the disease.
     * @responseField data.start string The start date of the disease.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActiveDisease(Request $request)
    {
        $activeDisease = ActiveDisease::query()
            ->where('active', true)
            ->whereDate('end', '>=', Carbon::today())
            ->with('disease')
            ->get();

        if (count($activeDisease) > 0) {
            $result = [];

            foreach ($activeDisease as $key => $disease) {
                $result[] = [
                    'id' => $disease->id,
                    'name' => $disease->disease->name,
                    'start' => $disease->start->format('Y-m-d'),
                ];
            }

            return response()->json(
                ['data' => $result], 200);
        } else {
            // Immer dasselbe Format (B-41) – vorher kam hier ein leerer Body.
            return response()->json(['data' => []], 200);
        }
    }
}
