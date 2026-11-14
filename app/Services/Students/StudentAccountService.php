<?php

namespace App\Services\Students;

use App\Model\Child;
use App\Model\User;
use App\Settings\SchuelerSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Anlegen, Sperren, Codes und Löschen von Schülerkonten (docs/konzept-schuelerkonten.md §6.2, §7, §9).
 *
 * Alle Wege (Verwaltung, Import, Klassen-Anlage, SSO, UCS) laufen hierüber, damit dieselben
 * Regeln gelten: ein Konto je Kind, keine E-Mail, keine Rollen, keine Gruppen, Protokoll.
 */
class StudentAccountService
{
    /** Gültigkeit eines Einmal-Codes. */
    public const CODE_DAYS = 7;

    /** Zeichen für Codes: ohne leicht verwechselbare Zeichen (0/O, 1/I/L). */
    private const CODE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public function __construct(private readonly SchuelerSetting $settings) {}

    /**
     * Konto für ein Kind anlegen oder ein vorhandenes zurückgeben (nie doppelt).
     *
     * @param  array{username?: ?string, ucs_username?: ?string, ucs_oidc_sub?: ?string, ucs_source?: ?string, via?: string}  $options
     * @return array{0: User, 1: bool} Konto und ob es neu angelegt wurde
     */
    public function createForChild(Child $child, array $options = []): array
    {
        if ($existing = $child->studentAccount) {
            return [$existing, false];
        }

        if ($child->status === Child::STATUS_LEFT) {
            throw new \DomainException('Für ausgeschiedene Kinder wird kein Schülerkonto angelegt.');
        }

        return DB::transaction(function () use ($child, $options) {
            $username = filled($options['username'] ?? null)
                ? $this->normalizeUsername((string) $options['username'])
                : $this->uniqueUsername($child->first_name, $child->last_name);

            if (! $this->usernameIsValid($username) || User::withStudents()->withTrashed()->where('username', $username)->exists()) {
                throw new \DomainException("Der Benutzername „{$username}“ ist ungültig oder schon vergeben.");
            }

            $needsConsent = $this->settings->eltern_zustimmung;

            $student = new User;
            $student->forceFill([
                'name' => trim($child->first_name.' '.$child->last_name),
                'account_type' => User::ACCOUNT_STUDENT,
                'username' => $username,
                'email' => null,
                'password' => Hash::make(Str::random(64)),
                'changePassword' => false,
                'is_active' => ! $needsConsent,
                'deactivated_at' => null,
                'lastEmail' => now(),
                'ucs_username' => $options['ucs_username'] ?? null,
                'ucs_oidc_sub' => $options['ucs_oidc_sub'] ?? null,
                'ucs_source' => $options['ucs_source'] ?? 'local',
            ]);
            $student->save();

            $child->forceFill(['user_id' => $student->id])->save();
            StudentContext::forget($student);

            StudentAccountLog::info('Schülerkonto angelegt', $student, ['kind_id' => $child->id, 'weg' => $options['via'] ?? 'verwaltung']);

            return [$student, true];
        });
    }

