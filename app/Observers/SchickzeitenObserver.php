<?php

namespace App\Observers;

use App\Model\Schickzeiten;
use Illuminate\Support\Facades\Cache;

class SchickzeitenObserver
{
    /**
     * Handle the Schickzeiten "created" event.
     */
    public function created(Schickzeiten $schickzeiten): void
    {
        Cache::forget('schickzeiten_'.$schickzeiten->child_id);
    }

    /**
     * Handle the Schickzeiten "updated" event.
     */
    public function updated(Schickzeiten $schickzeiten): void
    {
        Cache::forget('schickzeiten_'.$schickzeiten->child_id);
    }

    /**
     * Handle the Schickzeiten "deleted" event.
     */
    public function deleted(Schickzeiten $schickzeiten): void
    {
        Cache::forget('schickzeiten_'.$schickzeiten->child_id);
    }

    /**
     * Handle the Schickzeiten "restored" event.
     */
    public function restored(Schickzeiten $schickzeiten): void
    {
        Cache::forget('schickzeiten_'.$schickzeiten->child_id);
    }

    /**
     * Handle the Schickzeiten "force deleted" event.
     */
    public function forceDeleted(Schickzeiten $schickzeiten): void
    {
        Cache::forget('schickzeiten_'.$schickzeiten->child_id);
    }
}
