<?php


namespace App\Http\View\Composers;


use App\Model\Post;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class NachrichtenComposer
{
    public function compose($view)
    {


        $expire = 1;

        $nachrichten = Cache::remember('posts_'.auth()->id(), $expire ,function () {

            $user = auth()->user();

            if (!$user->can('view all')) {
                $Nachrichten = $user->posts()->orderByDesc('sticky')->orderByDesc('updated_at')->whereDate('archiv_ab', '>', Carbon::now()->startOfDay())->whereDate('archiv_ab', '>', $user->created_at)->with('media', 'autor', 'groups', 'rueckmeldung')->withCount('users')->get();

                    if ($user->can('create posts')){
                        $eigenePosts = Post::query()->where('author', $user->id)->whereDate('archiv_ab', '>', Carbon::now()->startOfDay())->get();
                        $Nachrichten = $Nachrichten->concat($eigenePosts);
                    }
            } else {
                $Nachrichten = Post::whereDate('archiv_ab', '>', Carbon::now()->startOfDay())->orderByDesc('sticky')->orderByDesc('updated_at')->with('media', 'autor', 'groups', 'rueckmeldung')->withCount('users')->get();
            }


            $Nachrichten = $Nachrichten->unique('id');

            return $Nachrichten->paginate(30);
        });
        $view->with([
            'nachrichten'=> $nachrichten,
            'user' => auth()->user()
        ]);
    }
}