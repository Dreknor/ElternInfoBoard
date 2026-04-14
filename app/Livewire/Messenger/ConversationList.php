<?php

namespace App\Livewire\Messenger;

use App\Model\Conversation;
use Livewire\Component;

class ConversationList extends Component
{
    public int $conversationId = 0;

    protected $listeners = ['conversationSelected' => 'selectConversation'];

    public function selectConversation(int $id): void
    {
        $this->conversationId = $id;
        $this->dispatch('openConversation', id: $id);
    }

    public function render()
    {
        $user = auth()->user();

        $conversations = Conversation::forUser($user->id)
            ->where('is_active', true)
            ->with(['users', 'latestMessage.sender', 'group'])
            ->get()
            ->sortByDesc(fn ($c) => $c->latestMessage?->created_at)
            ->values();

        $conversations->each(function (Conversation $conv) use ($user) {
            $conv->unread_count = $conv->unreadCountFor($user->id);
            $conv->display_name = $conv->displayNameFor($user->id);
        });

        return view('livewire.messenger.conversation-list', [
            'conversations' => $conversations,
        ]);
    }
}

