<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Model\AbfrageAntworten;
use App\Model\AbfrageOptions;
use App\Model\Post;
use App\Model\Rueckmeldungen;
use App\Model\UserRueckmeldungen;
use App\Services\App\FeedbackService;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
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
    public function storeAnswer(Request $request, $post, FeedbackService $service)
    {
        $request->validate([
            'data' => 'required|array',
            'data.*.id' => 'required|integer',
            'data.*.value' => 'present',
        ]);

        $post = Post::query()->findOrFail($post);
        $answers = collect($request->input('data'))->mapWithKeys(fn ($item) => [(int) $item['id'] => $item['value']])->all();

        // Gemeinsame Regeln mit der neuen API (B-21): Frist, Optionen dieser Abfrage, Pflichtfelder, Höchstzahl.
        try {
            $service->storeAbfrage($request->user(), $post, $answers);
        } catch (HttpException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid data',
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        }

        return response()->json([
            'success' => true,
            'message' => 'Antwort gespeichert',
        ]);
    }
}
