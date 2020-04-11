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
            $gruppen = Group::where('protected', 0)->where('bereich', '!=', 'Aufnahme')->get();
        } elseif ($gruppen[0] == 'Grundschule' or $gruppen[0] == 'Oberschule') {
            $gruppen = Group::whereIn('bereich', $gruppen)->orWhereIn('id', $gruppen)->get();
            $gruppen = $gruppen->unique();
        } else {
            $gruppen = Group::find($gruppen);
        }

        return $gruppen;
    }
}
