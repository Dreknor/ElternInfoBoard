<?php

namespace App\Policies;

use App\Model\Post;
use App\Model\User;

/**
 * Zugriff auf Beiträge (B-13) – eine EXISTS-Abfrage statt `$post->users` (lädt alle Gruppenmitglieder).
 */
class PostPolicy
{
    public function view(User $user, Post $post): bool
    {
        if ($user->can('view all') || (int) $post->author === $user->id) {
            return true;
        }
        if (! $post->released) {
            return false;
        }
        // Externe Angebote sind für alle angemeldeten Nutzer sichtbar (Web: /external).
        if ($post->external) {
            return true;
        }

        // Über eine Gruppe oder als direkt freigegebene Person
        return $post->hasRecipient($user);
    }

    public function react(User $user, Post $post): bool
    {
        return $post->reactable && $this->view($user, $post);
    }

    public function comment(User $user, Post $post): bool
    {
        return $this->view($user, $post)
            && ($post->rueckmeldung?->type === 'commentable' || (bool) $post->rueckmeldung?->commentable);
    }
}
