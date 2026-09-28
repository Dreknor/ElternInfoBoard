<?php

namespace App\Http\Controllers\API\V1;

use App\Model\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group App: Benachrichtigungen
 */
class NotificationController extends ApiController
{
    /**
     * Benachrichtigungen (neueste zuerst, paginiert) mit Ziel für die App – B-05.
     *
     * @queryParam unread boolean Nur ungelesene. Example: 1
     */
    public function index(Request $request): JsonResponse
    {
        $page = $request->user()->notifications()
            ->when($request->boolean('unread'), fn ($q) => $q->where('read', false))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate(30);

        return response()->json([
            'data' => collect($page->items())->map(fn (Notification $n) => [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'message' => $n->message,
                'read' => (bool) $n->read,
                'important' => (bool) $n->important,
                'created_at' => $n->created_at?->toIso8601String(),
                'target' => $n->target(),
            ])->all(),
            'meta' => [
                'next_cursor' => $page->nextCursor()?->encode(),
                'unread' => $request->user()->notifications()->where('read', false)->count(),
            ],
        ]);
    }

    /** Genau eine Benachrichtigung als gelesen markieren. */
    public function read(Request $request, int $id): JsonResponse
    {
        $request->user()->notifications()->where('id', $id)->update(['read' => true]);

        return response()->json(['message' => 'Gelesen.']);
    }

    public function readAll(Request $request): JsonResponse
    {
        $request->user()->notifications()->where('read', false)->update(['read' => true]);

        return response()->json(['message' => 'Alle gelesen.']);
    }
}
