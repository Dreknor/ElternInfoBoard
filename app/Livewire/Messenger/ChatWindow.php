<?php

namespace App\Livewire\Messenger;

use App\Model\Conversation;
use App\Model\Message;
use App\Model\Notification;
use App\Notifications\MessengerPushNotification;
use App\Settings\MessengerSetting;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class ChatWindow extends Component
{
    public int $conversationId;
    public string $newMessage = '';
    public ?int $replyToId   = null;

    protected $listeners = ['openConversation' => 'loadConversation'];

    public function mount(int $conversationId = 0): void
    {
        $this->conversationId = $conversationId;
    }

    public function loadConversation(int $id): void
    {
        $this->conversationId = $id;
        $this->newMessage     = '';
        $this->replyToId      = null;
        $this->markAsRead();
    }

    /** Wird bei wire:poll.5s aufgerufen */
    public function refresh(): void
    {
        $this->markAsRead();
    }

    public function setReplyTo(int $messageId): void
    {
        $this->replyToId = $messageId;
    }

    public function cancelReply(): void
    {
        $this->replyToId = null;
    }

    public function sendMessage(): void
    {
        if ($this->conversationId === 0) {
            return;
        }

        $settings = app(MessengerSetting::class);

        $this->validate([
            'newMessage' => ['required', 'string', 'max:' . $settings->max_message_length],
        ]);

        $conversation = Conversation::find($this->conversationId);
        if (! $conversation || ! Gate::check('sendMessage', $conversation)) {
            $this->addError('newMessage', 'Keine Berechtigung zum Senden.');
            return;
        }

        Message::create([
            'conversation_id' => $this->conversationId,
            'sender_id'       => auth()->id(),
            'body'            => $this->newMessage,
            'type'            => 'text',
            'reply_to_id'     => $this->replyToId,
        ]);

        // Andere Teilnehmer über neue Nachricht informieren
        $message = $conversation->messages()->latest()->first();
        $this->notifyParticipants($conversation, $message);

        $this->newMessage = '';
        $this->replyToId  = null;
        $this->markAsRead();
    }

    public function render()
    {
        if ($this->conversationId === 0) {
            return view('livewire.messenger.chat-window', [
                'conversation' => null,
                'messages'     => collect(),
                'replyMessage' => null,
            ]);
        }

        $conversation = Conversation::with('users')
            ->find($this->conversationId);

        if (! $conversation || ! Gate::check('view', $conversation)) {
            return view('livewire.messenger.chat-window', [
                'conversation' => null,
                'messages'     => collect(),
                'replyMessage' => null,
            ]);
        }

        $messages = $conversation->messages()
            ->with(['sender', 'replyTo.sender'])
            ->latest()
            ->take(100)
            ->get()
            ->reverse()
            ->values();

        $replyMessage = $this->replyToId
            ? Message::with('sender')->find($this->replyToId)
            : null;

        return view('livewire.messenger.chat-window', compact(
            'conversation',
            'messages',
            'replyMessage',
        ));
    }

    private function markAsRead(): void
    {
        if ($this->conversationId === 0) {
            return;
        }

        $user         = auth()->user();
        $conversation = Conversation::find($this->conversationId);
        if (! $conversation) {
            return;
        }

        // Pivot aktualisieren (Messenger-Ungelesen-Zähler)
        $conversation->users()->updateExistingPivot($user->id, ['last_read_at' => now()]);

        // In-App-Benachrichtigungen (Glocke) für diese Konversation als gelesen markieren
        $url = route('messenger.show', $conversation);
        Notification::where('user_id', $user->id)
            ->where('type', 'messenger')
            ->where('url', $url)
            ->where('read', false)
            ->update(['read' => true]);
    }

    private function notifyParticipants(Conversation $conversation, ?Message $message): void
    {
        if (! $message) {
            return;
        }

        /** @var \App\Model\User|null $sender */
        $sender = auth()->user();
        if (! $sender) {
            return;
        }

        $url = route('messenger.show', $conversation);

        $recipients = $conversation->users
            ->where('id', '!=', $sender->id)
            ->filter(function ($u) use ($url) {
                $pivot = $u->pivot;

                // Stumm geschaltete Nutzer überspringen
                if ($pivot->muted_until && now()->lessThan($pivot->muted_until)) {
                    return false;
                }

                // Nutzer überspringen, die den Chat gerade aktiv lesen (last_read_at < 30 Sek.)
                if ($pivot->last_read_at && now()->diffInSeconds($pivot->last_read_at) < 30) {
                    return false;
                }

                return true;
            });

        if ($recipients->isEmpty()) {
            return;
        }

        $title   = 'Neue Nachricht: ' . $conversation->displayNameFor($sender->id);
        $snippet = mb_substr($message->body, 0, 80);

        // In-App-Benachrichtigung (Typ 'messenger' — konsistent mit MessengerController)
        // updateExisting=true: bestehende ungelesene Benachrichtigung für diese Konversation wird
        // aktualisiert statt übersprungen, damit der Nutzer auch bei mehreren ungelesenen Nachrichten
        // immer die neueste Vorschau sieht und der Benachrichtigungs-Zähler korrekt bleibt.
        $conversation->notify($recipients, $title, "{$sender->name}: {$snippet}", false, $url, 'messenger', 'fas fa-comments', true);

        // WebPush für Nutzer mit aktiven Push-Subscriptions
        foreach ($recipients as $participant) {
            try {
                if ($participant->pushSubscriptions()->exists()) {
                    $participant->notify(
                        new MessengerPushNotification($title, "{$sender->name}: {$snippet}", $url)
                    );
                }
            } catch (\Exception $e) {
                Log::warning("Messenger WebPush fehlgeschlagen für User {$participant->id}: " . $e->getMessage());
            }
        }
    }
}


