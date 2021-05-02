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

class AufnahmeImport implements ToCollection, WithHeadingRow
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

            $AufnahmeGS = $this->groups->where('name', 'Aufnahme GS')->first();
            $AufnahmeOS = $this->groups->where('name', 'Aufnahme OS')->first();

            if (strpos($row[$this->header['gruppen']], 'GS')) {
                $Gruppe = $AufnahmeGS;
            } elseif (strpos($row[$this->header['gruppen']], 'OS')) {
                $Gruppe = $AufnahmeOS;
            } else {
                $Gruppe = null;
            }

            if (! is_null($row[$this->header['S1Email']])) {
                $user1 = User::firstOrCreate([
                    'email' => $row[$this->header['S1Email']],
                ],
                    [
                        'name'  => $row[$this->header['S1Vorname']].' '.$row[$this->header['S1Nachname']],
                        'changePassword'  => 1,
                        'password'      => Hash::make(config('app.import_aufnahme')),
                        'lastEmail' => Carbon::now(),
                    ]);

                if (! $user1->wasRecentlyCreated) {
                    $user1->update([
                        'changeSettings'    => 1,
                    ]);
                }

                $user1->assignRole('Aufnahme');

                if (! $user1->groups->contains($Gruppe)) {
                    $user1->groups()->attach($Gruppe);
                }
            }

            if (! is_null($row[$this->header['S2Email']])) {
                $user2 = User::firstOrCreate([
                    'email' => $row[$this->header['S2Email']],
                ],
                    [
                        'name' => $row[$this->header['S2Vorname']].' '.$row[$this->header['S2Nachname']],
                        'changePassword' => 1,
                        'password' => Hash::make(config('app.import_aufnahme')),
                        'lastEmail' => Carbon::now(),
                    ]);

                if (! $user2->wasRecentlyCreated) {
                    $user2->update([
                        'changeSettings'    => 1,
                    ]);
                }

                $user2->assignRole('Aufnahme');

                if (! $user2->groups->contains($Gruppe)) {
                    $user2->groups()->attach($Gruppe);
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
