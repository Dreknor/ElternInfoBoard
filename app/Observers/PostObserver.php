<?php

namespace App\Observers;

use App\Model\Settings;
use App\Model\Post;
use App\Repositories\WordpressRepository;
use Illuminate\Support\Str;

class PostObserver
{
    /**
     * Handle the Post "created" event.
     *
     * @param  \App\Post  $post
     * @return void
     */
    public function created(Post $post)
    {

    }

    /**
     * Handle the Post "updated" event.
     *
     * @param  \App\Post  $post
     * @return void
     */
    public function updated(Post $post)
    {
        $wp_push_is_enabled = Settings::firstWhere('setting', 'Push to WordPress')->options['active'];

        if ($wp_push_is_enabled == 1 and $post->published_wp_id != NULL and auth()->user()->can('push to wordpress')){
            $repository = new WordpressRepository();
            $wp_call = $repository->remote_post(Str::slug($post->header), $post->header, $post->news, $post->released, $post->published_wp_id);
        }

    }

    /**
     * Handle the Post "deleted" event.
     *
     * @param  \App\Post  $post
     * @return void
     */
    public function deleted(Post $post)
    {
        //
    }

    /**
     * Handle the Post "restored" event.
     *
     * @param  \App\Post  $post
     * @return void
     */
    public function restored(Post $post)
    {
        //
    }

    /**
     * Handle the Post "force deleted" event.
     *
     * @param  \App\Post  $post
     * @return void
     */
    public function forceDeleted(Post $post)
    {
        //
    }
}
