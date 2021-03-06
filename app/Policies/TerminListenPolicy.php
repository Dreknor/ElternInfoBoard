<?php

namespace App\Policies;

use App\Model\Liste;
use App\Model\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TerminListenPolicy
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
     * Determine whether the user can create termins.
     *
     * @param  \App\Model\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return auth()->user()->can('create terminliste');
    }

    public function storeTerminToListe(User $user, Liste $liste)
    {
        return $liste->besitzer == $user->id or $user->can('edit terminliste');
    }

    public function editListe(User $user, Liste $liste)
    {
        return $liste->besitzer == $user->id or $user->can('edit terminliste');
    }
}
