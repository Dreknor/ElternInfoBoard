<?php

namespace App\Services\Students;

use App\Http\Middleware\RestrictStudentAccounts;
use App\Model\Child;
use App\Model\User;
use App\Services\Auth\SsoStudentDetector;
use App\Settings\SchuelerSetting;
use App\Support\StudentAccounts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * SSO-Anmeldung von Schülerinnen und Schülern über UCS-OIDC oder Keycloak
 * (docs/konzept-schuelerkonten.md §6.1, Pakete S-08/S-09).
 *
 * Ein SSO-Nutzer gilt als Schüler, wenn der IdP die Schülerrolle meldet ODER seine Kennung zu
 * einem Kind gehört (`children.ucs_username`, `users.ucs_oidc_sub` eines Schülerkontos). So
 * entsteht nie versehentlich ein Elternkonto für ein Kind, auch wenn der Rollen-Claim fehlt.
 *
 * Angemeldet wird nur, wenn das Modul aktiv und „Login über SSO“ eingeschaltet ist. Ein Konto
 * entsteht beim ersten Login nur mit „Konto beim ersten Login anlegen“ und nur für Kinder in
 * freigegebenen Gruppen.
 */
class StudentSsoLogin
{
    public const MESSAGE_NO_ACCOUNT = 'Für dich gibt es noch keinen Zugang. Bitte wende dich an deine Schule.';

    public const MESSAGE_INACTIVE = 'Dein Zugang ist gesperrt. Bitte wende dich an deine Schule.';

    public function __construct(
        private readonly SsoStudentDetector $detector,
        private readonly StudentAccountService $accounts,
    ) {}

    /** Ist dieser SSO-Nutzer ein Schüler (Rolle oder bekannte Kennung)? */
    public function isStudent(object $oidcUser, string $sub, ?string $username): bool
    {
        if ($this->detector->isStudent($oidcUser)) {
            return true;
        }

        if ($sub !== '' && User::students()->where('ucs_oidc_sub', $sub)->exists()) {
            return true;
        }

        return filled($username) && Child::query()->where('ucs_username', $username)->exists();
    }

    /**
     * Schülerkonto zu den SSO-Daten finden oder (falls erlaubt) anlegen.
     *
     * @return array{0: ?User, 1: ?string} Konto oder Fehlermeldung
     */
    public function resolve(string $sub, ?string $username): array
    {
        if (! StudentAccounts::enabled() || ! app(SchuelerSetting::class)->login_sso) {
            return [null, SsoStudentDetector::MESSAGE];
        }

        $student = $sub !== '' ? User::students()->where('ucs_oidc_sub', $sub)->first() : null;

        $child = null;
        if ($student === null && filled($username)) {
            $student = User::students()->where('ucs_username', $username)->first();
            $child = Child::query()->where('ucs_username', $username)->where('status', '!=', Child::STATUS_LEFT)->first();
            $student ??= $child?->studentAccount;
        }

        if ($student === null && $child !== null && $this->mayCreate($child)) {
            try {
                [$student] = $this->accounts->createForChild($child, [
                    'username' => $this->accounts->usernameIsValid(mb_strtolower((string) $username)) ? $username : null,
                    'ucs_username' => $username,
                    'ucs_oidc_sub' => $sub ?: null,
                    'ucs_source' => 'sso',
                    'via' => 'sso',
                ]);
            } catch (\DomainException) {
                [$student] = $this->accounts->createForChild($child, [
                    'ucs_username' => $username, 'ucs_oidc_sub' => $sub ?: null, 'ucs_source' => 'sso', 'via' => 'sso',
                ]);
            }
        }

        if ($student === null) {
            StudentAccountLog::info('Schülerkonto: SSO-Login ohne Konto abgelehnt', null);

            return [null, self::MESSAGE_NO_ACCOUNT];
        }

        // Kennungen nachtragen (für den nächsten Login)
        $updates = array_filter([
            'ucs_oidc_sub' => $student->ucs_oidc_sub ? null : ($sub ?: null),
            'ucs_username' => $student->ucs_username ? null : $username,
        ]);
        if ($updates !== [] && ! User::withStudents()->where('ucs_oidc_sub', $sub)->whereKeyNot($student->id)->exists()) {
            $student->forceFill($updates)->save();
        }

        if ($student->is_active === false) {
            StudentAccountLog::info('Schülerkonto: SSO-Login abgelehnt (gesperrt)', $student);

            return [null, self::MESSAGE_INACTIVE];
        }

        return [$student, null];
    }

    /** Web-Anmeldung abschließen. */
    public function login(Request $request, User $student): void
    {
        \App\Services\Security\AuthEventSubscriber::expect('student_sso');
        Auth::guard('web')->login($student);
        $request->session()->regenerate();
        $request->session()->put(RestrictStudentAccounts::SESSION_LOGIN_AT, time());
        $request->session()->forget('passwordless_login');

        StudentAccountLog::info('Schülerkonto: Login (SSO)', $student);
    }

    private function mayCreate(Child $child): bool
    {
        $settings = app(SchuelerSetting::class);

        if (! $settings->jit_anlage) {
            return false;
        }

        $allowed = array_map('intval', $settings->freigegebene_gruppen);

        return $allowed !== [] && array_intersect($allowed, $child->derivedGroupIds()) !== [];
    }
}
