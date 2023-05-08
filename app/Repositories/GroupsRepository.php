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
        $groups = new Collection();

        //($gruppen);
        if ($gruppen[0] == "all") {
            $groups = Group::where('protected', 0)->get();
        }

        if (Group::whereIn('bereich', $gruppen)->first() != null) {
            $getGruppen = Group::whereIn('bereich', $gruppen)->orWhereIn('id', $gruppen)->get();
            $getGruppen = $getGruppen->unique();
            $groups = $groups->merge($getGruppen);
        }

        $groups = $groups->merge(Group::find($gruppen));
        $groups->unique();

        if (count($gruppen) < 1) {
            return [];
        }
        return $groups;

    }
}
