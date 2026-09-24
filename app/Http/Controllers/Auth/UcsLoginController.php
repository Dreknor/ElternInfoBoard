<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\SyncSingleUcsParentJob;
use App\Model\User;
use App\Services\Ucs\UcsSyncService;
use App\Settings\KeyCloakSetting;
use App\Settings\UcsSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

/**
 * UCS OIDC-Login-Controller.
 *
 * Implementiert den kompletten Login-Flow via UCS@school IdP (Keycloak):
 *
 * – redirect(): Startet den OIDC-Flow per Socialite::driver('ucs')
 *               (für eingeloggte Nutzer mit ?link=1: Konto-Verknüpfung)
 * – callback(): Match per OIDC-sub → Username → JIT; Negativ-Cache; Pending-Fallback
 * – pending():  "Konto wird vorbereitet"-Seite
 * – logout():   Lokaler Logout + Keycloak Single-Logout via id_token_hint
 *
 * Match-Reihenfolge (§6.2):
 *   1. Primär:   users.ucs_oidc_sub == OIDC sub-Claim (LDAP-entryUUID, kein API-Call)
 *   2. Sekundär: users.ucs_username == preferred_username (= UCS-uid = Kelvin „name"),
 *                Backfill ucs_oidc_sub
 *   3. JIT:      UcsSyncService::syncSingleParent(), nur wenn on_login_fallback=true
 *   4. Kein Match → Pending-Seite
 *
 * ❗ Niemals per E-Mail matchen (§6.2): Die E-Mail aus dem Token wird nur als
 *    Fallback-Adresse für die Neuanlage im JIT-Sync genutzt.
 *
 * ❗ users.ucs_uuid enthält die Kelvin-record_uid (Quellsystem-ID) und ist NICHT
 *    mit dem OIDC-sub vergleichbar.
 *
 * @see docs/ucs-kelvin-integration-konzept.md §6
 */
class UcsLoginController extends Controller implements HasMiddleware
{
    /** Session-Keys */
    public const SESSION_ID_TOKEN = 'ucs_id_token';
    public const SESSION_SSO      = 'ucs_sso_login';
    private const SESSION_LINK    = 'ucs_link_intent';

    public static function middleware(): array
    {
        return [
            // redirect/callback bewusst OHNE guest-Middleware: eingeloggte Nutzer
            // können ihr bestehendes Konto mit dem Schul-Login verknüpfen.
            new Middleware('auth', only: ['logout']),
        ];
    }

    /**
     * OIDC-Redirect-Endpunkt: leitet den Browser zum UCS IdP weiter.
     *
     * @see docs/ucs-kelvin-integration-konzept.md §6.2
     */
    public function redirect(Request $request): RedirectResponse
    {
        if (! $this->isEnabled()) {
            return $this->disabledResponse();
        }

        if (Auth::check()) {
            if (! $request->boolean('link')) {
                return redirect('/home');
            }

            $request->session()->put(self::SESSION_LINK, Auth::id());
        }

        return $this->provider()->redirect();
    }

