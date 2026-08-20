<?php

namespace App\Observers;

use App\Jobs\PushPostToWordpress;
use App\Model\Module;
use App\Model\Post;
use Illuminate\Support\Facades\Log;

class PostObserver
{
    /**
     * Handle the Post "created" event.
     *
     * @param  \App\Post  $post
     */
    public function created(Post $post): void
    {
        Post::bumpCacheVersion();

    }

    /**
     * Handle the Post "updated" event.
     *
     * @param  \App\Post  $post
     */
    public function updated(Post $post): void
    {
        $wp_push_is_enabled = Module::firstWhere('setting', 'Push to WordPress')?->options['active'] ?? false;

        if ($wp_push_is_enabled == 1 and $post->published_wp_id != null and auth()->user()?->can('push to wordpress')) {
            PushPostToWordpress::dispatch($post);
        }

        Post::bumpCacheVersion();

    }

    /**
     * Handle the Post "deleted" event.
     *
     * @param  \App\Post  $post
     */
    public function deleted(Post $post): void
    {
        Log::debug('Post deleted: ',[
            "alte Cache Version: " => Post::cacheVersion(),
        ]);



        Post::bumpCacheVersion();

        Log::debug('Post deleted: ',[
            "neue Cache Version: " => Post::cacheVersion(),
        ]);

    }

    /**
     * Handle the Post "restored" event.
     *
     * @param  \App\Post  $post
     */
    public function restored(Post $post): void
    {
        Post::bumpCacheVersion();

    }

    /**
     * Handle the Post "force deleted" event.
     *
     * @param  \App\Post  $post
     */
    public function forceDeleted(Post $post): void
    {
        Post::bumpCacheVersion();

    }
}
