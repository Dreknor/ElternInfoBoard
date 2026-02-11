<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Model\AbfrageAntworten;
use App\Model\AbfrageOptions;
use App\Model\Post;
use App\Model\Rueckmeldungen;
use App\Model\UserRueckmeldungen;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Log;

/**
 * Class AbfragenController
 * Controller for handling abfragen related API requests.
 */
class AbfragenController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth:sanctum',
        ];
    }

    /**
     * Get fields for a post
     *
     * Get the fields for the post with the given id
     *
     * @group Rückmeldungen
     *
     * @urlParam post_id required The id of the post. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "fields": [
     *       {
     *         "id": 1,
     *         "option": "Name",
     *         "type": "text",
     *         "required": true
     *       },
     *       {
     *         "id": 2,
     *         "option": "Email",
     *         "type": "email",
     *         "required": true
     *       }
     *     ],
     *     "rueckmeldung": {
     *       "id": 1,
     *       "post_id": 1,
     *       "type": "abfrage",
     *       "ende": "2026-03-01T00:00:00.000000Z",
     *       "text": "Bitte füllen Sie die Abfrage aus",
     *       "pflicht": true,
     *       "multiple": false,
     *       "max_answers": 1
     *     }
     *   },
     *   "message": "Felder erfolgreich abgerufen"
     * }
     *
     * @response 404 {
     *   "success": false,
     *   "error": "Post not found",
     *   "message": "Beitrag nicht gefunden"
     * }
     *
     * @response 403 {
     *   "success": false,
     *   "error": "Access denied",
     *   "message": "Sie haben keine Berechtigung für diesen Beitrag"
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFields($post_id)
    {

        $post = Post::query()->where('id', $post_id)->firstOrFail();

        if ($post == null) {
            return response()->json([
                'success' => false,
                'error' => 'Post not found',
                'message' => 'Beitrag nicht gefunden'
            ], 404);
        }

        if ($post->groups->intersect(auth()->user()->groups)->count() == 0) {
            return response()->json([
                'success' => false,
                'error' => 'Access denied',
                'message' => 'Sie haben keine Berechtigung für diesen Beitrag'
            ], 403);
        }

        $rueckmeldung = Rueckmeldungen::query()
            ->where('post_id', $post_id)
            ->first([
                'id',
                'post_id',
                'type',
                'ende',
                'text',
                'pflicht',
                'multiple',
                'max_answers',
            ]);

        if (!$rueckmeldung) {
            return response()->json([
                'success' => false,
                'error' => 'No rueckmeldung found',
                'message' => 'Keine Rückmeldung für diesen Beitrag gefunden'
            ], 404);
        }

        Log::debug('API: Get fields for post '.$post_id.' and rueckmeldung '.$rueckmeldung->id);
        Log::debug($rueckmeldung);

        $optionen = AbfrageOptions::query()
            ->where('rueckmeldung_id', $rueckmeldung->id)
            ->get(['id', 'option', 'type', 'required']);

        return response()->json([
            'success' => true,
            'data' => [
                'fields' => $optionen,
                'rueckmeldung' => $rueckmeldung,
            ],
            'message' => 'Felder erfolgreich abgerufen',
        ]);

    }

    /**
     * Store answer
     *
     * Store the answer for the post with the given id
     *
     * @group Rückmeldungen
     *
     * @urlParam post required The id of the post. Example: 1
     *
     * @bodyParam data array required The data to store. The data must be an array of objects with the following structure: [{"id": 1, "value": "Antwort"}]. Each object must have an "id" (the field ID from getFields) and a "value" (the user's answer). Example: [{"id": 1, "value": "Max Mustermann"}, {"id": 2, "value": "max@example.com"}]
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Antwort gespeichert"
     * }
     *
     * @response 404 {
     *   "success": false,
     *   "error": "Post not found",
     *   "message": "Beitrag nicht gefunden"
     * }
     *
     * @response 403 {
     *   "success": false,
     *   "error": "Access denied",
     *   "message": "Sie haben keine Berechtigung, auf diesen Beitrag zu antworten"
     * }
     *
     * @response 400 {
     *   "success": false,
     *   "error": "Invalid data",
     *   "message": "Ungültige Daten"
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeAnswer(Request $request, $post)
    {

        $request->validate([
            'data' => 'required|array',
            'data.*.id' => 'required|integer',
            'data.*.value' => 'required',
        ]);

        $post = Post::query()->where('id', $post)->firstOrFail();

        if ($post == null) {
            return response()->json([
                'success' => false,
                'error' => 'Post not found',
                'message' => 'Beitrag nicht gefunden'
            ], 404);
        }

        if ($post->groups->intersect(request()->user()->groups)->count() == 0) {
            return response()->json([
                'success' => false,
                'error' => 'Access denied',
                'message' => 'Sie haben keine Berechtigung, auf diesen Beitrag zu antworten'
            ], 403);
        }

        $rueckmeldung = $post->rueckmeldung;

        if ($rueckmeldung == null) {
            return response()->json([
                'success' => false,
                'error' => 'No abfrage found',
                'message' => 'Keine Abfrage für diesen Beitrag gefunden'
            ], 404);
        }

        if ($rueckmeldung->type != 'abfrage') {
            return response()->json([
                'success' => false,
                'error' => 'Invalid abfrage type',
                'message' => 'Ungültiger Rückmeldungstyp'
            ], 400);
        }

        $userRueckmeldung = UserRueckmeldungen::query()
            ->where('post_id', $post->id)
            ->where('users_id', request()->user()->id)
            ->first();

        if ($rueckmeldung->multiple == 1 or $userRueckmeldung == null) {
            $userRueckmeldung = UserRueckmeldungen::create([
                'post_id' => $post->id,
                'users_id' => request()->user()->id,
                'text' => ' ',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $data = [];

            foreach ($request->data as $value) {

                if (is_array($value) && isset($value['id']) && isset($value['value'])) {
                    if (! is_numeric($value['id'])) {
                        return response()->json([
                            'success' => false,
                            'error' => 'Invalid data',
                            'message' => 'Ungültige Daten: ID muss numerisch sein'
                        ], 400);
                    }

                    $data[] = [
                        'rueckmeldung_id' => $userRueckmeldung->id,
                        'user_id' => request()->user()->id,
                        'option_id' => $value['id'],
                        'answer' => $value['value'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                } else {
                    return response()->json([
                        'success' => false,
                        'error' => 'Invalid data',
                        'message' => 'Ungültige Daten: Jedes Element muss "id" und "value" enthalten'
                    ], 400);
                }

            }

        } else {
            $userRueckmeldung = UserRueckmeldungen::updateOrCreate([
                'post_id' => $post->id,
                'users_id' => request()->user()->id],
                [
                    'text' => ' ',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            AbfrageAntworten::query()->where('rueckmeldung_id', $userRueckmeldung->id)->delete();

            $data = [];

            foreach ($request->data as $value) {

                if (is_array($value) && isset($value['id']) && isset($value['value'])) {
                    if (! is_numeric($value['id'])) {
                        return response()->json([
                            'success' => false,
                            'error' => 'Invalid data',
                            'message' => 'Ungültige Daten: ID muss numerisch sein'
                        ], 400);
                    }

                    $data[] = [
                        'rueckmeldung_id' => $userRueckmeldung->id,
                        'user_id' => request()->user()->id,
                        'option_id' => $value['id'],
                        'answer' => $value['value'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                } else {
                    return response()->json([
                        'success' => false,
                        'error' => 'Invalid data',
                        'message' => 'Ungültige Daten: Jedes Element muss "id" und "value" enthalten'
                    ], 400);
                }

            }

        }

        if (count($data) == 0) {
            $userRueckmeldung->delete();

            return response()->json([
                'success' => false,
                'error' => 'Invalid data',
                'message' => 'Keine gültigen Daten zum Speichern'
            ], 400);
        }

        try {
            AbfrageAntworten::insert($data);
        } catch (\Exception $e) {
            Log::error('API: Error saving abfrage antworten: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Error saving data',
                'message' => 'Fehler beim Speichern der Daten'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Antwort gespeichert'
        ]);

    }
}
