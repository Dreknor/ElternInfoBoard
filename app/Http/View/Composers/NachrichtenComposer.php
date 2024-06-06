<?php

namespace App\Http\View\Composers;

use App\Model\Post;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class NachrichtenComposer
{
    public function compose($view): void
    {
        $expire = 30;

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

            }

            $Nachrichten = $Nachrichten->unique('id');
            $Nachrichten = $Nachrichten->load('userRueckmeldung');
            $Nachrichten = $Nachrichten->load('reactions');
            $Nachrichten = $Nachrichten->load('poll', 'poll.options', 'poll.votes', 'poll.answers');

            return $Nachrichten;
        });
        $view->with([
            'nachrichten' => $nachrichten,
            'user' => auth()->user(),
        ]);
    }
}
