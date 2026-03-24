<?php

namespace App\Imports;

use App\Mail\NewUserPasswordMail;
use App\Model\Group;
use App\Model\User;
use App\Scopes\GetGroupsScope;
use App\Settings\EmailSetting;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AufnahmeImport implements ToCollection, WithHeadingRow
{
    protected array $header;

    protected \Illuminate\Database\Eloquent\Collection $groups;

    public function __construct(array $header)
    {
        $this->header = $header;
        // TODO-1.10: GetGroupsScope umgehen, damit alle Gruppen geladen werden
        $this->groups = Group::withoutGlobalScope(GetGroupsScope::class)->get();
    }

    /** Gibt das konfigurierte Import-Passwort zurück oder ein zufälliges, falls ENV nicht gesetzt. */
    private function getImportPassword(): string
    {
        $pw = config('app.import_aufnahme');
        if (empty($pw)) {
            \Illuminate\Support\Facades\Log::warning('PW_IMPORT_AUFNAHME ist nicht gesetzt – zufälliges Passwort wird verwendet');
            return Str::password(16);
        }
        return $pw;
    }

    public function collection(Collection $collection): void
    {
        foreach ($collection as $row) {
            set_time_limit(20);

            $user1 = null;
            $user2 = null;

            $row = array_values($row->toArray());

            $AufnahmeGS = $this->groups->firstWhere('name', 'Aufnahme GS');
            $AufnahmeOS = $this->groups->firstWhere('name', 'Aufnahme OS');

            if (strpos($row[$this->header['gruppen']], 'GS')) {
                $Gruppe = $AufnahmeGS;
            } elseif (strpos($row[$this->header['gruppen']], 'OS')) {
                $Gruppe = $AufnahmeOS;
            } else {
                $Gruppe = null;
            }

            if (! is_null($row[$this->header['S1Email']])) {

                $user1 = User::where('email', $row[$this->header['S1Email']])->withTrashed()->first();

                if ($user1) {
                    $user1->update([
                        'lastEmail' => Carbon::now(),
                        'created_at' => ($user1->deleted_at != null) ? Carbon::now() : $user1->created_at,
                        'updated_at' => Carbon::now(),
                        'deleted_at' => null,
                    ]);
                } else {
                    // TODO-1.1: Sicheres Zufallspasswort generieren
                    $password1 = Str::password(12, true, true, true, false);

                    $user1 = User::create([
                        'email' => $row[$this->header['S1Email']],
                        'name' => $row[$this->header['S1Vorname']].' '.$row[$this->header['S1Nachname']],
                        'changePassword' => 1,
                        'password' => Hash::make($password1),
                        'lastEmail' => Carbon::now(),
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                        'deleted_at' => null,
                        'changeSettings' => 1,
                    ]);

                    // TODO-1.2: Willkommens-E-Mail versenden
                    try {
                        $emailSettings = app(EmailSetting::class);
                        Mail::to($user1->email)->queue(new NewUserPasswordMail($user1, $password1, $emailSettings->new_user_welcome_text));
                    } catch (\Exception $e) {
                        Log::error('Willkommens-E-Mail fehlgeschlagen für '.$user1->email.': '.$e->getMessage());
                    }
                }

                $user1->assignRole('Aufnahme');

                if (! $user1->groups->contains($Gruppe)) {
                    $user1->groups()->attach($Gruppe);
                }
            }

            if (! is_null($row[$this->header['S2Email']])) {

                $user2 = User::where('email', $row[$this->header['S2Email']])->withTrashed()->first();

                if ($user2) {
                    $user2->update([
                        'lastEmail' => Carbon::now(),
                        'created_at' => ($user2->deleted_at != null) ? Carbon::now() : $user2->created_at,
                        'updated_at' => Carbon::now(),
                        'deleted_at' => null,
                    ]);
                } else {
                    // TODO-1.1: Sicheres Zufallspasswort generieren
                    $password2 = Str::password(12, true, true, true, false);

                    $user2 = User::create([
                        'email' => $row[$this->header['S2Email']],
                        'name' => $row[$this->header['S2Vorname']].' '.$row[$this->header['S2Nachname']],
                        'changePassword' => 1,
                        'password' => Hash::make($password2),
                        'lastEmail' => Carbon::now(),
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                        'deleted_at' => null,
                        'changeSettings' => 1,
                    ]);

                    // TODO-1.2: Willkommens-E-Mail versenden
                    try {
                        $emailSettings = app(EmailSetting::class);
                        Mail::to($user2->email)->queue(new NewUserPasswordMail($user2, $password2, $emailSettings->new_user_welcome_text));
                    } catch (\Exception $e) {
                        Log::error('Willkommens-E-Mail fehlgeschlagen für '.$user2->email.': '.$e->getMessage());
                    }
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
