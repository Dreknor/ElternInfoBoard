<?php

namespace App\Observers;

use App\Model\UserRueckmeldungen;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UserRueckmeldungenObserver
{
    /**
     * Handle the Post "created" event.
     */
    public function created(UserRueckmeldungen $rueckmeldungen): void
    {
        Cache::forget('posts_'.auth()->id());
        Log::debug('UserRueckmeldungen erstellt', [
            'id' => $rueckmeldungen->id,
            'post_id' => $rueckmeldungen->post_id,
            'user_id' => $rueckmeldungen->users_id,
        ]);

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
        Log::info('UserRueckmeldungen gelöscht (soft delete)', [
            'id' => $rueckmeldungen->id,
            'post_id' => $rueckmeldungen->post_id,
            'user_id' => $rueckmeldungen->users_id,
            'deleted_by' => auth()->id(),
        ]);
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
        Log::debug('UserRueckmeldungen gelöscht', [
            'id' => $rueckmeldungen->id,
            'Benutzer' => $rueckmeldungen->user->name ?? 'unknown',
            'gelöscht durch' => auth()->user()->name ?? 'system',
        ]);

    }
}
