<?php

namespace App\Imports;

use App\Mail\NewUserPasswordMail;
use App\Model\Group;
use App\Model\User;
use App\Settings\EmailSetting;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
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
                    // Prüfe ob Benutzer bereits existiert
                    $isNewUser1 = !User::where('email', $email1)->exists();

                    // Generiere Zufallspasswort für neuen Benutzer
                    $password1 = Str::password(12, true, true, true, false);

                    $user1 = User::firstOrCreate([
                        'email' => $email1,
                    ],
                        [
                            'name' => $row[$this->header['S1Vorname']] . ' ' . $row[$this->header['S1Nachname']],
                            'changePassword' => 1,
                            'password' => Hash::make($password1),
                            'lastEmail' => Carbon::now(),
                        ]);

                    $user1->touch();
                    $user1->assignRole('Eltern');
                    $user1->removeRole('Aufnahme');

                    $user1->groups()->attach($gruppen);

                    // Sende Willkommens-E-Mail an neuen Benutzer
                    if ($isNewUser1) {
                        try {
                            $emailSettings = app(EmailSetting::class);
                            Mail::to($user1->email)->queue(new NewUserPasswordMail($user1, $password1, $emailSettings->new_user_welcome_text));
                            Log::info('Willkommens-E-Mail an ' . $user1->email . ' versendet');
                        } catch (\Exception $mailException) {
                            Log::error('Fehler beim Versenden der Willkommens-E-Mail an ' . $user1->email . ': ' . $mailException->getMessage());
                        }
                    }

                    if (!is_null($row[$this->header['S2Email']])) {
                        $email2 = explode(';', $row[$this->header['S2Email']]);
                        $email2 = $email2[0];

                        // Prüfe ob zweiter Benutzer bereits existiert
                        $isNewUser2 = !User::where('email', $email2)->exists();

                        // Generiere Zufallspasswort für neuen zweiten Benutzer
                        $password2 = Str::password(12, true, true, true, false);

                        $user2 = User::firstOrCreate([
                            'email' => $email2,
                        ],
                            [
                                'name' => $row[$this->header['S2Vorname']] . ' ' . $row[$this->header['S2Nachname']],
                                'changePassword' => 1,
                                'password' => Hash::make($password2),
                                'lastEmail' => Carbon::now(),
                            ]);

                        $user2->touch();
                        $user2->assignRole('Eltern');
                        $user2->removeRole('Aufnahme');
                        $user2->groups()->attach($gruppen);

                        // Sende Willkommens-E-Mail an neuen zweiten Benutzer
                        if ($isNewUser2) {
                            try {
                                $emailSettings = app(EmailSetting::class);
                                Mail::to($user2->email)->queue(new NewUserPasswordMail($user2, $password2, $emailSettings->new_user_welcome_text));
                                Log::info('Willkommens-E-Mail an ' . $user2->email . ' versendet');
                            } catch (\Exception $mailException) {
                                Log::error('Fehler beim Versenden der Willkommens-E-Mail an ' . $user2->email . ': ' . $mailException->getMessage());
                            }
                        }

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
