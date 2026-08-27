<?php

namespace App\Repositories;

use App\Model\Group;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class GroupsRepository
 */
class GroupsRepository
{
    public function getGroups(array $gruppen): Collection
    {
        if (empty($gruppen)) {
            return new Collection;
        }

        $groups = new Collection;

        if ($gruppen[0] == 'all') {
            $groups = Group::active()->where('protected', 0)->get();
        }

        if (Group::active()->whereIn('bereich', $gruppen)->first() != null) {
            $getGruppen = Group::active()
                ->where(function ($query) use ($gruppen) {
                    $query->whereIn('bereich', $gruppen)->orWhereIn('id', $gruppen);
                })
                ->get();
            $getGruppen = $getGruppen->unique();
            $groups = $groups->merge($getGruppen);
        }

        $groups = $groups->merge(Group::active()->find($gruppen));
        $groups = $groups->unique();

        return $groups;

    }
}
