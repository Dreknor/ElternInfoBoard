<?php

namespace App\Policies;

use App\Model\Termin;
use App\Model\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TerminPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any termins.
     */
    public function viewAny(User $user): bool
    {
        return $user !== null; // auth()->check()
    }

    /**
     * Determine whether the user can view the termin.
     */
    public function view(User $user, ?Termin $termin = null): bool
    {
        return $user !== null; // Jede/r angemeldete darf Termine sehen
    }

    /**
     * Determine whether the user can create termins.
     */
    public function create(User $user): bool
    {
        return $user->can('edit termin') || $user->can('create termine');
    }

    /**
     * Determine whether the user can update the termin.
     */
    public function update(User $user, Termin $termin): bool
    {
        return $user->can('edit termin');
    }

    /**
     * Determine whether the user can delete the termin.
     */
    public function delete(User $user, Termin $termin): bool
    {
        return $user->can('edit termin');
    }

    /**
     * Determine whether the user can restore the termin.
     */
    public function restore(User $user, Termin $termin): bool
    {
        return $user->can('edit termin');
    }

    /**
     * Determine whether the user can permanently delete the termin.
     */
    public function forceDelete(User $user, Termin $termin): bool
    {
        return $user->can('edit termin');
    }
}
