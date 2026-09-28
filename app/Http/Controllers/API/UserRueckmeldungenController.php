<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mail\UserRueckmeldung as UserRueckmeldungMail;
use App\Model\Post;
use App\Model\UserRueckmeldungen;
use App\Services\Rueckmeldungen\RueckmeldungStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * Class UserRueckmeldungenController
 *
 * Controller for handling user feedback (Rückmeldungen) related API requests.
 */
class UserRueckmeldungenController extends Controller
{
    public function __construct(private readonly RueckmeldungStatusService $status) {}

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Services\Rueckmeldungen\RueckmeldungTarget>  $targets
     */
    private function targetsPayload($targets): array
    {
        return $targets->map(fn ($target) => [
            'type' => $target->isChild() ? 'child' : $target->scope,
            'child_id' => $target->child?->id,
            'child_name' => $target->label(),
            'answered' => $target->isAnswered(),
            'answered_by' => $target->answeredBy(),
            'can_answer' => $target->canAnswer,
        ])->values()->all();
    }

    /**
     * Get existing user feedback for a post.
     *
     * @group Rückmeldungen
     *
     * @urlParam post_id integer required The ID of the post. Example: 1
     *
     * @responseField scope string Wirksamer Scope der Rückmeldung: child, family oder person.
     * @responseField targets array Antwortziele des Users: type (child|family|person), child_id, child_name, answered, answered_by, can_answer.
     * @responseField data array Sichtbare Antworten (eigene Kinder bzw. Familie/Person).
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

        // Antwortziele (pro Kind bzw. Familie/Person) und sichtbare Antworten
        $targets = $this->status->targetsFor($user, $post);

        $rueckmeldungen = UserRueckmeldungen::query()
            ->whereIn('id', $targets->flatMap(fn ($t) => $t->answers->pluck('id'))->all())
            ->with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'scope' => $this->status->effectiveScope($post->rueckmeldung),
            'targets' => $this->targetsPayload($targets),
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
     * @bodyParam child_id integer Kind, für das geantwortet wird (Pflicht bei Rückmeldung pro Kind mit mehreren Kindern; ohne Angabe wird bei genau einem Kind dieses verwendet, sonst 422). Nur Sorgeberechtigte (403). Example: 5
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
            'child_id' => 'nullable|integer|exists:children,id',
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

        // Antwortziel prüfen (pro Kind nur Sorgeberechtigte – E7). Alte Apps ohne
        // child_id: genau ein Kind → dieses, mehrere → 422, ohne Sorgerecht → 403.
        $resolved = $this->status->resolveTarget($user, $post, $request->integer('child_id') ?: null);
        if ($resolved['error'] !== null) {
            return response()->json([
                'success' => false,
                'error' => match ($resolved['status']) {
                    409 => 'Already responded',
                    422 => 'Child required',
                    default => 'Not allowed',
                },
                'message' => $resolved['error'],
                'data' => $resolved['status'] === 409 ? $resolved['target']?->answers->first() : null,
            ], $resolved['status']);
        }
        $target = $resolved['target'];

        $userRueckmeldung = new UserRueckmeldungen(
            [
                'post_id' => $request->post_id,
                'users_id' => $user->id,
                'child_id' => $target->child?->id,
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
            'subject' => 'Rückmeldung zu '.$post->header.($target->isChild() ? ' für '.$target->label() : ''),
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

        // Eigene Rückmeldung oder die eines Familienmitglieds
        if (! $this->status->mayEdit($user, $userRueckmeldung)) {
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
