<?php

namespace App\Policies;

use App\Model\Post;
use App\Model\User;
use Illuminate\Support\Facades\DB;

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

        return DB::table('group_post')
            ->join('group_user', 'group_post.group_id', '=', 'group_user.group_id')
            ->where('group_post.post_id', $post->id)
            ->where('group_user.user_id', $user->id)
            ->exists();
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
