<?php

namespace App\Imports;

use App\Mail\NewUserPasswordMail;
use App\Model\Child;
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

class UsersImport implements ToCollection, WithHeadingRow
{
    protected array $header;

    protected \Illuminate\Database\Eloquent\Collection $groups;

    protected bool $sendEmail;

    /** @var array<int, array{name: string, email: string, password: string}> */
    protected array $newUsers = [];

    public function __construct(array $header, bool $sendEmail = true)
    {
        $this->header = $header;
        $this->sendEmail = $sendEmail;
        $this->groups = Group::withoutGlobalScope(GetGroupsScope::class)->get();
    }

    /** Returns credentials of newly created users (only populated when sendEmail = false). */
    public function getNewUsers(): array
    {
        return $this->newUsers;
    }

    public function collection(Collection $collection)
    {
        foreach ($collection as $row) {
            set_time_limit(20);

            $user1 = null;
            $user2 = null;

            $row = array_values($row->toArray());

            // ── Gruppen aus Klassenstufe / Lerngruppe ──────────────────────────
            $Klassenstufe = isset($this->header['klassenstufe'])
                ? $this->groups->firstWhere('name', 'Klassenstufe ' . $row[$this->header['klassenstufe']])
                : null;

            $Lerngruppe = isset($this->header['lerngruppe'])
                ? $this->groups->firstWhere('name', substr($row[$this->header['lerngruppe']], 1))
                : null;

            $gruppen = [];
            if (! is_null($Klassenstufe)) {
                $gruppen[$Klassenstufe->id] = $Klassenstufe->id;
            }
            if (! is_null($Lerngruppe)) {
                $gruppen[$Lerngruppe->id] = $Lerngruppe->id;
            }

            // ── Zusätzliche Gruppen aus Gruppen-Spalte ─────────────────────────
            if (isset($this->header['gruppen']) && ! is_null($row[$this->header['gruppen']] ?? null)) {
                foreach (explode(';', $row[$this->header['gruppen']]) as $user_group) {
                    $group = $this->groups->firstWhere('name', trim($user_group));
                    if (! is_null($group)) {
                        $gruppen[$group->id] = $group->id;
                    }
                }
            }

            // ── Sorgeberechtigter 1 ────────────────────────────────────────────
            $s1EmailRaw = $row[$this->header['S1Email']] ?? null;
            if (! is_null($s1EmailRaw)) {
                $email1 = explode(';', $s1EmailRaw)[0];

                try {
                    $isNewUser1 = ! User::where('email', $email1)->exists();
                    $password1 = Str::password(12, true, true, true, false);

                    $user1 = User::firstOrCreate(
                        ['email' => $email1],
                        [
                            'name'           => $row[$this->header['S1Vorname']] . ' ' . $row[$this->header['S1Nachname']],
                            'changePassword' => 1,
                            'password'       => Hash::make($password1),
                            'lastEmail'      => Carbon::now(),
                        ]
                    );

                    $user1->touch();
                    $user1->assignRole('Eltern');
                    $user1->removeRole('Aufnahme');
                    $user1->groups()->attach($gruppen);

                    if ($isNewUser1) {
                        if ($this->sendEmail) {
                            try {
                                $emailSettings = app(EmailSetting::class);
                                Mail::to($user1->email)->queue(
                                    new NewUserPasswordMail($user1, $password1, $emailSettings->new_user_welcome_text)
                                );
                                Log::info('Willkommens-E-Mail an ' . $user1->email . ' versendet');
                            } catch (\Exception $mailException) {
                                Log::error('Fehler beim Versenden der Willkommens-E-Mail an ' . $user1->email . ': ' . $mailException->getMessage());
                            }
                        } else {
                            $this->newUsers[] = ['name' => $user1->name, 'email' => $user1->email, 'password' => $password1];
                        }
                    }

                    // ── Sorgeberechtigter 2 (optional) ────────────────────────────
                    $s2EmailIdx = $this->header['S2Email'] ?? null;
                    $s2EmailRaw = ($s2EmailIdx !== null) ? ($row[$s2EmailIdx] ?? null) : null;

                    if (! is_null($s2EmailRaw) && isset($this->header['S2Vorname']) && isset($this->header['S2Nachname'])) {
                        $email2 = explode(';', $s2EmailRaw)[0];

                        $isNewUser2 = ! User::where('email', $email2)->exists();
                        $password2 = Str::password(12, true, true, true, false);

                        $user2 = User::firstOrCreate(
                            ['email' => $email2],
                            [
                                'name'           => $row[$this->header['S2Vorname']] . ' ' . $row[$this->header['S2Nachname']],
                                'changePassword' => 1,
                                'password'       => Hash::make($password2),
                                'lastEmail'      => Carbon::now(),
                            ]
                        );

                        $user2->touch();
                        $user2->assignRole('Eltern');
                        $user2->removeRole('Aufnahme');
                        $user2->groups()->attach($gruppen);

                        if ($isNewUser2) {
                            if ($this->sendEmail) {
                                try {
                                    $emailSettings = app(EmailSetting::class);
                                    Mail::to($user2->email)->queue(
                                        new NewUserPasswordMail($user2, $password2, $emailSettings->new_user_welcome_text)
                                    );
                                    Log::info('Willkommens-E-Mail an ' . $user2->email . ' versendet');
                                } catch (\Exception $mailException) {
                                    Log::error('Fehler beim Versenden der Willkommens-E-Mail an ' . $user2->email . ': ' . $mailException->getMessage());
                                }
                            } else {
                                $this->newUsers[] = ['name' => $user2->name, 'email' => $user2->email, 'password' => $password2];
                            }
                        }
                    }

                    // ── Sorg2-Verknüpfung ─────────────────────────────────────────
                    if (isset($user2) && isset($user1) && $user2->id !== $user1->id
                        && isset($user2->email) && isset($user1->email)) {
                        $user2->sorg2 = $user1->id;
                        $user1->sorg2 = $user2->id;
                        $user2->save();
                        $user1->save();
                    }

                    // ── Kind-Verknüpfung (optional) ───────────────────────────────
                    $kindVornameIdx = $this->header['kind_vorname'] ?? null;
                    $kindNachnameIdx = $this->header['kind_nachname'] ?? null;

                    if ($kindVornameIdx !== null && $kindNachnameIdx !== null) {
                        $kindVorname = trim($row[$kindVornameIdx] ?? '');
                        $kindNachname = trim($row[$kindNachnameIdx] ?? '');

                        if ($kindVorname !== '' && $kindNachname !== '') {
                            $childQuery = Child::where('first_name', $kindVorname)
                                ->where('last_name', $kindNachname);

                            if ($Lerngruppe) {
                                $childQuery->where(function ($q) use ($Lerngruppe) {
                                    $q->where('group_id', $Lerngruppe->id)
                                        ->orWhere('class_id', $Lerngruppe->id);
                                });
                            }

                            $child = $childQuery->first();

                            if ($child) {
                                if ($user1) {
                                    $child->parents()->syncWithoutDetaching([$user1->id]);
                                }
                                if ($user2) {
                                    $child->parents()->syncWithoutDetaching([$user2->id]);
                                }
                                Log::info("Kind verknüpft: {$kindVorname} {$kindNachname} (ID: {$child->id})");
                            } else {
                                Log::info("Kind nicht gefunden – übersprungen: {$kindVorname} {$kindNachname}");
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $vorname = $row[$this->header['S1Vorname']] ?? '?';
                    $nachname = $row[$this->header['S1Nachname']] ?? '?';
                    Log::error("Fehler beim Importieren von {$vorname} {$nachname}: " . $e->getMessage());
                }
            }
        }
    }
}
