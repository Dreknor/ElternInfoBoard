<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Grosv\LaravelPasswordlessLogin\LoginUrl;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use App\Model\User;
use App\Notifications\SendPasswordLessLinkNotification;
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

        if ($user and $user->can('allow password-less-login')) {
            $generator = new LoginUrl($user);
            $generator->setRedirectUrl('/home');
            $url = $generator->generate();

            dd($url);

            $user->notify(new SendPasswordLessLinkNotification($url));
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

        $keycloakSetting = new \App\Settings\KeyCloakSetting();

        if ($keycloakSetting->enabled == false) {
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

        $keycloakSetting = new \App\Settings\KeyCloakSetting();

        if ($keycloakSetting->enabled == false) {
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

        } else {


            $domain = explode('@', $user->email)[1];

            $mailDomains = $keycloakSetting->mail_domain;
            $mailDomains = explode(',', $mailDomains);


            if (!is_array($mailDomains) || count($mailDomains) == 0) {
                return redirect()->route('login')->with([
                    'type' => 'danger',
                    'Meldung' => 'E-Mail-Domain ist nicht gestattet.'
                ]);

            } else {
                if (!in_array($domain, $mailDomains)) {
                    return redirect()->route('login')->with([
                        'type' => 'danger',
                        'Meldung' => 'E-Mail-Adresse ist nicht erlaubt.'
                    ]);
                }
            }

            $name = ($user->givenName ?? '').' '.($user->sn ?? $user->nickname);

            if (empty($name)) {
                $name = explode('@', $user->email)[0];
            }

            $newUser = User::create([
                'name' => $name,
                'email' => $user->email,
                'password' => bcrypt(now()->format('YmdHis')),
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
