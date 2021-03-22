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
     *
     * @param  \App\Model\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return auth()->check();
    }

    /**
     * Determine whether the user can view the termin.
     *
     * @param  \App\Model\User  $user
     * @param  \App\Model\Termin  $termin
     * @return mixed
     */
    public function view(User $user, Termin $termin)
    {
        return auth()->check();
    }

    /**
     * Determine whether the user can create termins.
     *
     * @param  \App\Model\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return auth()->user()->can('edit termin');
    }

    /**
     * Determine whether the user can update the termin.
     *
     * @param  \App\Model\User  $user
     * @param  \App\Model\Termin  $termin
     * @return mixed
     */
    public function update(User $user, Termin $termin)
    {
        return $user->can('edit termin');
    }

    /**
     * Determine whether the user can delete the termin.
     *
     * @param  \App\Model\User  $user
     * @param  \App\Model\Termin  $termin
     * @return mixed
     */
    public function delete(User $user, Termin $termin)
    {
        return $user->can('edit termin');
    }

    /**
     * Determine whether the user can restore the termin.
     *
     * @param  \App\Model\User  $user
     * @param  \App\Model\Termin  $termin
     * @return mixed
     */
    public function restore(User $user, Termin $termin)
    {
        return $user->can('edit termin');
    }

    /**
     * Determine whether the user can permanently delete the termin.
     *
     * @param  \App\Model\User  $user
     * @param  \App\Model\Termin  $termin
     * @return mixed
     */
    public function forceDelete(User $user, Termin $termin)
    {
        return $user->can('edit termin');
    }
}
