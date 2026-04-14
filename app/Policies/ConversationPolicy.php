<?php

namespace App\Policies;

use App\Model\Conversation;
use App\Model\Message;
use App\Model\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ConversationPolicy
{
    use HandlesAuthorization;

    /**
     * User kann eine Konversation sehen, wenn er Mitglied ist.
     */
    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->users->contains('id', $user->id);
    }

    /**
     * User kann in einer Konversation schreiben, wenn er Mitglied ist
     * und die Konversation aktiv ist.
     */
    public function sendMessage(User $user, Conversation $conversation): bool
    {
        if (! $conversation->is_active) {
            return false;
        }

        $pivot = $conversation->users->where('id', $user->id)->first()?->pivot;
        if (! $pivot) {
            return false;
        }

        // Stummschaltung prüfen
        if ($pivot->muted_until && now()->lessThan($pivot->muted_until)) {
            return false;
        }

        return true;
    }

    /**
     * Neue Direktnachricht starten (nur wenn Direktnachrichten erlaubt).
     */
    public function createDirect(User $user): bool
    {
        return $user->can('use messenger');
    }
}

