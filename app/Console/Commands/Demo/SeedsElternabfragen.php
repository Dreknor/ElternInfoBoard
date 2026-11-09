<?php

namespace App\Console\Commands\Demo;

use App\Model\User;

/**
 * Demo-Daten (demo:setup). Wird in App\Console\Commands\DemoSetup eingebunden und
 * nutzt dessen Zustand: $this->users, $this->groups (IDs), $this->children.
 */
trait SeedsElternabfragen
{
    private function createElternabfragen(User $admin): void
    {
        // folgt
    }
}
