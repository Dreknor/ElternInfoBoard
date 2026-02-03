<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mail\Krankmeldung;
use App\Model\ActiveDisease;
use App\Model\Disease;
use App\Model\krankmeldungen;
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
     * @bodyParam name string required The name of the Krankmeldung.
     * @bodyParam kommentar string required The comment of the Krankmeldung.
     * @bodyParam start string required The start date of the Krankmeldung.
     * @bodyParam ende string required The end date of the Krankmeldung.
     * @bodyParam disease_id int The id of the disease.
     *
     * @responseField message string The message.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'kommentar' => 'required',
            'start' => 'required',
            'ende' => 'required',
            'disease_id' => 'nullable',
        ]);

        try {
            $krankmeldung = new krankmeldungen(
                [
                    'name' => $request->name,
                    'kommentar' => $request->kommentar,
                    'start' => Carbon::createFromFormat('d.m.Y', $request->start),
                    'ende' => Carbon::createFromFormat('d.m.Y', $request->ende),
                    'users_id' => $request->user()->id,
                ]
            );

            $krankmeldung->save();
        } catch (\Exception $e) {
            Log::error('Krankmeldung: Fehler beim Speichern der Krankmeldung: '.$e->getMessage());
            $text = 'Fehler beim Speichern der Krankmeldung. Bitte überprüfen Sie die Eingaben.';
        }

        try {
            if ($request->disease_id != null && $request->disease_id != 0) {
                $disease = Disease::find($request->disease_id);
                ActiveDisease::insert([
                    'user_id' => auth()->id(),
                    'disease_id' => $request->disease_id,
                    'start' => $krankmeldung->start,
                    'end' => $krankmeldung->start->addDays($disease->aushang_dauer),
                    'active' => false,
                ]);

                Cache::forget('active_diseases');
            }
        } catch (\Exception $e) {
            Log::error('Krankmeldung: Fehler beim Speichern der Krankheit: '.$e->getMessage());
            $text .= 'Fehler beim Speichern der Krankheit. Bitte überprüfen Sie die Eingaben.';
        }

        try {
            // If API caller uploaded files via multipart/form-data with key 'files', store them
            $attachments = [];
            if ($request->hasFile('files')) {
                $krankmeldung->addAllMediaFromRequest(['files'])
                    ->each(fn ($fileAdder) => $fileAdder->toMediaCollection('files'));

                foreach ($krankmeldung->getMedia('files') as $media) {
                    $attachments[] = $media;
                }
            }

            Mail::to(config('mail.from.address'))
                ->cc($request->user()->email)
                ->queue(new Krankmeldung($request->user()->email, $request->user()->name, $request->name, $request->start, $request->ende, $request->kommentar, $disease->name ?? null, $attachments));

            return response()->json('Krankmeldung gesendet.', 200);
        } catch (\Exception $e) {
            Log::error('Krankmeldung-Fehler:', [
                'error' => $e->getMessage(),
            ]);
            $text .= 'Fehler beim Senden der Krankmeldung. Bitte überprüfen Sie die Eingaben.';
        }

        return response()->json([
            'message' => $text,
        ], 400);

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
            ->whereDate('end', '>=', Carbon::now()->addDay())
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
            return response()->json(null, 200);
        }
    }
}
