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
    protected bool $sendEmail;

    protected ?string $welcomeText;

    /** @var array<int, array{name: string, email: string, password: string}> */
    protected array $newUsers = [];

    public function __construct(bool $sendEmail = true)
    {
        $this->sendEmail = $sendEmail;
        // Einmal pro Import statt einmal pro Zeile laden, um unnötige
        // Settings-Abfragen bei vielen Zeilen zu vermeiden.
        $this->welcomeText = $this->sendEmail ? app(EmailSetting::class)->new_user_welcome_text : null;
    }

    /** Returns credentials of newly created users (only populated when sendEmail = false). */
    public function getNewUsers(): array
    {
        return $this->newUsers;
    }
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

                // Willkommens-E-Mail versenden (E-Mail-Modus) ODER Zugangsdaten für den
                // PDF-Export sammeln (PDF-Modus). Im PDF-Modus darf keine E-Mail an den
                // neuen Benutzer verschickt werden. Der Versand erfolgt über Mail::queue()
                // asynchron über die Laravel-Queue, damit ein langsamer Mailserver den
                // Import nicht ausbremst.
                if ($user->wasRecentlyCreated) {
                    if ($this->sendEmail) {
                        try {
                            Mail::to($user->email)->queue(new NewUserPasswordMail($user, $password, $this->welcomeText));
                        } catch (\Exception $e) {
                            Log::error('Willkommens-E-Mail fehlgeschlagen für '.$user->email.': '.$e->getMessage());
                        }
                    } else {
                        $this->newUsers[] = ['name' => $user->name, 'email' => $user->email, 'password' => $password];
                    }
                }
            }
        }
    }
}
