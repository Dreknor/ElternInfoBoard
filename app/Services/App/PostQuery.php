<?php

namespace App\Services\App;

use App\Model\Post;
use App\Model\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Sichtbare Beiträge als eine Abfrage (EXISTS über Gruppen und direkte Freigaben) – Grundlage für Liste, Aufgaben und Dashboard.
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
                        $q->where('external', true)
                            ->orWhere(fn ($q) => Post::constrainToRecipient($q, $user));
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
