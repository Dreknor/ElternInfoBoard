<?php

namespace App\Imports;

use App\Model\Group;
use App\Model\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UsersImport implements ToCollection, WithHeadingRow
{
    protected array $header;

    protected \Illuminate\Database\Eloquent\Collection $groups;

    /**
     * @param array $header
     */
    public function __construct(array $header)
    {
        $this->header = $header;
        $this->groups = Group::all();
    }

    public function collection(Collection $collection)
    {
        foreach ($collection as $row) {
            set_time_limit(20);

            $user1 = null;
            $user2 = null;

            $row = array_values($row->toArray());
            $Klassenstufe = $this->groups->firstWhere('name', 'Klassenstufe '.$row[$this->header['klassenstufe']]);
            $Lerngruppe = $this->groups->firstWhere('name', substr($row[$this->header['lerngruppe']], 1));

            $gruppen = [];
            if (!is_null($Klassenstufe)) {
                $gruppen[$Klassenstufe->id] = $Klassenstufe->id;
            }
            if (!is_null($Lerngruppe)) {
                $gruppen[$Lerngruppe->id] = $Lerngruppe->id;
            }


            foreach (explode($row[$this->header['gruppen']], ';') as $user_group) {
                $group = $this->groups->firstWhere('name', $user_group);
                if (!is_null($group)) {
                    $gruppen[$group->id] = $group->id;
                }
            }

            if (! is_null($row[$this->header['S1Email']])) {
                $email1 = explode(';', $row[$this->header['S1Email']]);
                $email1 = $email1[0];

                try {
                    $user1 = User::firstOrCreate([
                        'email' => $email1,
                    ],
                        [
                            'name' => $row[$this->header['S1Vorname']] . ' ' . $row[$this->header['S1Nachname']],
                            'changePassword' => 1,
                            'password' => Hash::make(config('import_eltern')),
                            'lastEmail' => Carbon::now(),
                        ]);

                    $user1->touch();
                    $user1->assignRole('Eltern');
                    $user1->removeRole('Aufnahme');

                    $user1->groups()->attach($gruppen);

                    if (!is_null($row[$this->header['S2Email']])) {
                        $email2 = explode(';', $row[$this->header['S2Email']]);
                        $email2 = $email2[0];

                        $user2 = User::firstOrCreate([
                            'email' => $email2,
                        ],
                            [
                                'name' => $row[$this->header['S2Vorname']] . ' ' . $row[$this->header['S2Nachname']],
                                'changePassword' => 1,
                                'password' => Hash::make(config('import_eltern')),
                                'lastEmail' => Carbon::now(),
                            ]);

                        $user2->touch();
                        $user2->assignRole('Eltern');
                        $user2->removeRole('Aufnahme');
                        $user2->groups()->attach($gruppen);

                    }

                    if (isset($user2) and isset($user1) and $user2->id != $user1->id and isset($user2->email) and isset($user1->email)) {
                        $user2->sorg2 = $user1->id;
                        $user1->sorg2 = $user2->id;

                        $user2->save();
                        $user1->save();
                    }
                } catch (\Exception $e) {
                    Log::error('Fehler beim Importieren von ' . $row[$this->header['S1Vorname']] . ' ' . $row[$this->header['S1Nachname']]);
                    Log::error($e->getMessage());
                }


            }


        }
    }
}
