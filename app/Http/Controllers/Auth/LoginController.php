<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use App\Model\User;
use App\Notifications\SendPasswordLessLinkNotification;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

/**
 *
 */
class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {

        if ($request->input('submit') == 'password-less') {
            $user = $this->loginViaPasswordLessLink($request);

            if (!$user or !$user->can('allow password-less-login')) {
                return redirect()->route('login')
                    ->withErrors(['email' => 'Benutzer existiert nicht oder hat keine Berechtigung für den Passwortlosen Login.'])
                    ->withInput();
            }

            return redirect()->route('login')
                ->with([
                    'type' => 'success',
                    'Meldung' => 'Login-Link wurde an die angegebene E-Mail-Adresse gesendet.'
                ]);
        }

        $this->validateLogin($request);

        if (method_exists($this, 'hasTooManyLoginAttempts') &&
            $this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        if ($this->attemptLogin($request)) {
            if ($request->hasSession()) {
                $request->session()->put('auth.password_confirmed_at', time());
            }
            return $this->sendLoginResponse($request);
        }

        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }


    /**
     * @param Request $request
     * @return User
     */
    public function loginViaPasswordLessLink(Request $request): User
    {
        $user = User::where('email', $request->input('email'))->first();

        if ($user) {
            $user->notify(new SendPasswordLessLinkNotification());
        }

        return $user;
    }

    /**
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     */
    public function redirectToKeycloak() {
        if (auth()->check()) {
            return redirect()->route('home');
        }

        if (config('app.keycloak.enabled') == false) {
            return redirect()->route('login')->with([
                'type' => 'danger',
                'Meldung' => 'Keycloak ist nicht aktiviert.'
            ]);
        }

        return Socialite::driver('keycloak')->redirect();
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     *
     */
    public function handleKeycloakCallback()
    {

        if (config('app.keycloak.enabled') == false) {
            return redirect()->route('login')->with([
                'type' => 'danger',
                'Meldung' => 'Keycloak ist nicht aktiviert.'
            ]);
        }

        try {
            $user = Socialite::driver('keycloak')->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->with([
                'type' => 'danger',
                'Meldung' => 'Login fehlgeschlagen.'
            ]);
        }


        if (!$user->email) {
            return redirect()->route('login')->with([
                'type' => 'danger',
                'Meldung' => 'E-Mail-Adresse konnte nicht abgerufen werden.'
            ]);
        }

        $existingUser = User::where('email', $user->email)->first();


        if ($existingUser) {
            auth()->login($existingUser);
            Log::info('User logged in via Keycloak: '.$existingUser->email);
            Log::info($existingUser);
        } else {


            $domain = explode('@', $user->email)[1];

            if (!in_array($domain, config('keycloak.mail_domain'))) {
                return redirect()->route('login')->with([
                    'type' => 'danger',
                    'Meldung' => 'E-Mail-Adresse ist nicht erlaubt.'
                ]);
            }

            $newUser = User::create([
                'name' => $user->givenName.' '.$user->sn ?? $user->nickname,
                'email' => $user->email,
                'password' => bcrypt('password'),
                'created_at' => now(),
                'updated_at' => now(),
                'changePassword' => 0,
                'lastEmail' => now(),
            ]);

            $newUser->assignRole('Mitarbeiter');

            auth()->login($newUser);
        }


        return redirect()->intended('/home');
    }
}
