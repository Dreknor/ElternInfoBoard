<?php

namespace App\Http\Controllers\API\V1;

use App\Mail\newFilesAddToPost;
use App\Model\Comment;
use App\Model\Poll;
use App\Model\Post;
use App\Model\UserRueckmeldungen;
use App\Services\App\FeedbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Rückmeldungen, Abfragen, Bild-Rückmeldungen, Umfragen und Kommentare zu Beiträgen.
 *
 * @group App: Nachrichten
 */
class FeedbackController extends ApiController
{
    public function __construct(private readonly FeedbackService $service) {}

    /**
     * Text-Rückmeldung geben.
     *
     * @bodyParam text string required
     */
    public function storeText(Request $request, Post $post): JsonResponse
    {
        $request->validate(['text' => 'required|string|max:5000']);
        $response = $this->service->storeText($request->user(), $post, $request->text);

        return response()->json(['data' => ['id' => $response->id], 'message' => 'Rückmeldung gesendet.'], 201);
    }

    /**
     * Text-Rückmeldung ändern (bis zur Frist, auch durch den Partner) – B-20.
     */
    public function updateText(Request $request, UserRueckmeldungen $response): JsonResponse
    {
        $request->validate(['text' => 'required|string|max:5000']);
        $this->service->updateText($request->user(), $response, $request->text);

        return response()->json(['message' => 'Rückmeldung geändert.']);
    }

    /**
     * Abfrage beantworten – B-21.
     *
     * Nicht mehrfach erlaubte Abfragen: erneutes Senden ersetzt die Antwort der Familie.
     *
     * @bodyParam answers object required option_id => Wert ("1" für angehakte Optionen). Example: {"12": "1", "13": "Allergie: Nüsse"}
     */
    public function storeAbfrage(Request $request, Post $post): JsonResponse
    {
        $request->validate(['answers' => 'required|array']);
        $response = $this->service->storeAbfrage($request->user(), $post, $request->input('answers'));

        return response()->json(['data' => ['id' => $response->id], 'message' => 'Antworten gespeichert.'], 201);
    }

    /**
     * Bestimmte Antwort einer mehrfach erlaubten Abfrage ändern.
     */
    public function updateAbfrage(Request $request, Post $post, int $response): JsonResponse
    {
        $request->validate(['answers' => 'required|array']);
        $this->service->storeAbfrage($request->user(), $post, $request->input('answers'), $response);

        return response()->json(['message' => 'Antworten geändert.']);
    }

    /**
     * Bilder zu einer Bild-Rückmeldung hochladen – B-22.
     *
     * @bodyParam files file[] required Bilder (je max. 10 MB).
     */
    public function storeImages(Request $request, Post $post): JsonResponse
    {
        $this->service->requireFeedback($request->user(), $post, 'bild');
        $request->validate([
            'files' => 'required|array|max:10',
            'files.*' => 'image|max:10240',
        ]);
        $user = $request->user();

        foreach ($request->file('files') as $file) {
            $post->addMedia($file)
                ->usingName($user->name)
                ->withCustomProperties(['uploaded_by' => $user->id])
                ->toMediaCollection('images');
        }

        if ($post->autor?->email) {
            Mail::to($post->autor->email)->queue(new newFilesAddToPost($user->name, $post->header, $post->id));
        }

        return response()->json(['message' => 'Bilder hochgeladen.'], 201);
    }

    /**
     * Eigenes hochgeladenes Bild löschen.
     */
    public function destroyImage(Request $request, Post $post, Media $media): JsonResponse
    {
        abort_unless(
            $media->model_type === Post::class && (int) $media->model_id === $post->id
                && (int) $media->getCustomProperty('uploaded_by') === $request->user()->id,
            403,
            'Sie können nur eigene Bilder löschen.'
        );
        $media->delete();

        return response()->json(['message' => 'Bild gelöscht.']);
    }

    /**
     * Umfrage mit Ergebnissen.
     */
    public function poll(Request $request, Post $post): JsonResponse
    {
        $this->authorize('view', $post);
        $poll = $post->poll()->firstOrFail();

        return response()->json(['data' => $this->presentPoll($poll, $request->user()->id)]);
    }

