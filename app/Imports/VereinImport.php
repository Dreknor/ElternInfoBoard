<?php

namespace App\Imports;

use App\Model\Group;
use App\Model\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class VereinImport implements ToCollection, WithHeadingRow
{

    protected \Illuminate\Database\Eloquent\Collection $groups;

    protected $Gruppe;

    public function __construct(Group $group)
    {
        $this->Gruppe = $group;
    }

    /**
     * @param Collection $collection
     * @return void
     */
    public function collection(Collection $collection): void
    {

        foreach ($collection as $row) {
            set_time_limit(20);

            $user1 = null;


            if (!is_null($row['person_e_mail_privat'])) {
                $email = explode(';', $row['person_e_mail_privat']);
                if (count($email) > 1) {
                    foreach ($email as $mail) {
                        $user = User::where('email', $mail)->first();
                        if (!is_null($user)) {
                            $email = $user->email;
                        }
                    }
                } else {
                    $email = $email[0];
                }

                $user1 = User::firstOrCreate([
                    'email' => $email,
                ],
                    [
                        'name' => $row['person_vorname_nachname'],
                        'changePassword' => 1,
                        'password' => Hash::make(config('app.import_verein')),
                        'lastEmail' => Carbon::now(),
                    ]);

                if (!$user1->wasRecentlyCreated) {
                    $user1->update([
                        'changeSettings' => 1,
                    ]);
                }

                $user1->assignRole('Vereinsmitglied');
                $user1->groups()->attach($this->Gruppe);
            }
        }
    }
}