    /**
     * Neuen Einmal-Code ausstellen (ersetzt einen alten). Der Klartext wird nur hier zurückgegeben.
     */
    public function issueCode(User $student): string
    {
        $this->assertStudent($student);

        $code = '';
        for ($i = 0; $i < 8; $i++) {
            $code .= self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)];
        }

        $student->forceFill([
            'student_code_hash' => self::hashCode($code),
            'student_code_expires_at' => now()->addDays(self::CODE_DAYS),
        ])->save();

        StudentAccountLog::info('Schülerkonto: Einmal-Code ausgegeben', $student);

        return substr($code, 0, 4).'-'.substr($code, 4);
    }

    /**
     * Code einlösen und Passwort setzen. Gibt das Konto zurück oder null (Code falsch/abgelaufen).
     */
    public function redeemCode(string $username, string $code, string $password): ?User
    {
        $student = User::students()->where('username', $this->normalizeUsername($username))->first();

        if ($student === null
            || $student->student_code_hash === null
            || $student->student_code_expires_at === null
            || $student->student_code_expires_at->isPast()
            || ! hash_equals($student->student_code_hash, self::hashCode($code))) {
            return null;
        }

        $student->forceFill([
            'password' => Hash::make($password),
            'changePassword' => false,
            'student_code_hash' => null,
            'student_code_expires_at' => null,
        ])->save();

        StudentAccountLog::info('Schülerkonto: Einmal-Code eingelöst', $student);

        return $student;
    }

    public function deactivate(User $student, string $reason = 'verwaltung'): void
    {
        $this->assertStudent($student);

        if ($student->is_active === false) {
            return;
        }

        $student->forceFill(['is_active' => false, 'deactivated_at' => now()])->save();
        \App\Services\Security\AccountSecurity::revokeTokens($student, 'account_deactivated');
        $this->forgetSessions($student);

        StudentAccountLog::info('Schülerkonto gesperrt', $student, ['grund' => $reason]);
    }

    public function activate(User $student): void
    {
        $this->assertStudent($student);

        $child = Child::query()->where('user_id', $student->id)->first();
        if ($child === null || $child->status === Child::STATUS_LEFT) {
            throw new \DomainException('Das Konto ist keinem aktiven Kind zugeordnet.');
        }

        if ($this->settings->eltern_zustimmung && $student->student_consent !== 'ja') {
            throw new \DomainException('Es fehlt die Zustimmung der Eltern.');
        }

        $student->forceFill(['is_active' => true, 'deactivated_at' => null])->save();
        StudentAccountLog::info('Schülerkonto entsperrt', $student);
    }

    /**
     * Zustimmung oder Widerspruch einer Bezugsperson (§9): der letzte Stand gilt.
     */
    public function setConsent(User $student, User $guardian, bool $granted): void
    {
        $this->assertStudent($student);

        $student->forceFill([
            'student_consent' => $granted ? 'ja' : 'nein',
            'student_consent_at' => now(),
            'student_consent_by' => $guardian->id,
        ])->save();

        StudentAccountLog::info($granted ? 'Schülerkonto: Zustimmung erteilt' : 'Schülerkonto: Zustimmung widerrufen', $student, ['bezugsperson_id' => $guardian->id]);

        if (! $granted) {
            $this->deactivate($student, 'widerspruch');

            return;
        }

        $child = Child::query()->where('user_id', $student->id)->first();
        if ($student->is_active === false && $child && $child->status !== Child::STATUS_LEFT) {
            $student->forceFill(['is_active' => true, 'deactivated_at' => null])->save();
            StudentAccountLog::info('Schülerkonto entsperrt', $student, ['grund' => 'zustimmung']);
        }
    }

    public function rename(User $student, string $username): void
    {
        $this->assertStudent($student);
        $username = $this->normalizeUsername($username);

        if (! $this->usernameIsValid($username)
            || User::withStudents()->withTrashed()->where('username', $username)->whereKeyNot($student->id)->exists()) {
            throw new \DomainException("Der Benutzername „{$username}“ ist ungültig oder schon vergeben.");
        }

        $student->forceFill(['username' => $username])->save();
        StudentAccountLog::info('Schülerkonto: Benutzername geändert', $student);
    }

    /** Konto endgültig löschen; das Kind bleibt erhalten (`children.user_id` → null). */
    public function delete(User $student, string $reason = 'verwaltung'): void
    {
        $this->assertStudent($student);

        $id = $student->id;
        DB::transaction(function () use ($student) {
            Child::query()->where('user_id', $student->id)->update(['user_id' => null]);
            \App\Services\Security\AccountSecurity::revokeTokens($student, 'account_deleted');
            $this->forgetSessions($student);
            DB::table('notifications')->where('user_id', $student->id)->delete();
            $student->pushSubscriptions()->delete();
            StudentMessenger::deleteFor($student->id);
            $student->forceDelete();
        });

        StudentAccountLog::info('Schülerkonto gelöscht', $id, ['grund' => $reason]);
    }

    /**
     * Benutzername nach §6.2: vorname.nachname, klein, Umlaute umgeschrieben, Doppelnamen mit
     * Bindestrich, sonst nur a–z, 0–9, Punkt, Bindestrich. Bei Kollision eine Zahl anhängen.
     */
    public function uniqueUsername(?string $firstName, ?string $lastName): string
    {
        $base = $this->slug($firstName).'.'.$this->slug($lastName);
        $base = trim($base, '.') ?: 'schueler';
        $base = mb_substr($base, 0, 100);

        $candidate = $base;
        $n = 2;
        while (User::withStudents()->withTrashed()->where('username', $candidate)->exists()) {
            $candidate = $base.$n++;
        }

        return $candidate;
    }

    public function usernameIsValid(string $username): bool
    {
        return (bool) preg_match('/^[a-z0-9][a-z0-9.\-]{1,118}[a-z0-9]$/', $username) && ! str_contains($username, '@');
    }

    public function normalizeUsername(string $username): string
    {
        return mb_strtolower(trim($username));
    }

    public static function hashCode(string $code): string
    {
        $normalized = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code));

        return hash_hmac('sha256', $normalized, (string) config('app.key'));
    }

    private function slug(?string $name): string
    {
        $name = mb_strtolower(trim((string) $name));
        $name = strtr($name, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss']);
        $name = Str::ascii($name);
        $name = preg_replace('/\s+/', '-', $name);
        $name = preg_replace('/[^a-z0-9\-]/', '', $name);

        return trim(preg_replace('/-+/', '-', $name), '-');
    }

    private function assertStudent(User $student): void
    {
        if (! $student->isStudent()) {
            throw new \LogicException('Kein Schülerkonto.');
        }
    }

    /** Laufende Web-Sitzungen beenden (nur bei Datenbank-Sessions möglich). */
    private function forgetSessions(User $student): void
    {
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $student->id)->delete();
        }
    }
}
