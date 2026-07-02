<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Model\Conversation;
use App\Model\Message;
use App\Notifications\MessengerPushNotification;
use App\Settings\MessengerSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
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

        $messages = $conversation->messagesVisibleTo($user->id)
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

        // Andere Teilnehmer über neue Nachricht informieren
        // ($conversation->users wurde bereits durch Gate::authorize geladen)
        $this->notifyParticipants($conversation, $message);

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

    private function notifyParticipants(Conversation $conversation, Message $message): void
    {
        $sender = auth()->user();
        if (! $sender) {
            return;
        }

        $url = route('messenger.show', $conversation);

        // Alle Teilnehmer außer dem Sender und stummgeschalteten Nutzern
        $allRecipients = $conversation->users
            ->where('id', '!=', $sender->id)
            ->filter(function ($user) {
                $pivot = $user->pivot;
                if ($pivot->muted_until && now()->lessThan($pivot->muted_until)) {
                    return false;
                }
                return true;
            });

        if ($allRecipients->isEmpty()) {
            return;
        }

        $title   = 'Neue Nachricht: ' . $conversation->displayNameFor($sender->id);
        $snippet = mb_substr($message->body, 0, 80) . (mb_strlen($message->body) > 80 ? '…' : '');

        // In-App-Benachrichtigung für ALLE nicht-stummgeschalteten Teilnehmer
        $conversation->notify($allRecipients, $title, "{$sender->name}: {$snippet}", false, $url, 'messenger', 'fas fa-comments', true);

        // WebPush nur für Nutzer, die den Chat NICHT gerade aktiv lesen (last_read_at > 30 Sek.)
        $pushRecipients = $allRecipients->filter(function ($user) {
            $pivot = $user->pivot;
            if ($pivot->last_read_at && now()->diffInSeconds($pivot->last_read_at) < 30) {
                return false;
            }
            return true;
        });

        // Cooldown: max. eine Push-Benachrichtigung pro Nutzer/Konversation alle 10 Minuten
        $pushCooldownMinutes = 10;

        foreach ($pushRecipients as $participant) {
            try {
                if ($participant->pushSubscriptions()->exists()) {
                    $cacheKey = "messenger_push_{$participant->id}_{$conversation->id}";
                    if (Cache::add($cacheKey, true, now()->addMinutes($pushCooldownMinutes))) {
                        $participant->notify(
                            new MessengerPushNotification($title, "{$sender->name}: {$snippet}", $url)
                        );
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Messenger WebPush (API) fehlgeschlagen für User {$participant->id}: " . $e->getMessage());
            }
        }
    }

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

