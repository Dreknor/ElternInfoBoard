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
     * @return bool
     */
    public function viewAny(): bool
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
        return auth()->user()->can('create terminliste');
    }

    public function storeTerminToListe(User $user, Liste $liste): bool
    {
        return $liste->besitzer == $user->id or $user->can('edit terminliste');
    }

    public function editListe(User $user, Liste $liste): bool
    {
        return $liste->besitzer == $user->id or $user->can('edit terminliste');
    }
}
