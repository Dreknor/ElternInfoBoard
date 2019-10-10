<?php

namespace App\Imports;

use App\Model\Groups;
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
        $this->groups = Groups::all();
    }


    public function collection(Collection $rows)
    {
        foreach ($rows as $row)
        {
            set_time_limit (  20 );

            $user1 = null;
            $user2 = null;

            $row = array_values($row->toArray());
            $Klassenstufe = $this->groups->where('name',"Klassenstufe ".$row[$this->header["klassenstufe"]])->first();
            $Lerngruppe = $this->groups->where('name', $row[$this->header["lerngruppe"]])->first();


            if (!is_null($row[$this->header['S1Email']])){

                $user1 = User::firstOrCreate([
                    'email' => $row[$this->header['S1Email']]
                ],
                    [
                        "name"  => $row[$this->header['S1Vorname']]." ".$row[$this->header['S1Nachname']],
                        "changePassword"  => 1,
                        "password"      => Hash::make("ESZ".Carbon::now()->year."!"),
                        'lastEmail' => Carbon::now()
                    ]);


                $user1->touch();

                $user1->groups()->attach([$Klassenstufe->id, $Lerngruppe->id]);
            }

            if (!is_null($row[$this->header['S2Email']])) {

                $user2 = User::firstOrCreate([
                    'email' => $row[$this->header['S2Email']]
                ],
                    [
                        "name" => $row[$this->header['S2Vorname']] . " " . $row[$this->header['S2Nachname']],
                        "changePassword" => 1,
                        "password" => Hash::make("ESZ" . Carbon::now()->year . "!"),
                        'lastEmail' => Carbon::now()
                    ]);


                $user2->touch();
                $user2->groups()->attach([$Klassenstufe->id, $Lerngruppe->id]);

            }

            if (isset($user2) and isset($user1) and $user2->id != $user1->id and isset($user2->email) and isset($user1->email) ){
                $user2->sorg2=$user1->id;
                $user1->sorg2=$user2->id;

                $user2->save();
                $user1->save();
            }


        }




    }
}