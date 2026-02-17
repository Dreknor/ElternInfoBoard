<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Model\User;
use App\Notifications\SendPasswordLessLinkNotification;
use Grosv\LaravelPasswordlessLogin\LoginUrl;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class LoginController extends Controller implements HasMiddleware
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

    public static function middleware(): array
    {
        return [
            new Middleware('guest', except: ['logout']),
        ];
    }

    /**
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {

        if ($request->input('submit') == 'password-less') {

            $user = $this->loginViaPasswordLessLink($request);

            if (! $user or ! $user->can('allow password-less-login')) {

                return redirect()->route('login')
                    ->withErrors(['email' => 'Benutzer existiert nicht oder hat keine Berechtigung für den Passwortlosen Login.'])
                    ->withInput();
            }

            return redirect()->route('login')
                ->with([
                    'type' => 'success',
                    'Meldung' => 'Login-Link wurde an die angegebene E-Mail-Adresse gesendet.',
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

    public function loginViaPasswordLessLink(Request $request): ?User
    {

        $user = User::where('email', $request->input('email'))->first();

        if ($user and $user->can('allow password-less-login')) {

            $generator = new LoginUrl($user);
            $generator->setRedirectUrl('/home');
            $url = $generator->generate();

            $user->notify(new SendPasswordLessLinkNotification($url));

            return $user;
        }

        return null;
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectToKeycloak()
    {
        if (auth()->check()) {
            return redirect()->route('home');
        }

        try {
            $keycloakSetting = new \App\Settings\KeyCloakSetting;

            if ($keycloakSetting->enabled == false) {
                Log::warning('Keycloak login attempt but Keycloak is disabled');
                return redirect()->route('login')->with([
                    'type' => 'danger',
                    'Meldung' => 'Keycloak ist nicht aktiviert.',
                ]);
            }

            Log::info('Redirecting to Keycloak', [
                'client_id' => $keycloakSetting->client_id ?? env('KEYCLOAK_CLIENT_ID'),
                'base_url' => $keycloakSetting->base_url ?? env('KEYCLOAK_BASE_URL'),
                'realm' => $keycloakSetting->realm ?? env('KEYCLOAK_REALM'),
                'redirect_uri' => $keycloakSetting->redirect_uri ?? env('KEYCLOAK_REDIRECT_URI'),
            ]);

            return Socialite::driver('keycloak')->redirect();
        } catch (\Exception $e) {
            Log::error('Error redirecting to Keycloak', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('login')->with([
                'type' => 'danger',
                'Meldung' => 'Keycloak-Verbindung fehlgeschlagen: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleKeycloakCallback()
    {

        $keycloakSetting = new \App\Settings\KeyCloakSetting;

        if ($keycloakSetting->enabled == false) {
            return redirect()->route('login')->with([
                'type' => 'danger',
                'Meldung' => 'Keycloak ist nicht aktiviert.',
            ]);
        }

        try {
            Log::info('Keycloak callback received', [
                'query_params' => request()->query(),
                'has_code' => request()->has('code'),
                'has_state' => request()->has('state'),
            ]);

            $user = Socialite::driver('keycloak')->user();

            Log::info('Keycloak user data received', [
                'user' => $user,
                'email' => $user->email ?? 'N/A',
                'name' => $user->name ?? 'N/A',
            ]);
        } catch (\Exception $e) {
            Log::error('Keycloak callback error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return redirect()->route('login')->with([
                'type' => 'danger',
                'Meldung' => 'Login fehlgeschlagen: ' . $e->getMessage(),
            ]);
        }

        if (! $user->email) {
            return redirect()->route('login')->with([
                'type' => 'danger',
                'Meldung' => 'E-Mail-Adresse konnte nicht abgerufen werden.',
            ]);
        }

        $existingUser = User::where('email', $user->email)->first();

        if ($existingUser) {
            auth()->login($existingUser);
            // Remove passwordless login marker for Keycloak login
            request()->session()->forget('passwordless_login');

            Log::info('Keycloak login successful for existing user', [
                'user_id' => $existingUser->id,
                'email' => $existingUser->email,
                'intended' => session('url.intended'),
            ]);

        } else {

            $domain = explode('@', $user->email)[1];

            $mailDomains = $this->getKeycloakConfigValue(
                $keycloakSetting->maildomain ?? null,
                'KEYCLOAK_MAILDOMAIN',
                '*'
            );
            $mailDomains = explode(',', $mailDomains);
            $mailDomains = array_map('trim', $mailDomains);

            if (! is_array($mailDomains) || count($mailDomains) == 0) {
                return redirect()->route('login')->with([
                    'type' => 'danger',
                    'Meldung' => 'E-Mail-Domain ist nicht gestattet.',
                ]);

            } else {
                // Prüfen ob Wildcard (*) gesetzt ist - erlaubt alle Domains
                if (! in_array('*', $mailDomains) && ! in_array($domain, $mailDomains)) {
                    return redirect()->route('login')->with([
                        'type' => 'danger',
                        'Meldung' => 'E-Mail-Adresse ist nicht erlaubt.',
                    ]);
                }
            }

            $name = ($user->givenName ?? '').' '.($user->sn ?? $user->nickname);

            if (empty($name)) {
                $name = explode('@', $user->email)[0];
            }

            Log::info('Creating new user from Keycloak login', [
                'name' => $name,
                'email' => $user->email,
            ]);

            $newUser = User::create([
                'name' => $name,
                'email' => $user->email,
                'password' => bcrypt(now()->format('YmdHis')),
                'created_at' => now(),
                'updated_at' => now(),
                'changePassword' => 0,
                'lastEmail' => now(),
            ]);

            // $newUser->assignRole('Mitarbeiter');

            auth()->login($newUser);
            // Remove passwordless login marker for Keycloak login
            request()->session()->forget('passwordless_login');

            Log::info('Keycloak login successful for new user', [
                'user_id' => $newUser->id,
                'email' => $newUser->email,
                'intended' => session('url.intended'),
            ]);
        }

        // Clear any intended URL from session that might redirect to unwanted locations
        session()->forget('url.intended');

        return redirect('/home');
    }

    /**
     * The user has been authenticated.
     * Remove passwordless_login marker for regular login.
     *
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        // Remove passwordless login marker for regular email/password login
        $request->session()->forget('passwordless_login');
    }

    /**
     * Get Keycloak configuration value with fallback to .env
     *
     * @param mixed $settingValue
     * @param string $envKey
     * @param mixed $default
     * @return mixed
     */
    protected function getKeycloakConfigValue($settingValue, string $envKey, $default = null)
    {
        // If setting value exists and is not empty, use it
        if (!empty($settingValue) && $settingValue !== null) {
            return $settingValue;
        }

        // Otherwise fallback to .env
        return env($envKey, $default);
    }
}
