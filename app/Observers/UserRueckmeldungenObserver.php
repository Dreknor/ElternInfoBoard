<?php

namespace App\Observers;

use App\Model\UserRueckmeldungen;
use Illuminate\Support\Facades\Cache;

class UserRueckmeldungenObserver
{
    /**
     * Handle the Post "created" event.
     *
     * @param UserRueckmeldungen $rueckmeldungen
     * @return void
     */
    public function created(UserRueckmeldungen $rueckmeldungen)
    {
        Cache::forget('posts_' . auth()->id());

    }

    /**
     * Handle the Post "updated" event.
     *
     * @param UserRueckmeldungen $rueckmeldungen
     * @return void
     */
    public function updated(UserRueckmeldungen $rueckmeldungen)
    {
        Cache::forget('posts_' . auth()->id());

    }


    /**
     * Handle the Post "deleted" event.
     *
     * @param UserRueckmeldungen $rueckmeldungen
     * @return void
     */
    public function deleted(UserRueckmeldungen $rueckmeldungen)
    {
        Cache::forget('posts_' . auth()->id());

    }

    /**
     * Handle the Post "restored" event.
     *
     * @param UserRueckmeldungen $rueckmeldungen
     * @return void
     */
    public function restored(UserRueckmeldungen $rueckmeldungen)
    {
        Cache::forget('posts_' . auth()->id());

    }

    /**
     * Handle the Post "force deleted" event.
     *
     * @param UserRueckmeldungen $rueckmeldungen
     * @return void
     */
    public function forceDeleted(UserRueckmeldungen $rueckmeldungen)
    {
        Cache::forget('posts_' . auth()->id());

    }
}
