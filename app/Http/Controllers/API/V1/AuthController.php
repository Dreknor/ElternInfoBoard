<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Model\User;
use Grosv\LaravelPasswordlessLogin\LoginUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

/**
 * Anmeldung der Eltern-App: Passwort, SSO (Keycloak mit PKCE), Anmelde-Link per E-Mail.
 * SSO und Anmelde-Link liefern einen einmaligen Code, den die App gegen ein Token tauscht.
 *
 * @group App: Anmeldung
 */
class AuthController extends Controller
{
    private const CODE_TTL_MINUTES = 5;

    private const MAGIC_TTL_MINUTES = 15;

    /**
     * Anmelden mit E-Mail und Passwort.
     *
     * @unauthenticated
     *
     * @bodyParam email string required Example: eltern@example.org
     * @bodyParam password string required
     * @bodyParam device_name string required Example: iPhone – ElternInfo-App
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'required|string|max:100',
        ]);

        $user = User::whereRaw('LOWER(email) = ?', [Str::lower(trim($request->email))])->first();

        if (! $user || ! Hash::check($request->password, $user->password) || $user->is_active === false) {
            throw ValidationException::withMessages(['email' => ['E-Mail oder Passwort ist nicht korrekt.']]);
        }

        return $this->tokenResponse($user, $request->device_name);
    }

    /**
     * SSO starten: leitet zum Keycloak der Schule weiter.
     *
     * Die App öffnet diese URL im System-Browser (PKCE, `code_challenge` = BASE64URL(SHA256(verifier))).
     *
     * @unauthenticated
     *
     * @queryParam code_challenge string required
     */
    public function ssoStart(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate(['code_challenge' => 'required|string|min:43|max:128']);

        if (! config('services.keycloak.enabled')) {
            return response()->json(['message' => 'Die Anmeldung mit Schulkonto ist nicht aktiviert.'], 404);
        }

        $state = Str::random(40);
        Cache::put("app_sso_state:{$state}", $request->code_challenge, now()->addMinutes(10));

        return Socialite::driver('keycloak')
            ->stateless()
            ->redirectUrl(route('api.v1.auth.sso.callback'))
            ->with(['state' => $state])
            ->redirect();
    }

    /**
     * Rücksprung von Keycloak → Weiterleitung in die App mit einmaligem Code.
     *
     * Die URL muss im Keycloak-Client als gültige Redirect-URI eingetragen sein.
     *
     * @unauthenticated
     */
    public function ssoCallback(Request $request): RedirectResponse
    {
        $challenge = Cache::pull('app_sso_state:'.$request->query('state'));
        if (! $challenge) {
            return $this->toApp(['error' => 'expired']);
        }

        try {
            $keycloakUser = Socialite::driver('keycloak')
                ->stateless()
                ->redirectUrl(route('api.v1.auth.sso.callback'))
                ->user();
        } catch (\Throwable $e) {
            Log::warning('App-SSO: Keycloak-Rückmeldung fehlerhaft: '.$e->getMessage());

            return $this->toApp(['error' => 'sso_failed']);
        }

        // Nur bestehende Konten – neue Konten entstehen über Import/UCS bzw. die Web-Anmeldung.
        $user = $keycloakUser->email ? User::where('email', $keycloakUser->email)->first() : null;
        if (! $user || $user->is_active === false) {
            return $this->toApp(['error' => 'unknown_user']);
        }

        $code = Str::random(48);
        Cache::put("app_login_code:{$code}", ['user_id' => $user->id, 'challenge' => $challenge], now()->addMinutes(self::CODE_TTL_MINUTES));

        return $this->toApp(['code' => $code]);
    }

    /**
     * Anmelde-Link per E-Mail anfordern.
     *
     * Antwortet immer gleich, damit nicht erkennbar ist, ob die Adresse existiert.
     *
     * @unauthenticated
     */
    public function magicLink(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $user = User::whereRaw('LOWER(email) = ?', [Str::lower(trim($request->email))])->first();
        if ($user && $user->is_active !== false && $user->can('allow password-less-login')) {
            $code = Str::random(48);
            Cache::put("app_login_code:{$code}", ['user_id' => $user->id, 'challenge' => null], now()->addMinutes(self::MAGIC_TTL_MINUTES));
            $user->notify(new \App\Notifications\SendPasswordLessLinkNotification(route('app.login.open', ['code' => $code])));
        }

        return response()->json(['message' => 'Falls die Adresse bekannt ist, wurde ein Anmelde-Link gesendet.']);
    }

    /**
     * Einmaligen Code (SSO oder Anmelde-Link) gegen ein Token tauschen.
     *
     * @unauthenticated
     *
     * @bodyParam code string required
     * @bodyParam code_verifier string PKCE-Verifier (nur bei SSO).
     * @bodyParam device_name string required
     */
    public function exchange(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
            'code_verifier' => 'nullable|string|max:128',
            'device_name' => 'required|string|max:100',
        ]);

        $entry = Cache::pull('app_login_code:'.$request->code);
        if (! $entry) {
            return response()->json(['message' => 'Der Anmeldecode ist abgelaufen. Bitte erneut anmelden.'], 422);
        }
        if ($entry['challenge'] !== null) {
            $expected = rtrim(strtr(base64_encode(hash('sha256', (string) $request->code_verifier, true)), '+/', '-_'), '=');
            if (! hash_equals($entry['challenge'], $expected)) {
                return response()->json(['message' => 'Die Anmeldung konnte nicht bestätigt werden.'], 422);
            }
        }

        return $this->tokenResponse(User::findOrFail($entry['user_id']), $request->device_name);
    }

    /**
     * Token verlängern: neues Token ausstellen, altes widerrufen.
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $current = $user->currentAccessToken();
        $response = $this->tokenResponse($user, $current->name ?? 'ElternInfo-App');
        $current->delete();

        return $response;
    }

    /**
     * Abmelden: Token und Push-Gerät entfernen.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Abgemeldet.']);
    }

    /**
     * Kurzlebiger Anmelde-Link für Seiten, die nur im Web existieren (Datenschutz, Hilfe …).
     *
     * @bodyParam path string required Ziel innerhalb der Webseite. Example: /datenschutz
     */
    public function webLink(Request $request): JsonResponse
    {
        $request->validate(['path' => ['required', 'string', 'max:200', 'regex:#^/[A-Za-z0-9/_\-]*$#']]);

        $generator = new LoginUrl($request->user());
        $generator->setRedirectUrl($request->path);

        return response()->json(['data' => ['url' => $generator->generate()]]);
    }

    private function tokenResponse(User $user, string $deviceName): JsonResponse
    {
        $minutes = config('sanctum.expiration');
        $token = $user->createToken(mb_substr($deviceName, 0, 100));

        return response()->json([
            'data' => [
                'token' => $token->plainTextToken,
                'expires_at' => $minutes ? now()->addMinutes($minutes)->toIso8601String() : null,
                'must_change_password' => (bool) $user->changePassword,
                'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
            ],
        ]);
    }

    private function toApp(array $params): RedirectResponse
    {
        return redirect()->away(config('services.app.scheme').'://auth?'.http_build_query($params));
    }
}
