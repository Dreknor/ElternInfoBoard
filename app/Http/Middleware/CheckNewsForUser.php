<?php

namespace App\Http\Middleware;

use App\Support\Collection;
use Closure;
use Illuminate\Support\Facades\DB;
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

        if (auth()->user()->track_login == true or auth()->user()->track_login == 1)
        {
            $news = [];

            $termine = auth()->user()->termine()->whereDate('termine.created_at', '>=', auth()->user()->last_online_at->startofDay())->get();
            $termine = $termine->unique('id');
            foreach ($termine as $termin){
                $news[]=[
                    'link' => url('/'),
                    'title' => "<i class=\"far fa-calendar-alt\"></i> $termin->terminname"
                ];
            }

            $posts = auth()->user()->posts()->whereDate('posts.created_at', '>=', auth()->user()->last_online_at->startofDay())->get();
            $posts = $posts->unique('id');

            foreach ($posts as $post){
                $news[]=[
                    'link' => url('home#'.$post->id),
                    'title' => '<i class="far fa-newspaper"></i> '.$post->header
                ];
            }

            $listen = auth()->user()->listen()->whereDate('listen.created_at', '>=', auth()->user()->last_online_at->startofDay())->get();
            $listen = $listen->unique('id');

            foreach ($listen as $liste){
                $news[]=[
                    'link' => url('listen/'.$liste->id),
                    'title' => "<i class=\"far fa-list-alt\"></i> $liste->listenname"
                ];
            }

            $gruppen = auth()->user()->groups->load('media');
            $media = new Collection();

            foreach ($gruppen as $gruppe){
                $gruppenMedien = $gruppe->getMedia();
                foreach ($gruppenMedien as $medium){
                    if ($medium->created_at->greaterThan(auth()->user()->last_online_at->startofDay())){
                        $media->push($medium);
                    }
                }
            }

            $media = $media->unique('name');

            foreach ($media as $medium){
                $news[]=[
                    'link' => url('files'),
                    'title' => "<i class=\"fa fa-download\"></i> $medium->name"
                ];
            }

            View::share('news', $news);
        }
        return $next($request);
    }
}
