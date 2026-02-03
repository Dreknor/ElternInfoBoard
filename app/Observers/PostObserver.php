<?php

namespace App\Observers;

use App\Jobs\PushPostToWordpress;
use App\Model\Module;
use App\Model\Post;
use Illuminate\Support\Facades\Cache;

class PostObserver
{
    /**
     * Handle the Post "created" event.
     *
     * @param  \App\Post  $post
     * @return void
     */
    public function created(Post $post): void
    {
        Cache::forget('posts_'.auth()->id());

    }

    /**
     * Handle the Post "updated" event.
     *
     * @param  \App\Post  $post
     * @return void
     */
    public function updated(Post $post): void
    {
        $wp_push_is_enabled = Module::firstWhere('setting', 'Push to WordPress')->options['active'];

        if ($wp_push_is_enabled == 1 and $post->published_wp_id != null and auth()->user()->can('push to wordpress')) {
            PushPostToWordpress::dispatch($post);
        }

        Cache::forget('posts_'.auth()->id());

    }

    /**
     * Handle the Post "deleted" event.
     *
     * @param  \App\Post  $post
     * @return void
     */
    public function deleted(Post $post): void
    {
        Cache::forget('posts_'.auth()->id());

    }

    /**
     * Handle the Post "restored" event.
     *
     * @param  \App\Post  $post
     * @return void
     */
    public function restored(Post $post): void
    {
        Cache::forget('posts_'.auth()->id());

    }

    /**
     * Handle the Post "force deleted" event.
     *
     * @param  \App\Post  $post
     * @return void
     */
    public function forceDeleted(Post $post): void
    {
        Cache::forget('posts_'.auth()->id());

    }
}
