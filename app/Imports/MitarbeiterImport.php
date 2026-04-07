<?php

namespace App\Imports;

use App\Mail\NewUserPasswordMail;
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

class MitarbeiterImport implements ToCollection, WithHeadingRow
{
    private function getImportPassword(): string
    {
        $pw = config('app.import_mitarbeiter');
        if (empty($pw)) {
            Log::warning('PW_IMPORT_MITARBEITER ist nicht gesetzt – zufälliges Passwort wird verwendet');
            return Str::password(16);
        }
        return $pw;
    }

    public function collection(Collection $collection)
    {
        foreach ($collection as $row) {
            if (array_key_exists('e_mail', $row) and ! is_null($row['e_mail'])) {
                $password = Str::password(12, true, true, true, false);

                $user = User::firstOrCreate([
                    'email' => $row['e_mail'],
                ], [
                    'name' => $row['vorname'].' '.$row['nachname'],
                    'changePassword' => 1,
                    'password' => Hash::make($this->getImportPassword()),
                    'password' => Hash::make($password),
                    'lastEmail' => Carbon::now(),
                ]);

                $user->touch();
                $user->assignRole('Mitarbeiter');

                // TODO-1.2: Willkommens-E-Mail für neue Benutzer versenden
                if ($user->wasRecentlyCreated) {
                    try {
                        $emailSettings = app(EmailSetting::class);
                        Mail::to($user->email)->queue(new NewUserPasswordMail($user, $password, $emailSettings->new_user_welcome_text));
                    } catch (\Exception $e) {
                        Log::error('Willkommens-E-Mail fehlgeschlagen für '.$user->email.': '.$e->getMessage());
                    }
                }
            }
        }
    }
}
