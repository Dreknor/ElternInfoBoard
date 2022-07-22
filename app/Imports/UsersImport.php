<?php

namespace App\Imports;

use App\Model\Group;
use App\Model\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UsersImport implements ToCollection, WithHeadingRow
{
    protected $header;
    protected $groups;

    public function __construct($header)
    {
        $this->header = $header;
        $this->groups = Group::all();
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            set_time_limit(20);

            $user1 = null;
            $user2 = null;

            $row = array_values($row->toArray());
            $Klassenstufe = $this->groups->where('name', 'Klassenstufe '.$row[$this->header['klassenstufe']])->first();
            $Lerngruppe = $this->groups->where('name', $row[$this->header['lerngruppe']])->first();
            dd($this->groups);
            if (! is_null($row[$this->header['S1Email']])) {
                $email1 = explode(';', $row[$this->header['S1Email']]);
                $email1 = $email1[0];

                $user1 = User::firstOrCreate([
                    'email' => $email1,
                ],
                    [
                        'name'  => $row[$this->header['S1Vorname']].' '.$row[$this->header['S1Nachname']],
                        'changePassword'  => 1,
                        'password'      => Hash::make(config('import_eltern')),
                        'lastEmail' => Carbon::now(),
                    ]);

                $user1->touch();
                $user1->assignRole('Eltern');
                $user1->removeRole('Aufnahme');
                if (is_object($Klassenstufe)) {
                    $user1->groups()->attach([optional($Klassenstufe)->id, optional($Lerngruppe)->id]);
                } else {

                }
            }

            if (! is_null($row[$this->header['S2Email']])) {
                $email2 = explode(';', $row[$this->header['S2Email']]);
                $email2 = $email2[0];

                $user2 = User::firstOrCreate([
                    'email' => $email2,
                ],
                    [
                        'name' => $row[$this->header['S2Vorname']].' '.$row[$this->header['S2Nachname']],
                        'changePassword' => 1,
                        'password' => Hash::make(config('import_eltern')),
                        'lastEmail' => Carbon::now(),
                    ]);

                $user2->touch();
                $user2->assignRole('Eltern');
                $user2->removeRole('Aufnahme');
                if (is_object($Klassenstufe)) {
                    $user2->groups()->attach([optional($Klassenstufe)->id, optional($Lerngruppe)->id]);
                } else {

                }
            }

            if (isset($user2) and isset($user1) and $user2->id != $user1->id and isset($user2->email) and isset($user1->email)) {
                $user2->sorg2 = $user1->id;
                $user1->sorg2 = $user2->id;

                $user2->save();
                $user1->save();
            }
        }
    }
}
