<?php

namespace App\Observers;

use App\Model\UserRueckmeldungen;
use Illuminate\Support\Facades\Cache;

class UserRueckmeldungenObserver
{
    /**
     * Handle the Post "created" event.
     */
    public function created(UserRueckmeldungen $rueckmeldungen): void
    {
        Cache::forget('posts_'.auth()->id());

    }

    /**
     * Handle the Post "updated" event.
     */
    public function updated(UserRueckmeldungen $rueckmeldungen): void
    {
        Cache::forget('posts_'.auth()->id());

    }

    /**
     * Handle the Post "deleted" event.
     */
    public function deleted(UserRueckmeldungen $rueckmeldungen): void
    {
        Cache::forget('posts_'.auth()->id());

    }

    /**
     * Handle the Post "restored" event.
     */
    public function restored(UserRueckmeldungen $rueckmeldungen): void
    {
        Cache::forget('posts_'.auth()->id());

    }

    /**
     * Handle the Post "force deleted" event.
     */
    public function forceDeleted(UserRueckmeldungen $rueckmeldungen): void
    {
        Cache::forget('posts_'.auth()->id());

    }
}
