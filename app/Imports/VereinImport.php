<?php

namespace App\Imports;

use App\Model\Group;
use App\Model\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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

    private function getImportPassword(): string
    {
        $pw = config('app.import_verein');
        if (empty($pw)) {
            Log::warning('PW_IMPORT_VEREIN ist nicht gesetzt – zufälliges Passwort wird verwendet');
            return Str::password(16);
        }
        return $pw;
    }

    public function collection(Collection $collection): void
    {

        foreach ($collection as $row) {
            set_time_limit(20);

            $user1 = null;

            if (! is_null($row['person_e_mail_privat'])) {
                $email = explode(';', $row['person_e_mail_privat']);
                if (count($email) > 1) {
                    foreach ($email as $mail) {
                        $user = User::where('email', Str::remove(' ', $mail))->first();
                        if (! is_null($user)) {
                            $email = $user->email;
                        }
                    }

                    if (is_array($email)) {
                        $email = $email[0];
                    }
                } else {
                    $email = $row['person_e_mail_privat'];
                }

                $user1 = User::firstOrCreate([
                    'email' => "$email",
                ],
                    [
                        'name' => $row['person_vorname_nachname'],
                        'changePassword' => 1,
                        'password' => Hash::make($this->getImportPassword()),
                        'lastEmail' => Carbon::now(),
                    ]);

                if (! $user1->wasRecentlyCreated) {
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
