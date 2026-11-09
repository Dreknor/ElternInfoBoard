<?php

namespace App\Console\Commands\Demo;

use App\Model\User;

/**
 * Demo-Daten (demo:setup). Wird in App\Console\Commands\DemoSetup eingebunden und
 * nutzt dessen Zustand: $this->users, $this->groups (IDs), $this->children.
 */
trait SeedsInhalte
{
    private function createReinigung(): void
    {
        // folgt
    }

    private function createLinks(User $admin): void
    {
        // folgt
    }

    private function createChangelog(User $admin): void
    {
        // folgt
    }

    private function createNachrichtenExtras(User $admin): void
    {
        // folgt
    }

    private function createDateien(User $admin): void
    {
        // folgt
    }
}
