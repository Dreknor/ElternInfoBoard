<?php

namespace App\Services\App;

use App\Model\Post;
use App\Model\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Sichtbare Beiträge als eine Abfrage (EXISTS über Gruppen) – Grundlage für Liste, Aufgaben und Dashboard.
 */
class PostQuery
{
    public static function visibleTo(User $user): Builder
    {
        $query = Post::query();

        if ($user->can('view all')) {
            return $query->where(fn ($q) => $q->where('released', 1)->orWhere('author', $user->id));
        }

        return $query->where(function ($q) use ($user) {
            $q->where('author', $user->id)
                ->orWhere(function ($q) use ($user) {
                    $q->where('released', 1)->where(function ($q) use ($user) {
                        $q->where('external', true)->orWhereExists(function ($sub) use ($user) {
                            $sub->selectRaw(1)
                                ->from('group_post')
                                ->join('group_user', 'group_post.group_id', '=', 'group_user.group_id')
                                ->whereColumn('group_post.post_id', 'posts.id')
                                ->where('group_user.user_id', $user->id);
                        });
                    });
                });
        });
    }

    /** Aktuelle (nicht archivierte) Beiträge. */
    public static function current(User $user): Builder
    {
        return self::visibleTo($user)->where(fn ($q) => $q->whereNull('archiv_ab')->orWhere('archiv_ab', '>', now()));
    }
}
