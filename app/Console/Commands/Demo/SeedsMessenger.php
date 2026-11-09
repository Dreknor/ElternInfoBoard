<?php

namespace App\Console\Commands\Demo;

use App\Model\User;

/**
 * Demo-Daten (demo:setup). Wird in App\Console\Commands\DemoSetup eingebunden und
 * nutzt dessen Zustand: $this->users, $this->groups (IDs), $this->children.
 */
trait SeedsMessenger
{
    private function createMessenger(User $admin): void
    {
        // folgt
    }

    private function createModeration(User $admin): void
    {
        // folgt
    }
}
