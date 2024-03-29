<?php

namespace App\Imports;

use App\Model\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MitarbeiterImport implements ToCollection, WithHeadingRow
{
    /**
     * @param  Collection  $collection
     */
    public function collection(Collection $collection)
    {
        foreach ($collection as $row) {
            if (array_key_exists('e_mail', $row) and ! is_null($row['e_mail'])) {
                $user = User::firstOrCreate([
                    'email' => $row['e_mail'],
                ], [
                    'name' => $row['vorname'].' '.$row['nachname'],
                    'changePassword' => 1,
                    'password' => Hash::make(config('app.import_mitarbeiter')),
                    'lastEmail' => Carbon::now(),
                ]);

                $user->touch();
                $user->assignRole('Mitarbeiter');
            }
        }
    }
}
