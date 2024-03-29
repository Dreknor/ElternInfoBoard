<?php

namespace App\Http\View\Composers;

use App\Model\Post;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class NachrichtenComposer
{
    public function compose($view): void
    {
        $expire = 1;

        $nachrichten = Cache::remember('posts_'.auth()->id(), $expire, function () {
            $user = auth()->user();

            if (! $user->can('view all')) {
                $Nachrichten = $user->postsNotArchived()
                    ->distinct()
                    ->where('external', 0)
                    ->orderByDesc('sticky')
                    ->orderByDesc('updated_at')
                    ->whereDate('archiv_ab', '>', $user->created_at)
                    ->with('media', 'autor', 'groups')
                    ->withCount('users')->get();

                if ($user->can('create posts')) {
                    $eigenePosts = Post::query()
                        ->where('author', $user->id)
                        ->whereDate('archiv_ab', '>', Carbon::now()->startOfDay())
                        ->where('external', 0)
                        ->get();
                    $Nachrichten = $Nachrichten->concat($eigenePosts);
                }
            } else {

                $Nachrichten = Post::whereDate('archiv_ab', '>', Carbon::now()->startOfDay())
                    ->where('external', 0)
                    ->orderByDesc('sticky')
                    ->orderByDesc('updated_at')
                    ->with('media', 'autor', 'groups', 'rueckmeldung')
                    ->withCount('users')
                    ->get();
/*
                if (!auth()->user()->can('view protected')) {
                    $Nachrichten = $Nachrichten->filter(function ($nachricht) {
                        $unprotected = 0;
                        foreach ($nachricht->groups as $group) {
                            if ($group->protected == 0) {
                                $unprotected++;
                            }
                        }
                        if (($unprotected > 0) or $nachricht->author == auth()->id()) {
                            return $nachricht;
                        }
                    });
                }
*/
            }

            $Nachrichten = $Nachrichten->unique('id');
            $Nachrichten = $Nachrichten->load('userRueckmeldung');
            $Nachrichten = $Nachrichten->load('reactions');
            $Nachrichten = $Nachrichten->load('poll', 'poll.options', 'poll.votes', 'poll.answers');

            return $Nachrichten->paginate(30);
        });
        $view->with([
            'nachrichten' => $nachrichten,
            'user' => auth()->user(),
        ]);
    }
}
