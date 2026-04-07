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
     * Get existing user feedback for a post.
     *
     * @group Rückmeldungen
     *
     * @urlParam post_id integer required The ID of the post. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "post_id": 1,
     *       "users_id": 1,
     *       "text": "Ich nehme teil",
     *       "created_at": "2026-02-11T10:00:00.000000Z",
     *       "updated_at": "2026-02-11T10:00:00.000000Z",
     *       "user": {
     *         "id": 1,
     *         "name": "Max Mustermann",
     *         "email": "max@example.com"
     *       }
     *     }
     *   ]
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
     *   "error": "User not allowed",
     *   "message": "Keine Berechtigung für diesen Beitrag"
     * }
     *
     * @authenticated
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, $post_id)
    {
        $post = Post::query()->find($post_id);
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

        // Collect user IDs: authenticated user and sorgeberechtigter2
        $userIds = [$user->id];
        if (! is_null($user->sorg2)) {
            $userIds[] = $user->sorg2;
        }

        // Get all feedback from the user and sorgeberechtigter2 for this post
        $rueckmeldungen = UserRueckmeldungen::query()
            ->where('post_id', $post_id)
            ->whereIn('users_id', $userIds)
            ->with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rueckmeldungen,
        ], 200);
    }

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

    /**
     * Update an existing user feedback.
     *
     * @group Rückmeldungen
     *
     * @urlParam id integer required The ID of the user feedback to update. Example: 1
     *
     * @bodyParam text string required The updated feedback text. Example: "Ich nehme doch nicht teil"
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Rückmeldung aktualisiert",
     *   "data": {
     *     "id": 1,
     *     "post_id": 1,
     *     "users_id": 1,
     *     "text": "Ich nehme doch nicht teil",
     *     "created_at": "2026-02-11T10:00:00.000000Z",
     *     "updated_at": "2026-02-18T14:30:00.000000Z"
     *   }
     * }
     *
     * @response 404 {
     *   "success": false,
     *   "error": "Feedback not found",
     *   "message": "Rückmeldung nicht gefunden"
     * }
     *
     * @response 403 {
     *   "success": false,
     *   "error": "Not authorized",
     *   "message": "Sie sind nicht berechtigt, diese Rückmeldung zu ändern"
     * }
     *
     * @response 410 {
     *   "success": false,
     *   "error": "Feedback deadline passed",
     *   "message": "Die Frist für Rückmeldungen ist abgelaufen"
     * }
     *
     * @authenticated
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'text' => 'required|string|max:5000',
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'User not found',
                'message' => 'Benutzer nicht gefunden'
            ], 404);
        }

        $userRueckmeldung = UserRueckmeldungen::query()
            ->with('nachricht.rueckmeldung')
            ->find($id);

        if (!$userRueckmeldung) {
            return response()->json([
                'success' => false,
                'error' => 'Feedback not found',
                'message' => 'Rückmeldung nicht gefunden'
            ], 404);
        }

        // Check if the user owns this feedback or is the sorgeberechtigter2
        $isOwner = $userRueckmeldung->users_id === $user->id;
        $isSorgeberechtigter = !is_null($user->sorg2) && $userRueckmeldung->users_id === $user->sorg2;

        if (!$isOwner && !$isSorgeberechtigter) {
            return response()->json([
                'success' => false,
                'error' => 'Not authorized',
                'message' => 'Sie sind nicht berechtigt, diese Rückmeldung zu ändern'
            ], 403);
        }

        $post = $userRueckmeldung->nachricht;

        if (!$post) {
            return response()->json([
                'success' => false,
                'error' => 'Post not found',
                'message' => 'Beitrag nicht gefunden'
            ], 404);
        }

        // Check if user has access to the post
        if (!$post->users->contains($user)) {
            return response()->json([
                'success' => false,
                'error' => 'User not allowed',
                'message' => 'Keine Berechtigung für diesen Beitrag'
            ], 403);
        }

        $rueckmeldung = $post->rueckmeldung;

        if (!$rueckmeldung) {
            return response()->json([
                'success' => false,
                'error' => 'Feedback not enabled',
                'message' => 'Rückmeldung für diesen Beitrag nicht aktiviert'
            ], 404);
        }

        // Check if the feedback deadline has passed
        if ($rueckmeldung->active === false) {
            return response()->json([
                'success' => false,
                'error' => 'Feedback deadline passed',
                'message' => 'Die Frist für Rückmeldungen ist abgelaufen'
            ], 410);
        }

        // Update the feedback
        $userRueckmeldung->text = $request->text;
        $userRueckmeldung->updated_at = now();
        $userRueckmeldung->save();

        // Send notification email about the update
        $rueckmeldungData = [
            'email' => $user->email,
            'name' => $user->name,
            'text' => $request->text,
            'subject' => 'Aktualisierte Rückmeldung zu ' . $post->header,
        ];

        $empfaenger = $rueckmeldung->empfaenger;

        if ($user->sendCopy == 1) {
            Mail::to($empfaenger)
                ->cc($user)
                ->queue(new UserRueckmeldungMail((array) $rueckmeldungData));
        } else {
            Mail::to($empfaenger)
                ->queue(new UserRueckmeldungMail((array) $rueckmeldungData));
        }

        return response()->json([
            'success' => true,
            'message' => 'Rückmeldung aktualisiert',
            'data' => $userRueckmeldung,
        ], 200);
    }
}
