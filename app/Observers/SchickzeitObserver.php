<?php

namespace App\Observers;

use App\Model\Schickzeiten;
use Illuminate\Support\Facades\Auth;

class SchickzeitObserver
{
    /**
     * Handle the schickzeit "created" event.
     *
     * @param  \App\Model\Schickzeiten  $schickzeit
     * @return void
     */
    public function created(Schickzeiten $schickzeit)
    {

    }

    /**
     * Handle the schickzeit "updated" event.
     *
     * @param  \App\Model\Schickzeiten  $schickzeit
     * @return void
     */
    public function updated(Schickzeiten $schickzeit)
    {

    }

    /**
     * Handle the schickzeit "deleted" event.
     *
     * @param  \App\Model\Schickzeiten  $schickzeit
     * @return void
     */
    public function deleted(Schickzeiten $schickzeit)
    {

    }

    /**
     * Handle the schickzeit "restored" event.
     *
     * @param  \App\Model\Schickzeiten  $schickzeit
     * @return void
     */
    public function restored(Schickzeiten $schickzeit)
    {
        //
    }

    /**
     * Handle the schickzeit "force deleted" event.
     *
     * @param  \App\Model\Schickzeiten  $schickzeit
     * @return void
     */
    public function forceDeleted(Schickzeiten $schickzeit)
    {
        //
    }
}
