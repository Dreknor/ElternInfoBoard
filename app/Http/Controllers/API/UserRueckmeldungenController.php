<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mail\UserRueckmeldung as UserRueckmeldungMail;
use App\Model\Post;
use App\Model\UserRueckmeldungen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * Class UserRueckmeldungenController
 *
 * Controller for handling user feedback (Rückmeldungen) related API requests.
 */
class UserRueckmeldungenController extends Controller
{
    /**
     * Store a newly created user feedback in storage.
     *
     * @group Rückmeldungen
     *
     * @bodyParam post_id integer required The ID of the post to which the feedback is related. Example: 1
     * @bodyParam text string required The feedback text. Example: "Ich nehme teil"
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Rückmeldung abgegeben",
     *   "data": {
     *     "id": 1,
     *     "post_id": 1,
     *     "users_id": 1,
     *     "text": "Ich nehme teil",
     *     "created_at": "2026-02-11T10:00:00.000000Z",
     *     "updated_at": "2026-02-11T10:00:00.000000Z"
     *   }
     * }
     *
     * @response 404 {
     *   "success": false,
     *   "error": "User not found",
     *   "message": "Benutzer nicht gefunden"
     * }
     *
     * @response 403 {
     *   "success": false,
     *   "error": "User not allowed",
     *   "message": "Keine Berechtigung für diesen Beitrag"
     * }
     *
     * @response 409 {
     *   "success": false,
     *   "error": "Already responded",
     *   "message": "Rückmeldung bereits abgegeben"
     * }
     *
     * @authenticated
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'post_id' => 'required|integer|exists:posts,id',
            'text' => 'required|string|max:5000',
        ]);

        $post = Post::query()->find($request->post_id);
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'error' => 'User not found',
                'message' => 'Benutzer nicht gefunden'
            ], 404);
        }

        if (! $post) {
            return response()->json([
                'success' => false,
                'error' => 'Post not found',
                'message' => 'Beitrag nicht gefunden'
            ], 404);
        }

        if (! $post->users->contains($user)) {
            return response()->json([
                'success' => false,
                'error' => 'User not allowed',
                'message' => 'Keine Berechtigung für diesen Beitrag'
            ], 403);
        }

        if (! $post->rueckmeldung) {
            return response()->json([
                'success' => false,
                'error' => 'Feedback not enabled',
                'message' => 'Rückmeldung für diesen Beitrag nicht aktiviert'
            ], 404);
        }

        if ($post->rueckmeldung->active === false) {
            return response()->json([
                'success' => false,
                'error' => 'Feedback not active',
                'message' => 'Rückmeldung ist nicht aktiv'
            ], 404);
        }

        if ($post->rueckmeldung->multiple != 1) {
            $userRueckmeldung = UserRueckmeldungen::query()
                ->where('post_id', $request->post_id)
                ->where('users_id', $user->id)
                ->first();

            if ($userRueckmeldung) {
                return response()->json([
                    'success' => false,
                    'error' => 'Already responded',
                    'message' => 'Rückmeldung bereits abgegeben',
                    'data' => $userRueckmeldung,
                ], 409);
            }
        }

        $userRueckmeldung = new UserRueckmeldungen(
            [
                'post_id' => $request->post_id,
                'users_id' => $user->id,
                'text' => $request->text,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        $userRueckmeldung->save();

        $rueckmeldung = [
            'email' => $user->email,
            'name' => $user->name,
            'text' => $request->text,
            'subject' => 'Rückmeldung zu '.$post->header,
        ];

        $empfaenger = $post->rueckmeldung->empfaenger;

        // Send a copy of the feedback to the user if requested
        if ($user->sendCopy == 1) {
            Mail::to($empfaenger)
                ->cc($user)
                ->queue(new UserRueckmeldungMail((array) $rueckmeldung));
        } else {
            Mail::to($empfaenger)
                ->queue(new UserRueckmeldungMail((array) $rueckmeldung));
        }

        return response()->json([
            'success' => true,
            'message' => 'Rückmeldung abgegeben',
            'data' => $userRueckmeldung,
        ], 200);
    }
}