    /**
     * OIDC-Callback-Endpunkt: verarbeitet die Antwort des IdP.
     *
     * Zwei Modi:
     *   a) Gast → Login-Flow (Match 1/2 → JIT → Pending)
     *   b) Eingeloggter Nutzer mit Verknüpfungs-Absicht → Account-Linking
     *
     * @see docs/ucs-kelvin-integration-konzept.md §6.2, §6.4
     */
    public function callback(Request $request): RedirectResponse
    {
        if (! $this->isEnabled()) {
            return $this->disabledResponse();
        }

        $linkUserId = $request->session()->pull(self::SESSION_LINK);

        if (Auth::check() && $linkUserId === null) {
            return redirect('/home');
        }

        // ── OIDC-User abholen (inkl. state-Prüfung durch Socialite) ──────────
        try {
            $oidc = $this->provider()->user();
        } catch (\Throwable $e) {
            Log::channel('ucs')->warning('[UcsLoginController] Socialite-Fehler im Callback', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('login')->with([
                'type'    => 'danger',
                'Meldung' => 'Login fehlgeschlagen. Bitte versuchen Sie es erneut.',
            ]);
        }

        $sub      = (string) $oidc->getId();
        $username = data_get($oidc->user, 'preferred_username') ?: data_get($oidc->user, 'uid');
        $email    = data_get($oidc->user, 'email');
        $idToken  = data_get($oidc->accessTokenResponseBody, 'id_token');

        Log::channel('ucs')->info('[UcsLoginController] Callback erhalten', [
            'sub'      => $sub,
            'username' => $username,
        ]);

        if ($sub === '') {
            return redirect()->route('login')->with([
                'type'    => 'danger',
                'Meldung' => 'Der Schul-Login hat keine Benutzerkennung geliefert.',
            ]);
        }

        // ── Modus B: Eingeloggter Nutzer → Account-Linking ───────────────────
        if (Auth::check()) {
            if ((int) $linkUserId !== (int) Auth::id()) {
                return redirect('/home');
            }

            return $this->handleAccountLinking($request, Auth::user(), $sub, $username, $idToken);
        }

        // ── Stufe 1: Primär-Match via OIDC-sub ──────────────────────────────
        /** @var User|null $user */
        $user = User::where('ucs_oidc_sub', $sub)->first();

        // ── Stufe 2: Sekundär-Match via ucs_username + sub-Backfill ─────────
        if ($user === null && $username) {
            $user = User::where('ucs_username', $username)->first();

            if ($user !== null) {
                $user->update(['ucs_oidc_sub' => $sub]);
                Log::channel('ucs')->info('[UcsLoginController] ucs_oidc_sub-Backfill', [
                    'user_id'  => $user->id,
                    'username' => $username,
                ]);
            }
        }

        // ── Stufe 3: JIT-Sync ─────────────────────────────────────────────────
        if ($user === null && $username && app(UcsSetting::class)->on_login_fallback) {
            $user = $this->jitProvision($username, $email, $sub);

            if ($user === null) {
                return redirect()->route('auth.ucs.pending');
            }
        }

        // ── Stufe 4: Kein User nach allen Match-Versuchen ─────────────────────
        if ($user === null) {
            Log::channel('ucs')->info('[UcsLoginController] Kein User gefunden → Pending', [
                'sub'      => $sub,
                'username' => $username,
            ]);

            return redirect()->route('auth.ucs.pending');
        }

        // ── Guard: deaktivierter Account ──────────────────────────────────────
        abort_unless($user->is_active, 403, 'Ihr Konto ist deaktiviert. Bitte wenden Sie sich an die Schule.');

        // ── Login ─────────────────────────────────────────────────────────────
        // Marker VOR Auth::login setzen: TriggerUcsProvisioningOnLogin erkennt daran
        // den SSO-Login. Auth::login() migriert die Session-ID und behält die Daten.
        $this->rememberSsoSession($request, $idToken);
        Auth::login($user, remember: true);

        Log::channel('ucs')->info('[UcsLoginController] Login erfolgreich', [
            'user_id'  => $user->id,
            'username' => $user->ucs_username,
        ]);

        return redirect()->intended('/home');
    }

    /**
     * JIT-Provisionierung eines unbekannten Elternteils inkl. Negativ-Cache.
     */
    private function jitProvision(string $username, ?string $email, string $sub): ?User
    {
        $cacheKey = UcsSyncService::jitMissKey($username);

        if (Cache::has($cacheKey)) {
            Log::channel('ucs')->info('[UcsLoginController] JIT übersprungen (Negativ-Cache)', [
                'username' => $username,
            ]);

            return null;
        }

        Log::channel('ucs')->info('[UcsLoginController] JIT-Sync starten', ['username' => $username]);

        try {
            $user = app(UcsSyncService::class)->syncSingleParent($username, $email);
        } catch (\Throwable $e) {
            Log::channel('ucs')->warning('[UcsLoginController] JIT-Sync Fehler', [
                'username' => $username,
                'error'    => $e->getMessage(),
            ]);
            $user = null;
        }

        if ($user === null) {
            Cache::put($cacheKey, true, UcsSyncService::JIT_MISS_TTL);

            return null;
        }

        Cache::forget($cacheKey);

        if (empty($user->ucs_oidc_sub) && ! User::where('ucs_oidc_sub', $sub)->exists()) {
            $user->update(['ucs_oidc_sub' => $sub]);
        }

        return $user;
    }

    /**
     * Account-Linking für bereits eingeloggte Nutzer (z. B. Lehrkräfte oder
     * Eltern mit bestehendem Passwort-Konto).
     *
     * @see docs/ucs-kelvin-integration-konzept.md §6.4
     */
    private function handleAccountLinking(Request $request, User $user, string $sub, ?string $username, ?string $idToken): RedirectResponse
    {
        $conflict = User::withTrashed()
            ->whereKeyNot($user->id)
            ->where(function ($q) use ($sub, $username) {
                $q->where('ucs_oidc_sub', $sub);
                if ($username) {
                    $q->orWhere('ucs_username', $username);
                }
            })
            ->exists();

        if ($conflict) {
            Log::channel('ucs')->warning('[UcsLoginController] Account-Linking abgelehnt – Schul-Login bereits mit anderem Konto verknüpft', [
                'user_id'  => $user->id,
                'username' => $username,
            ]);

            return redirect('/home')->with([
                'type'    => 'danger',
                'Meldung' => 'Dieser Schul-Login ist bereits mit einem anderen Konto verknüpft. Bitte wenden Sie sich an die Schule.',
            ]);
        }

        if (($user->ucs_oidc_sub && $user->ucs_oidc_sub !== $sub)
            || ($user->ucs_username && $username && strcasecmp($user->ucs_username, $username) !== 0)) {
            return redirect('/home')->with([
                'type'    => 'danger',
                'Meldung' => 'Ihr Konto ist bereits mit einem anderen Schul-Login verknüpft.',
            ]);
        }

        $user->update(array_filter([
            'ucs_oidc_sub' => $sub,
            'ucs_username' => $user->ucs_username ?: $username,
        ]));

        Log::channel('ucs')->info('[UcsLoginController] Account-Linking durchgeführt', [
            'user_id'  => $user->id,
            'username' => $username,
        ]);

        $this->rememberSsoSession($request, $idToken);

        if ($username && app(UcsSetting::class)->on_login_fallback) {
            SyncSingleUcsParentJob::dispatchAfterResponse($username, $user->id);
        }

        return redirect('/home')->with([
            'type'    => 'success',
            'Meldung' => 'Ihr Konto wurde erfolgreich mit dem Schul-Login verknüpft.',
        ]);
    }

    /**
     * "Konto wird vorbereitet"-Seite.
     *
     * @see docs/ucs-kelvin-integration-konzept.md §6.4
     */
    public function pending(): \Illuminate\Contracts\View\View
    {
        return view('auth.ucs.pending');
    }

    /**
     * UCS-Logout: lokaler Logout + Keycloak Single-Logout via id_token_hint.
     *
     * @see docs/ucs-kelvin-integration-konzept.md §6.3
     */
    public function logout(Request $request): RedirectResponse
    {
        $idToken = $request->session()->pull(self::SESSION_ID_TOKEN);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $logoutUrl = self::singleLogoutUrl($idToken);

        if ($logoutUrl !== null) {
            Log::channel('ucs')->info('[UcsLoginController] Single-Logout Redirect');

            return redirect()->away($logoutUrl);
        }

        return redirect('/');
    }

    /**
     * Baut die Keycloak-End-Session-URL (RP-Initiated Logout).
     *
     * Keycloak ≥ 18 verlangt id_token_hint oder client_id zusammen mit
     * post_logout_redirect_uri; die Redirect-URI muss im Client unter
     * „Valid post logout redirect URIs" eingetragen sein.
     */
    public static function singleLogoutUrl(?string $idToken): ?string
    {
        if (! $idToken) {
            return null;
        }

        try {
            /** @var KeyCloakSetting $kc */
            $kc = app(KeyCloakSetting::class);
        } catch (\Throwable) {
            return null;
        }

        if (! $kc->base_url || ! $kc->realm) {
            return null;
        }

        $params = array_filter([
            'id_token_hint'            => $idToken,
            'post_logout_redirect_uri' => url('/'),
            'client_id'                => $kc->client_id,
        ]);

        return rtrim($kc->base_url, '/')
            .'/realms/'.rawurlencode($kc->realm)
            .'/protocol/openid-connect/logout?'
            .http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    // =========================================================================
    // Hilfsmethoden
    // =========================================================================

    private function isEnabled(): bool
    {
        try {
            return app(UcsSetting::class)->enabled && app(KeyCloakSetting::class)->enabled;
        } catch (\Throwable) {
            return false;
        }
    }

    private function disabledResponse(): RedirectResponse
    {
        return redirect()->route('login')->with([
            'type'    => 'danger',
            'Meldung' => 'UCS-Login ist nicht aktiviert.',
        ]);
    }

    /**
     * Socialite-Provider mit eigener Callback-URL und OIDC-Standard-Scopes.
     *
     * Die Callback-URL ist unabhängig vom Legacy-Keycloak-Flow (login/keycloak/callback);
     * sie muss im Keycloak-Client als „Valid redirect URI" hinterlegt sein.
     */
    private function provider(): \Laravel\Socialite\Contracts\Provider
    {
        $provider = Socialite::driver('ucs');

        if (method_exists($provider, 'redirectUrl')) {
            $provider->redirectUrl($this->callbackUrl());
        }
        if (method_exists($provider, 'scopes')) {
            $provider->scopes(['openid', 'profile', 'email']);
        }

        return $provider;
    }

    private function callbackUrl(): string
    {
        try {
            $configured = (string) app(KeyCloakSetting::class)->redirect_uri;
        } catch (\Throwable) {
            $configured = '';
        }

        // Explizit konfigurierte URL (z. B. hinter Reverse-Proxy) nur verwenden,
        // wenn sie auf den UCS-Callback zeigt.
        if ($configured !== '' && str_ends_with(rtrim($configured, '/'), '/auth/ucs/callback')) {
            return $configured;
        }

        return route('auth.ucs.callback');
    }

    private function rememberSsoSession(Request $request, ?string $idToken): void
    {
        $request->session()->put(self::SESSION_SSO, true);
        $request->session()->forget('passwordless_login');

        if ($idToken) {
            $request->session()->put(self::SESSION_ID_TOKEN, $idToken);
        }
    }
}
