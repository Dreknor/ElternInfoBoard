<?php

namespace App\Http\Middleware;

use App\Model\Changelog;
use App\Support\Collection;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;

class CheckNewsForUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (auth()->guest()) {
            return $next($request);
        }

        if (auth()->user()->changeSettings) {
            $user = auth()->user();
            $user->changeSettings = 0;
            $user->save();

            return redirect()->to('/einstellungen')->with(['changelog' => true]);
        }

        if (auth()->user()->track_login == true or auth()->user()->track_login == 1) {
            $news = Cache::remember('news_'.auth()->id(), 60 * 5, function () {
                $news = [];
                $changelog = Changelog::whereDate('created_at', '>=', auth()->user()->last_online_at)->first();
                if (! is_null($changelog)) {
                    $news[] = [
                        'link' => url('/changelog'),
                        'title' => '<i class="fa fa-cog"></i> Changelog ',
                    ];
                }

                $termine = auth()->user()->termine()->whereDate('termine.created_at', '>=', auth()->user()->last_online_at)->get();
                $termine = $termine->unique('id');
                foreach ($termine as $termin) {
                    $news[] = [
                        'link' => url('/'),
                        'title' => "<i class=\"far fa-calendar-alt\"></i> $termin->terminname",
                    ];
                }

                $posts = auth()->user()->posts()->whereDate('posts.created_at', '>=', auth()->user()->last_online_at)->get();
                $posts = $posts->unique('id');

                foreach ($posts as $post) {
                    $news[] = [
                        'link' => url('home#'.$post->id),
                        'title' => '<i class="far fa-newspaper"></i> '.$post->header,
                    ];
                }

                $listen = auth()->user()->listen()->whereDate('listen.created_at', '>=', auth()->user()->last_online_at->startofDay())->get();
                $listen = $listen->unique('id');

                foreach ($listen as $liste) {
                    $news[] = [
                        'link' => url('listen/'.$liste->id),
                        'title' => "<i class=\"far fa-list-alt\"></i> $liste->listenname",
                    ];
                }

                $gruppen = auth()->user()->groups->load('media');
                $media = new Collection();

                foreach ($gruppen as $gruppe) {
                    $gruppenMedien = $gruppe->getMedia();
                    foreach ($gruppenMedien as $medium) {
                        if ($medium->created_at->greaterThan(auth()->user()->last_online_at)) {
                            $media->push($medium);
                        }
                    }
                }

                $media = $media->unique('name');

                foreach ($media as $medium) {
                    $news[] = [
                        'link' => url('files'),
                        'title' => "<i class=\"fa fa-download\"></i> $medium->name",
                    ];
                }

                return $news;
            });

            View::share('news', $news);
        }

        return $next($request);
    }
}
