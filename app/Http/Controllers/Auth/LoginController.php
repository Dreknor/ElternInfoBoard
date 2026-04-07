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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
     * Redirect to KeyCloak for authentication
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectToKeycloak()
    {
        if (auth()->check()) {
            return redirect()->route('home');
        }

        // Check if KeyCloak is enabled (ENV only)
        if (!env('KEYCLOAK_ENABLED', false)) {
            Log::warning('Keycloak login attempt but Keycloak is disabled in ENV');
            return redirect()->route('login')->with([
                'type' => 'danger',
                'Meldung' => 'Keycloak ist nicht aktiviert.',
            ]);
        }

        try {
            // Nur unkritische Infos loggen, keine Konfigurationsdetails
            Log::info('Redirecting to Keycloak');

            return Socialite::driver('keycloak')->redirect();
        } catch (\Exception $e) {
            Log::error('Error redirecting to Keycloak', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return redirect()->route('login')->with([
                'type' => 'danger',
                'Meldung' => 'Keycloak-Verbindung fehlgeschlagen: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle KeyCloak callback
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleKeycloakCallback()
    {
        // Check if KeyCloak is enabled (ENV only)
        if (!env('KEYCLOAK_ENABLED', false)) {
            return redirect()->route('login')->with([
                'type' => 'danger',
                'Meldung' => 'Keycloak ist nicht aktiviert.',
            ]);
        }

        try {
            Log::info('Keycloak callback received', request()->query());

            // Get user from KeyCloak
            $keycloakUser = Socialite::driver('keycloak')->user();

            Log::info('Keycloak user received', [
                'email' => $keycloakUser->email ?? 'N/A',
                'name' => $keycloakUser->name ?? 'N/A',
            ]);

        } catch (\Exception $e) {
            Log::error('Keycloak callback error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return redirect()->route('login')->with([
                'type' => 'danger',
                'Meldung' => 'Login fehlgeschlagen: ' . $e->getMessage(),
            ]);
        }

        // Check if email exists
        if (!$keycloakUser->email) {
            return redirect()->route('login')->with([
                'type' => 'danger',
                'Meldung' => 'E-Mail-Adresse konnte nicht abgerufen werden.',
            ]);
        }

        // Find existing user
        $existingUser = User::where('email', $keycloakUser->email)->first();

        if ($existingUser) {
            // Login existing user
            auth()->login($existingUser);
            request()->session()->forget('passwordless_login');


        } else {
            // Check email domain whitelist
            $domain = explode('@', $keycloakUser->email)[1];
            $allowedDomains = explode(',', env('KEYCLOAK_MAILDOMAIN', '*'));
            $allowedDomains = array_map('trim', $allowedDomains);

            if (!in_array('*', $allowedDomains) && !in_array($domain, $allowedDomains)) {
                return redirect()->route('login')->with([
                    'type' => 'danger',
                    'Meldung' => 'E-Mail-Domain ist nicht erlaubt: ' . $domain,
                ]);
            }

            // Extract name from KeyCloak user data
            $name = $keycloakUser->name ?? $keycloakUser->nickname ?? explode('@', $keycloakUser->email)[0];

            // Try to get given_name and family_name from raw user data
            if (isset($keycloakUser->user)) {
                $givenName = $keycloakUser->user['given_name'] ?? '';
                $familyName = $keycloakUser->user['family_name'] ?? '';
                if ($givenName || $familyName) {
                    $name = trim($givenName . ' ' . $familyName);
                }
            }

            Log::info('Creating new user from Keycloak', [
                'name' => $name,
                'email' => $keycloakUser->email,
            ]);

            // Create new user
            $newUser = User::create([
                'name' => $name,
                'email' => $user->email,
                'password' => Hash::make(Str::random(64)), 
                'created_at' => now(),
                'updated_at' => now(),
                'changePassword' => 0,
                'lastEmail' => now(),
            ]);

            auth()->login($newUser);
            request()->session()->forget('passwordless_login');

            Log::info('Keycloak login successful (new user)', [
                'user_id' => $newUser->id,
                'email' => $newUser->email,
            ]);
        }

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
}
