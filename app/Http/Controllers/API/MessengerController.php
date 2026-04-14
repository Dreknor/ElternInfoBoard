<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Model\Conversation;
use App\Model\Message;
use App\Settings\MessengerSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;

class MessengerController extends Controller
{
    public function __construct(private readonly MessengerSetting $settings) {}

    /**
     * Alle Konversationen des eingeloggten Users.
     *
     * @group Messenger
     */
    public function conversations(Request $request): JsonResponse
    {
        $user = auth()->user();

        $conversations = Conversation::forUser($user->id)
            ->where('is_active', true)
            ->with(['users', 'latestMessage.sender', 'group'])
            ->get()
            ->sortByDesc(fn ($c) => $c->latestMessage?->created_at)
            ->values()
            ->map(function (Conversation $conv) use ($user) {
                return [
                    'id'            => $conv->id,
                    'type'          => $conv->type,
                    'display_name'  => $conv->displayNameFor($user->id),
                    'unread_count'  => $conv->unreadCountFor($user->id),
                    'latest_message' => $conv->latestMessage ? [
                        'body'       => $conv->latestMessage->body,
                        'sender'     => $conv->latestMessage->sender?->name,
                        'created_at' => $conv->latestMessage->created_at?->toIso8601String(),
                    ] : null,
                    'participants'  => $conv->users->map(fn ($u) => [
                        'id'   => $u->id,
                        'name' => $u->name,
                    ]),
                ];
            });

        return response()->json(['success' => true, 'data' => $conversations]);
    }

    /**
     * Nachrichten einer Konversation abrufen.
     *
     * @group Messenger
     */
    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('view', $conversation);

        $user = auth()->user();

        $messages = $conversation->messages()
            ->with(['sender', 'replyTo.sender'])
            ->latest()
            ->paginate(50);

        // Als gelesen markieren
        $conversation->users()->updateExistingPivot($user->id, ['last_read_at' => now()]);

        return response()->json([
            'success' => true,
            'data'    => $messages->map(fn (Message $m) => $this->formatMessage($m, $user->id)),
            'meta'    => [
                'current_page' => $messages->currentPage(),
                'last_page'    => $messages->lastPage(),
                'total'        => $messages->total(),
            ],
        ]);
    }

    /**
     * Nachricht senden.
     *
     * @group Messenger
     */
    public function send(Request $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('sendMessage', $conversation);

        // Rate-Limiting
        $key = 'messenger_api_send_' . auth()->id();
        if (RateLimiter::tooManyAttempts($key, 30)) {
            return response()->json(['success' => false, 'message' => 'Zu viele Nachrichten.'], 429);
        }
        RateLimiter::hit($key, 60);

        $request->validate([
            'body'        => ['required', 'string', 'max:' . $this->settings->max_message_length],
            'reply_to_id' => ['nullable', 'integer', 'exists:messages,id'],
        ]);

        $message = $conversation->messages()->create([
            'sender_id'   => auth()->id(),
            'body'        => $request->body,
            'type'        => 'text',
            'reply_to_id' => $request->reply_to_id,
        ]);

        $message->load(['sender', 'replyTo.sender']);

        return response()->json([
            'success' => true,
            'data'    => $this->formatMessage($message, auth()->id()),
        ], 201);
    }

    /**
     * Konversation als gelesen markieren.
     *
     * @group Messenger
     */
    public function markRead(Request $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('view', $conversation);

        $conversation->users()->updateExistingPivot(auth()->id(), ['last_read_at' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * Gesamtzahl ungelesener Nachrichten über alle Konversationen.
     *
     * @group Messenger
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $user = auth()->user();

        $total = Conversation::forUser($user->id)
            ->where('is_active', true)
            ->with(['users', 'messages'])
            ->get()
            ->sum(fn ($conv) => $conv->unreadCountFor($user->id));

        return response()->json(['success' => true, 'unread_count' => $total]);
    }

    // ── Hilfsmethoden ────────────────────────────────────────────

    private function formatMessage(Message $message, int $currentUserId): array
    {
        return [
            'id'         => $message->id,
            'body'       => $message->body,
            'type'       => $message->type,
            'is_own'     => $message->sender_id === $currentUserId,
            'edited_at'  => $message->edited_at?->toIso8601String(),
            'created_at' => $message->created_at?->toIso8601String(),
            'sender'     => [
                'id'   => $message->sender?->id,
                'name' => $message->sender?->name,
            ],
            'reply_to'   => $message->replyTo ? [
                'id'     => $message->replyTo->id,
                'body'   => $message->replyTo->trashed() ? '[gelöscht]' : mb_substr($message->replyTo->body, 0, 100),
                'sender' => $message->replyTo->sender?->name,
            ] : null,
            'attachment' => $message->getFirstMedia('message-attachments') ? [
                'url'      => $message->getFirstMediaUrl('message-attachments'),
                'filename' => $message->getFirstMedia('message-attachments')->file_name,
                'mime'     => $message->getFirstMedia('message-attachments')->mime_type,
            ] : null,
        ];
    }
}

