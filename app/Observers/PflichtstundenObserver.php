<?php

namespace App\Observers;

use App\Model\Notification;
use App\Model\Pflichtstunde;

class PflichtstundenObserver
{
    /**
     * Handle the Pflichtstunde "created" event.
     */
    public function created(Pflichtstunde $pflichtstunde): void
    {
        //
    }

    /**
     * Handle the Pflichtstunde "updated" event.
     */
    public function updated(Pflichtstunde $pflichtstunde): void
    {
        if ($pflichtstunde->wasChanged('approved') && $pflichtstunde->approved) {
            // Pflichtstunde was approved
            $notification = new Notification([
                'user_id' => $pflichtstunde->user_id,
                'title' => 'Pflichtstunde genehmigt',
                'message' => 'Deine Pflichtstunde vom '.$pflichtstunde->start->format('d.m.Y H:i').' bis '.$pflichtstunde->end->format('d.m.Y H:i').' wurde genehmigt.',
                'type' => 'Pflichtstunden',
                'is_read' => false,
            ]);
            $notification->save();
        }

        if ($pflichtstunde->wasChanged('rejected') && $pflichtstunde->rejected) {
            // Pflichtstunde was rejected
            $notification = new Notification([
                'user_id' => $pflichtstunde->user_id,
                'title' => 'Pflichtstunde abgelehnt',
                'message' => 'Deine Pflichtstunde vom '.$pflichtstunde->start->format('d.m.Y H:i').' bis '.$pflichtstunde->end->format('d.m.Y H:i').' wurde abgelehnt. Grund: '.$pflichtstunde->rejection_reason,
                'type' => 'Pflichtstunden',
                'is_read' => false,
            ]);
            $notification->save();
        }
    }

    /**
     * Handle the Pflichtstunde "deleted" event.
     */
    public function deleted(Pflichtstunde $pflichtstunde): void
    {
        //
    }

    /**
     * Handle the Pflichtstunde "restored" event.
     */
    public function restored(Pflichtstunde $pflichtstunde): void
    {
        //
    }

    /**
     * Handle the Pflichtstunde "force deleted" event.
     */
    public function forceDeleted(Pflichtstunde $pflichtstunde): void
    {
        //
    }
}
