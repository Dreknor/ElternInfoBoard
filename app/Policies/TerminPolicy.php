<?php

namespace App\Policies;

use App\Model\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TerminPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any termins.
     *
     * @return bool
     */
    public function viewAny(): bool
    {
        return auth()->check();
    }

    /**
     * Determine whether the user can view the termin.
     *
     * @return bool
     */
    public function view(): bool
    {
        return auth()->check();
    }

    /**
     * Determine whether the user can create termins.
     *
     * @return mixed
     */
    public function create(): mixed
    {
        return auth()->user()->can('edit termin');
    }

    /**
     * Determine whether the user can update the termin.
     *
     * @param User $user
     * @return bool
     */
    public function update(User $user): bool
    {
        return $user->can('edit termin');
    }

    /**
     * Determine whether the user can delete the termin.
     *
     * @param User $user
     * @return bool
     */
    public function delete(User $user): bool
    {
        return $user->can('edit termin');
    }

    /**
     * Determine whether the user can restore the termin.
     *
     * @param User $user
     * @return bool
     */
    public function restore(User $user): bool
    {
        return $user->can('edit termin');
    }

    /**
     * Determine whether the user can permanently delete the termin.
     *
     * @param User $user
     * @return bool
     */
    public function forceDelete(User $user): bool
    {
        return $user->can('edit termin');
    }
}
