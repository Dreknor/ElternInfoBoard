<?php

namespace App\Repositories;

use App\Model\Group;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class GroupsRepository
 */
class GroupsRepository
{
    /**
     * @param array $gruppen
     * @return Collection
     */
    public function getGroups(array $gruppen): Collection
    {
        if ($gruppen[0] == 'all') {
            $gruppen = Group::where('protected', 0)->get();
        } elseif (Group::whereIn('bereich', $gruppen)->first() != null) {
            $gruppen = Group::whereIn('bereich', $gruppen)->orWhereIn('id', $gruppen)->get();
            $gruppen = $gruppen->unique();
        } else {
            $gruppen = Group::find($gruppen);
        }

        return $gruppen;
    }
}
