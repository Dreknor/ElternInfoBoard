<?php


namespace App\Repositories;


use App\Model\Group;

/**
 * Class GroupsRepository
 * @package App\Http\Repositories
 */
class GroupsRepository
{

    /**
     * @param array $gruppen
     * @return array
     */
    public function getGroups(Array $gruppen){

        if ($gruppen[0] == "all") {
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
