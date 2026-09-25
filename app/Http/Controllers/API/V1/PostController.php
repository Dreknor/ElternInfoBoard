<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\NachrichtenController;
use App\Http\Controllers\PostReportController;
use App\Model\Post;
use App\Model\PostReport;
use App\Model\ReadReceipts;
use App\Services\App\PostPresenter;
use App\Services\App\PostQuery;
use App\Services\App\TodoService;
use DevDojo\LaravelReactions\Models\Reaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group App: Nachrichten
 */
class PostController extends ApiController
{
    /**
     * Beiträge (paginiert, schlank) – B-10, B-16.
     *
     * @queryParam filter string `todo` = nur Beiträge mit offener Aufgabe. Example: todo
     * @queryParam archived boolean Archivierte Beiträge. Example: 1
     * @queryParam month string Archiv-Monat `YYYY-MM`. Example: 2026-06
     * @queryParam external boolean Nur externe Angebote. Example: 1
     * @queryParam q string Volltextsuche in Titel und Text.
     * @queryParam cursor string Fortsetzung aus `meta.next_cursor`.
     */
    public function index(Request $request, TodoService $todos): JsonResponse
    {
        $request->validate([
            'filter' => 'nullable|in:todo',
            'month' => ['nullable', 'regex:/^\d{4}-\d{2}$/'],
            'q' => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:5|max:50',
        ]);
        $user = $request->user();

        $query = $request->boolean('archived')
            ? PostQuery::visibleTo($user)->whereNotNull('archiv_ab')->where('archiv_ab', '<=', now())
            : PostQuery::current($user);

        if ($request->boolean('external')) {
            $query->where('external', true);
        }
        if ($month = $request->input('month')) {
            [$y, $m] = explode('-', $month);
            $query->whereYear('posts.updated_at', $y)->whereMonth('posts.updated_at', $m);
        }
        if ($q = $request->input('q')) {
            $query->where(fn ($w) => $w->where('header', 'like', "%{$q}%")->orWhere('news', 'like', "%{$q}%"));
        }
        if ($request->input('filter') === 'todo') {
            $ids = collect($todos->forUser($user))->where('target.type', 'post')->pluck('target.id');
            $query->whereIn('posts.id', $ids);
        }

        $page = $query
            ->select('posts.*')
            ->with(['media', 'autor:id,name', 'poll:id,post_id'])
            ->withCount('comments')
            ->orderByDesc('sticky')
            ->orderByDesc('posts.updated_at')
            ->orderByDesc('posts.id')
            ->cursorPaginate($request->integer('per_page', 20));

        return response()->json([
            'data' => (new PostPresenter($user))->list(collect($page->items())),
            'meta' => ['next_cursor' => $page->nextCursor()?->encode()],
        ]);
    }

    /**
     * Einzelner Beitrag mit bereinigtem HTML, Anhängen, Rückmeldung und eigenen Antworten.
     */
    public function show(Request $request, Post $post): JsonResponse
    {
        $this->authorize('view', $post);

        return response()->json(['data' => (new PostPresenter($request->user()))->detail($post)]);
    }

    /**
     * Lesebestätigung (B-13).
     */
    public function read(Request $request, Post $post): JsonResponse
    {
        $this->authorize('view', $post);

        $receipt = ReadReceipts::firstOrCreate(
            ['post_id' => $post->id, 'user_id' => $request->user()->id],
            ['confirmed_at' => now()]
        );
        if (! $receipt->confirmed_at) {
            $receipt->update(['confirmed_at' => now()]);
        }

        return response()->json(['message' => 'Lesebestätigung gespeichert.']);
    }

    /**
     * Reagieren (ersetzt eine bestehende eigene Reaktion).
     *
     * @bodyParam reaction string required Example: like
     */
    public function react(Request $request, Post $post): JsonResponse
    {
        $this->authorize('react', $post);
        $request->validate(['reaction' => 'required|string|exists:reactions,name']);

        $user = $request->user();
        $reaction = Reaction::where('name', $request->reaction)->firstOrFail();
        if ($post->userReaction($user) !== $reaction->name) {
            $user->reactTo($post, $reaction); // entfernt ggf. die alte und setzt die neue
        }

        return response()->json(['message' => 'Reaktion gespeichert.']);
    }

    /**
     * Eigene Reaktion entfernen (B-13).
     */
    public function unreact(Request $request, Post $post): JsonResponse
    {
        $this->authorize('view', $post);
        $user = $request->user();
        $current = $post->reactions()->where('responder_id', $user->id)->where('responder_type', get_class($user))->first();
        if ($current) {
            $user->reactTo($post, $current); // gleiche Reaktion erneut = entfernen
        }

        return response()->json(['message' => 'Reaktion entfernt.']);
    }

    /**
     * Beitrag melden (B-15).
     *
     * @bodyParam reason string required
     */
    public function report(Request $request, Post $post): JsonResponse
    {
        $this->authorize('view', $post);
        $request->validate(['reason' => 'required|string|max:500']);
        $user = $request->user();

        if ((int) $post->author === $user->id) {
            return response()->json(['message' => 'Eigene Beiträge können nicht gemeldet werden.'], 422);
        }
        if (PostReport::where('post_id', $post->id)->where('reporter_id', $user->id)->whereNull('resolved_at')->exists()) {
            return response()->json(['message' => 'Sie haben diesen Beitrag bereits gemeldet.'], 409);
        }

        $report = PostReport::create(['post_id' => $post->id, 'reporter_id' => $user->id, 'reason' => $request->reason]);
        app(PostReportController::class)->notifyAdmins($report, $post, $user);

        return response()->json(['message' => 'Beitrag wurde gemeldet.'], 201);
    }

    /**
     * Beitrag als PDF (wie im Web).
     */
    public function pdf(Post $post): Response
    {
        $this->authorize('view', $post);

        return app(NachrichtenController::class)->downloadPdf($post);
    }
}
