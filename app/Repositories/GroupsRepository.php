<?php


namespace App\Repositories;


use App\Model\Groups;

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
            $gruppen = Groups::where('proteted', 0)->get();
        } elseif ($gruppen[0] == 'Grundschule' or $gruppen[0] == 'Oberschule') {
            $gruppen = Groups::whereIn('bereich', $gruppen)->orWhereIn('id', $gruppen)->get();
            $gruppen = $gruppen->unique();
        } else {
            $gruppen = Groups::find($gruppen);
        }

        return $gruppen;
    }
}