    /**
     * Abstimmen – B-24 (Enddatum, Höchstzahl, nur eigene Optionen).
     *
     * @bodyParam option_ids int[] required
     */
    public function vote(Request $request, Post $post): JsonResponse
    {
        $this->authorize('view', $post);
        $poll = $post->poll()->firstOrFail();
        $request->validate(['option_ids' => 'required|array|min:1', 'option_ids.*' => 'integer|distinct']);
        $user = $request->user();

        if ($poll->ends && $poll->ends->endOfDay()->isPast()) {
            return response()->json(['message' => 'Die Umfrage ist beendet.'], 410);
        }
        $optionIds = $request->input('option_ids');
        if (count($optionIds) > max(1, (int) $poll->max_number)) {
            return response()->json(['message' => "Bitte wählen Sie höchstens {$poll->max_number} Antworten."], 422);
        }
        if ($poll->options()->whereIn('id', $optionIds)->count() !== count($optionIds)) {
            return response()->json(['message' => 'Ungültige Antwortoption.'], 422);
        }

        $voted = DB::transaction(function () use ($poll, $user, $optionIds) {
            if ($poll->votes()->where('author_id', $user->id)->lockForUpdate()->exists()) {
                return false;
            }
            $poll->votes()->create(['poll_id' => $poll->id, 'author_id' => $user->id]);
            $poll->answers()->insert(array_map(fn ($id) => [
                'poll_id' => $poll->id, 'option_id' => $id, 'created_at' => now(), 'updated_at' => now(),
            ], $optionIds));

            return true;
        });
        if (! $voted) {
            return response()->json(['message' => 'Sie haben bereits abgestimmt.'], 409);
        }

        return response()->json(['data' => $this->presentPoll($poll->fresh(), $user->id)], 201);
    }

    /**
     * Kommentare (paginiert, älteste zuerst) – B-14.
     */
    public function comments(Request $request, Post $post): JsonResponse
    {
        $this->authorize('view', $post);
        $page = $post->comments()->with('creator:id,name')->orderBy('created_at')->orderBy('id')->cursorPaginate(30);
        $userId = $request->user()->id;

        return response()->json([
            'data' => collect($page->items())->map(fn (Comment $c) => [
                'id' => $c->id,
                'body' => $c->body,
                'author' => $c->creator?->name ?? 'Unbekannt',
                'own' => (int) $c->creator_id === $userId,
                'created_at' => $c->created_at?->toIso8601String(),
            ])->all(),
            'meta' => ['next_cursor' => $page->nextCursor()?->encode()],
        ]);
    }

    /**
     * Kommentar schreiben (nur wenn der Beitrag Kommentare erlaubt).
     *
     * @bodyParam body string required
     */
    public function storeComment(Request $request, Post $post): JsonResponse
    {
        $this->authorize('comment', $post);
        $request->validate(['body' => 'required|string|max:5000']);
        $comment = $post->comment(['body' => $request->body], $request->user());

        return response()->json(['data' => [
            'id' => $comment->id,
            'body' => $comment->body,
            'author' => $request->user()->name,
            'own' => true,
            'created_at' => $comment->created_at?->toIso8601String(),
        ]], 201);
    }

    public function destroyComment(Request $request, Comment $comment): JsonResponse
    {
        abort_unless((int) $comment->creator_id === $request->user()->id || $request->user()->can('delete posts'), 403);
        $comment->delete();

        return response()->json(['message' => 'Kommentar gelöscht.']);
    }

    private function presentPoll(Poll $poll, int $userId): array
    {
        $counts = $poll->answers()->selectRaw('option_id, count(*) as votes')->groupBy('option_id')->pluck('votes', 'option_id');

        return [
            'id' => $poll->id,
            'name' => $poll->poll_name,
            'description' => $poll->description,
            'ends' => $poll->ends?->toDateString(),
            'ended' => (bool) ($poll->ends && $poll->ends->endOfDay()->isPast()),
            'max_number' => max(1, (int) $poll->max_number),
            'total_votes' => $poll->votes()->count(),
            'has_voted' => $poll->votes()->where('author_id', $userId)->exists(),
            'options' => $poll->options()->orderBy('id')->get()->map(fn ($o) => [
                'id' => $o->id,
                'option' => $o->option,
                'votes' => (int) ($counts[$o->id] ?? 0),
            ])->all(),
        ];
    }
}
