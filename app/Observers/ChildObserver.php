<?php

namespace App\Observers;

use App\Model\Child;
use App\Services\Family\GroupMembershipService;

/**
 * Hält abgeleitete Eltern-Gruppen aktuell, wenn sich Klasse/Gruppe eines
 * Kindes ändert oder das Kind (weich) gelöscht bzw. wiederhergestellt wird.
 *
 * @see docs/kind-zentriertes-familienmodell-konzept.md §5.4
 */
class ChildObserver
{
    public function updated(Child $child): void
    {
        if ($child->wasChanged(['class_id', 'group_id'])) {
            $this->sync($child);
        }
    }

    public function deleted(Child $child): void
    {
        $this->sync($child);
    }

    public function restored(Child $child): void
    {
        $this->sync($child);
    }

    private function sync(Child $child): void
    {
        app(GroupMembershipService::class)->syncForChild($child);
    }
}
