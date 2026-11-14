<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Http\Middleware\RestrictStudentAccounts;
use App\Model\User;
use App\Services\Security\AuthEventSubscriber;
use App\Services\Security\SecurityLog;
use App\Services\Students\StudentAccountLog;
use App\Services\Students\StudentAccountService;
use App\Settings\SchuelerSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Anmeldung der Schülerkonten ohne SSO (docs/konzept-schuelerkonten.md §6.2, Paket S-07).
 *
 * Eigene Seite mit eigenem Rate-Limit (`login-student`: je Benutzername und je Gerät), damit
 * eine ganze Klasse hinter einer Schul-IP sich anmelden kann, ohne das Eltern-Limit zu teilen.
 * Nur erreichbar bei aktivem Modul und Einstellung `login_benutzername`.
 */
class StudentLoginController extends Controller
{
    private const FAILED = 'Benutzername oder Passwort ist nicht korrekt.';

    public function __construct(private readonly SchuelerSetting $settings) {}

    public function show(): View|RedirectResponse
    {
        $this->ensureEnabled();

        if (Auth::check()) {
            return redirect('/home');
        }

        return view('student.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $this->ensureEnabled();

        $data = $request->validate([
            'username' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string', 'max:200'],
        ]);

        $student = User::students()
            ->where('username', mb_strtolower(trim($data['username'])))
            ->first();

        // Gleiche Antwortzeit und Meldung, ob es das Konto gibt oder nicht
        $valid = Hash::check($data['password'], $student?->password ?? Hash::make(\Illuminate\Support\Str::random(32)));

        if (! $student || ! $valid) {
            SecurityLog::record('auth.failed', $student?->id, ['method' => 'student_password', 'reason' => 'invalid_credentials']
                + ($student ? [] : AuthEventSubscriber::identifierContext($data['username'])));

            return back()->withInput($request->only('username'))->withErrors(['username' => self::FAILED]);
        }

        if ($student->is_active === false) {
            SecurityLog::record('auth.failed', $student->id, ['method' => 'student_password', 'reason' => 'inactive']);

            return back()->withInput($request->only('username'))->withErrors(['username' => $this->inactiveMessage($student)]);
        }

        AuthEventSubscriber::expect('student_password');
        Auth::guard('web')->login($student);
        $request->session()->regenerate();
        $request->session()->put(RestrictStudentAccounts::SESSION_LOGIN_AT, time());
        $request->session()->forget('passwordless_login');

        StudentAccountLog::info('Schülerkonto: Login', $student);

        return redirect()->route('student.home');
    }

    public function showCode(Request $request): View
    {
        $this->ensureEnabled();

        return view('student.auth.code', [
            'username' => (string) $request->query('u', ''),
            'code' => (string) $request->query('c', ''),
        ]);
    }

    public function redeemCode(Request $request, StudentAccountService $accounts): RedirectResponse
    {
        $this->ensureEnabled();

        $data = $request->validate([
            'username' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $student = $accounts->redeemCode($data['username'], $data['code'], $data['password']);

        if ($student === null) {
            SecurityLog::record('auth.failed', null, ['method' => 'student_code', 'reason' => 'invalid_code']
                + AuthEventSubscriber::identifierContext($data['username']));

            return back()->withInput($request->only('username', 'code'))
                ->withErrors(['code' => 'Der Code ist falsch oder abgelaufen. Bitte frage im Sekretariat oder bei deinen Eltern nach einem neuen Code.']);
        }

        if ($student->is_active === false) {
            return redirect()->route('student.login')->with([
                'type' => 'warning',
                'Meldung' => 'Dein Passwort ist gespeichert. '.$this->inactiveMessage($student),
            ]);
        }

        AuthEventSubscriber::expect('student_code');
        Auth::guard('web')->login($student);
        $request->session()->regenerate();
        $request->session()->put(RestrictStudentAccounts::SESSION_LOGIN_AT, time());

        return redirect()->route('student.home')->with(['type' => 'success', 'Meldung' => 'Dein Passwort ist gespeichert. Willkommen!']);
    }

    private function inactiveMessage(User $student): string
    {
        return $this->settings->eltern_zustimmung && $student->student_consent !== 'ja'
            ? 'Dein Zugang wird freigeschaltet, sobald deine Eltern zugestimmt haben.'
            : 'Dein Zugang ist gesperrt. Bitte wende dich an das Sekretariat.';
    }

    private function ensureEnabled(): void
    {
        abort_unless($this->settings->login_benutzername, 404);
    }
}
