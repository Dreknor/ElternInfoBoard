<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\API\MessengerController as LegacyMessengerController;
use App\Model\Conversation;
use App\Model\Message;
use App\Model\MessageReport;
use App\Model\User;
use App\Services\App\MessengerUnread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Messenger der App – vollständig (B-60): neue Direktchats, Anhänge, Bearbeiten, Löschen, Melden, Stummschalten.
 * Benachrichtigungslogik stammt aus dem bestehenden API-Controller.
 *
 * @group App: Messenger
 */
class MessengerController extends LegacyMessengerController
{
    /** Unterhaltungen mit ungelesenen Nachrichten (eine Zählabfrage für alle). */
    public function conversations(Request $request): JsonResponse
    {
        $user = $request->user();
        $unread = MessengerUnread::perConversation($user->id);

        $conversations = Conversation::forUser($user->id)
            ->where('is_active', true)
            ->with(['users:id,name', 'latestMessage.sender:id,name', 'group:id,name'])
            ->get()
            ->sortByDesc(fn ($c) => $c->latestMessage?->created_at ?? $c->created_at)
            ->values()
            ->map(function (Conversation $c) use ($user, $unread) {
                $pivot = $c->users->firstWhere('id', $user->id)?->pivot;

                return [
                    'id' => $c->id,
                    'type' => $c->type,
                    'display_name' => $c->displayNameFor($user->id),
                    'unread_count' => $unread[$c->id] ?? 0,
                    'muted' => (bool) ($pivot?->muted_until && now()->lessThan($pivot->muted_until)),
                    'latest_message' => $c->latestMessage ? [
                        'body' => $c->latestMessage->trashed() ? null : mb_strimwidth((string) $c->latestMessage->body, 0, 120, '…'),
                        'sender' => $c->latestMessage->sender?->name,
                        'created_at' => $c->latestMessage->created_at?->toIso8601String(),
                    ] : null,
                ];
            });

        return response()->json(['data' => $conversations, 'meta' => ['unread' => array_sum($unread)]]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json(['data' => ['unread' => MessengerUnread::total($request->user()->id)]]);
    }

    /** Nachrichten (neueste zuerst, Cursor) – markiert die Unterhaltung als gelesen. */
    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('view', $conversation);
        $user = $request->user();

        $page = $conversation->messagesVisibleTo($user->id)
            ->with(['sender:id,name', 'replyTo.sender:id,name', 'media'])
            ->orderByDesc('created_at')->orderByDesc('id')
            ->cursorPaginate(40);

        $conversation->users()->updateExistingPivot($user->id, ['last_read_at' => now()]);

        return response()->json([
            'data' => collect($page->items())->map(fn (Message $m) => $this->formatMessage($m, $user->id))->all(),
            'meta' => ['next_cursor' => $page->nextCursor()?->encode()],
        ]);
    }

    /**
     * Nachricht senden, optional mit Anhang (multipart `attachment`).
     */
    public function send(Request $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('sendMessage', $conversation);

        $key = 'messenger_api_send_'.$request->user()->id;
        if (RateLimiter::tooManyAttempts($key, 30)) {
            return response()->json(['message' => 'Zu viele Nachrichten. Bitte warten Sie einen Moment.'], 429);
        }
        RateLimiter::hit($key, 60);

        $request->validate([
            'body' => ['required_without:attachment', 'nullable', 'string', 'max:'.$this->settings->max_message_length],
            'reply_to_id' => ['nullable', 'integer', 'exists:messages,id'],
            'attachment' => ['nullable', 'file', 'max:'.($this->settings->max_file_size_mb * 1024)],
        ]);
        if ($request->hasFile('attachment') && ! $this->settings->allow_file_uploads) {
            return response()->json(['message' => 'Dateianhänge sind nicht erlaubt.'], 422);
        }

        $type = 'text';
        if ($request->hasFile('attachment')) {
            $type = str_starts_with((string) $request->file('attachment')->getMimeType(), 'image/') ? 'image' : 'file';
        }

        $message = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => (string) $request->input('body', ''),
            'type' => $type,
            'reply_to_id' => $request->reply_to_id,
        ]);
        if ($request->hasFile('attachment')) {
            $message->addMediaFromRequest('attachment')->toMediaCollection('message-attachments');
        }

        $this->notifyParticipants($conversation->load('users'), $message);
        $message->load(['sender:id,name', 'replyTo.sender:id,name', 'media']);

        return response()->json(['data' => $this->formatMessage($message, $request->user()->id)], 201);
    }

    public function update(Request $request, Message $message): JsonResponse
    {
        abort_unless($message->isEditableBy($request->user()), 403, 'Nachrichten können nur 15 Minuten lang bearbeitet werden.');
        $request->validate(['body' => ['required', 'string', 'max:'.$this->settings->max_message_length]]);
        $message->update(['body' => $request->body, 'edited_at' => now()]);

        return response()->json(['data' => $this->formatMessage($message->fresh(['sender', 'replyTo.sender', 'media']), $request->user()->id)]);
    }

    public function destroy(Request $request, Message $message): JsonResponse
    {
        abort_unless($message->isDeletableBy($request->user()), 403);
        $message->delete();

        return response()->json(['message' => 'Nachricht gelöscht.']);
    }

    /**
     * Nachricht melden.
     *
     * @bodyParam reason string required
     */
    public function report(Request $request, Message $message): JsonResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);
        abort_unless($message->isVisibleTo($request->user()), 403);
        if ($message->sender_id === $request->user()->id) {
            return response()->json(['message' => 'Eigene Nachrichten können nicht gemeldet werden.'], 422);
        }
        if (MessageReport::where('message_id', $message->id)->where('reporter_id', $request->user()->id)->whereNull('resolved_at')->exists()) {
            return response()->json(['message' => 'Sie haben diese Nachricht bereits gemeldet.'], 409);
        }
        MessageReport::create(['message_id' => $message->id, 'reporter_id' => $request->user()->id, 'reason' => $request->reason]);

        return response()->json(['message' => 'Nachricht gemeldet.'], 201);
    }

    /**
     * Stummschalten (24 h) bzw. aufheben.
     *
     * @bodyParam muted boolean required
     */
    public function mute(Request $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('view', $conversation);
        $request->validate(['muted' => 'required|boolean']);
        $conversation->users()->updateExistingPivot($request->user()->id, [
            'muted_until' => $request->boolean('muted') ? now()->addHours(24) : null,
        ]);

        return response()->json(['message' => $request->boolean('muted') ? 'Für 24 Stunden stummgeschaltet.' : 'Stummschaltung aufgehoben.']);
    }

    /**
     * Personen aus gemeinsamen Gruppen suchen (für neue Direktnachricht).
     *
     * @queryParam q string required Mindestens 2 Zeichen.
     */
    public function searchUsers(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['data' => []]);
        }
        $user = $request->user();
        $myGroups = DB::table('group_user')->where('user_id', $user->id)->pluck('group_id');
        $userIds = DB::table('group_user')->whereIn('group_id', $myGroups)->where('user_id', '!=', $user->id)->pluck('user_id')->unique();

        $users = User::whereIn('id', $userIds)
            ->where('is_active', true)
            ->where('messenger_discoverable', true)
            ->permission('use messenger')
            ->where('name', 'like', '%'.$q.'%')
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'name']);

        return response()->json(['data' => $users]);
    }

    /** Direktchat mit einer Person öffnen oder anlegen. */
    public function startDirect(Request $request, User $target): JsonResponse
    {
        abort_unless($this->settings->allow_direct_messages, 403, 'Direktnachrichten sind deaktiviert.');
        $user = $request->user();
        abort_if($target->id === $user->id, 422, 'Sie können sich nicht selbst schreiben.');

        $shared = DB::table('group_user as a')
            ->join('group_user as b', 'a.group_id', '=', 'b.group_id')
            ->where('a.user_id', $user->id)->where('b.user_id', $target->id)->exists();
        abort_unless($shared && $target->messenger_discoverable && $target->can('use messenger'), 403, 'Sie können nur Mitglieder Ihrer Gruppen anschreiben.');

        $conversation = Conversation::where('type', 'direct')->forUser($user->id)
            ->whereHas('users', fn ($q) => $q->where('users.id', $target->id))->first();

        if (! $conversation) {
            $conversation = Conversation::create(['type' => 'direct', 'created_by' => $user->id]);
            $conversation->users()->syncWithoutDetaching([$user->id, $target->id]);
        }

        return response()->json(['data' => ['id' => $conversation->id, 'display_name' => $target->name]], 201);
    }

    /** Anhang herunterladen (Token-geschützt, statt Web-Route mit Session). */
    public function attachment(Request $request, Message $message): BinaryFileResponse
    {
        abort_unless($message->isVisibleTo($request->user()), 403);
        $media = $message->getFirstMedia('message-attachments');
        abort_unless($media && file_exists($media->getPath()), 404);

        return response()->file($media->getPath(), [
            'Content-Type' => $media->mime_type,
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    protected function formatMessage(Message $message, int $currentUserId): array
    {
        $media = $message->getFirstMedia('message-attachments');

        return [
            'id' => $message->id,
            'body' => $message->body,
            'type' => $message->type,
            'is_own' => $message->sender_id === $currentUserId,
            'editable' => $message->sender_id === $currentUserId && $message->created_at?->diffInMinutes(now()) <= 15,
            'edited_at' => $message->edited_at?->toIso8601String(),
            'created_at' => $message->created_at?->toIso8601String(),
            'sender' => ['id' => $message->sender?->id, 'name' => $message->sender?->name],
            'reply_to' => $message->replyTo ? [
                'id' => $message->replyTo->id,
                'body' => $message->replyTo->trashed() ? '[gelöscht]' : mb_substr((string) $message->replyTo->body, 0, 100),
                'sender' => $message->replyTo->sender?->name,
            ] : null,
            'attachment' => $media ? [
                'url' => route('api.v1.messenger.attachment', $message),
                'filename' => $media->file_name,
                'mime' => $media->mime_type,
                'size' => $media->size,
            ] : null,
        ];
    }
}
