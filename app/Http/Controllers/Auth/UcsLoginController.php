<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
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
 * Implementiert den kompletten Login-Flow via UCS@school IdP (Keycloak/Kopano Konnect):
 *
 * – redirect(): Startet den OIDC-Flow per Socialite::driver('ucs')
 * – callback(): Match per UUID → Username → JIT; Negativ-Cache; Pending-Fallback
 * – pending(): "Konto wird vorbereitet"-Seite mit Auto-Refresh
 * – logout(): Lokaler Logout + Keycloak Single-Logout via id_token_hint
 *
 * Match-Reihenfolge (§6.2):
 *   1. Primär: users.ucs_uuid == OIDC sub-Claim (kein API-Call)
 *   2. Sekundär: users.ucs_username == preferred_username (Backfill uuid)
 *   3. JIT: UcsSyncService::syncSingleParent(), nur wenn on_login_fallback=true
 *   4. Kein Match → Pending-Seite
 *
 * ❗ NIEMALS per E-Mail matchen (§6.2, Kommentar).
 *
 * @see docs/ucs-kelvin-integration-konzept.md §6
 */
class UcsLoginController extends Controller implements HasMiddleware
{
    /** Cache-Key-Präfix für Negativ-Cache (60 s). */
    private const JIT_MISS_PREFIX = 'ucs.jit.miss:';

    /** Negativ-Cache TTL in Sekunden. */
    private const MISS_TTL = 60;

    public static function middleware(): array
    {
        return [
            // redirect + callback: nur für Gäste (neuer Login-Flow)
            // pending: KEIN guest – ein eingeloggter Nutzer kann hier landen,
            //          wenn er den OIDC-Flow aus einer anderen Tab-Session startet.
            new Middleware('guest', only: ['redirect', 'callback']),
            new Middleware('auth', only: ['logout']),
        ];
    }

    /**
     * OIDC-Redirect-Endpunkt: leitet den Browser zum UCS IdP weiter.
     *
     * @see docs/ucs-kelvin-integration-konzept.md §6.2
     */
    public function redirect(): RedirectResponse
    {
        /** @var UcsSetting $ucsSetting */
        $ucsSetting = app(UcsSetting::class);
        /** @var KeyCloakSetting $kc */
        $kc = app(KeyCloakSetting::class);

        if (! $ucsSetting->enabled || ! $kc->enabled) {
            return redirect()->route('login')->with([
                'type'    => 'danger',
                'Meldung' => 'UCS-Login ist nicht aktiviert.',
            ]);
        }

        return Socialite::driver('ucs')->redirect();
    }

    /**
     * OIDC-Callback-Endpunkt: verarbeitet die Antwort des IdP.
     *
     * Zwei Modi:
     *   a) Gast → vollständiger Login-Flow (Match 1/2/3 → JIT → Pending)
     *   b) Eingeloggter Nutzer → Account-Linking: ucs_uuid + ucs_username
     *      des aktuellen Auth-Users mit dem OIDC-Sub-Claim verknüpfen,
     *      dann JIT-Sync ausführen und zurück zum Home.
     *
     * @throttle ucs-jit
     *
     * @see docs/ucs-kelvin-integration-konzept.md §6.2, §6.4
     */
    public function callback(Request $request): RedirectResponse
    {
        /** @var UcsSetting $ucsSetting */
        $ucsSetting = app(UcsSetting::class);
        /** @var KeyCloakSetting $kc */
        $kc = app(KeyCloakSetting::class);

        if (! $ucsSetting->enabled || ! $kc->enabled) {
            return redirect()->route('login')->with([
                'type'    => 'danger',
                'Meldung' => 'UCS-Login ist nicht aktiviert.',
            ]);
        }

        // ── OIDC-User abholen ─────────────────────────────────────────────────
        try {
            $oidc = Socialite::driver('ucs')->user();
        } catch (\Throwable $e) {
            Log::channel('ucs')->warning('[UcsLoginController] Socialite-Fehler im Callback', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('login')->with([
                'type'    => 'danger',
                'Meldung' => 'Login fehlgeschlagen. Bitte versuchen Sie es erneut.',
            ]);
        }

        $uuid     = $oidc->getId();   // sub-Claim: stabile UCS-UUID
        $username = data_get($oidc->user, 'preferred_username');
        $idToken  = data_get($oidc->accessTokenResponseBody, 'id_token');

        Log::channel('ucs')->info('[UcsLoginController] Callback erhalten', [
            'uuid'     => $uuid,
            'username' => $username,
        ]);

        // ── Modus B: Eingeloggter Nutzer → Account-Linking ───────────────────
        if (Auth::check()) {
            return $this->handleAccountLinking(Auth::user(), $uuid, $username, $idToken);
        }

        // ── Modus A: Gast → vollständiger Login-Flow ──────────────────────────

        // ── Stufe 1: Primär-Match via ucs_uuid ──────────────────────────────
        /** @var User|null $user */
        $user = User::where('ucs_uuid', $uuid)->first();

        // ── Stufe 2: Sekundär-Match via ucs_username + uuid-Backfill ─────────
        if ($user === null && $username) {
            $user = User::where('ucs_username', $username)->first();

            if ($user !== null) {
                $user->update(['ucs_uuid' => $uuid]);
                Log::channel('ucs')->info('[UcsLoginController] ucs_uuid-Backfill', [
                    'user_id'  => $user->id,
                    'username' => $username,
                    'uuid'     => $uuid,
                ]);
            }
        }

        // ── Stufe 3: JIT-Sync ─────────────────────────────────────────────────
        if ($user === null && $ucsSetting->on_login_fallback && $username) {
            $cacheKey = self::JIT_MISS_PREFIX . sha1(strtolower($username));

            if (Cache::has($cacheKey)) {
                Log::channel('ucs')->info('[UcsLoginController] JIT übersprungen (Negativ-Cache)', [
                    'username' => $username,
                ]);

                return redirect()->route('auth.ucs.pending');
            }

            Log::channel('ucs')->info('[UcsLoginController] JIT-Sync starten', [
                'username' => $username,
            ]);

            try {
                /** @var UcsSyncService $svc */
                $svc  = app(UcsSyncService::class);
                $user = $svc->syncSingleParent($username);
            } catch (\Throwable $e) {
                Log::channel('ucs')->warning('[UcsLoginController] JIT-Sync Fehler', [
                    'username' => $username,
                    'error'    => $e->getMessage(),
                ]);
                $user = null;
            }

            if ($user === null) {
                Cache::put($cacheKey, true, now()->addSeconds(self::MISS_TTL));

                return redirect()->route('auth.ucs.pending');
            }

            // Erfolgreicher JIT: Negativ-Cache löschen (falls vorhanden)
            Cache::forget($cacheKey);
        }

        // ── Stufe 4: Kein User nach allen Match-Versuchen ─────────────────────
        if ($user === null) {
            Log::channel('ucs')->info('[UcsLoginController] Kein User gefunden → Pending', [
                'uuid'     => $uuid,
                'username' => $username,
            ]);

            return redirect()->route('auth.ucs.pending');
        }

        // ── Guard: deaktivierter Account ──────────────────────────────────────
        abort_unless($user->is_active, 403, 'Konto deaktiviert.');

        // ── id_token für Single-Logout in Session ablegen ─────────────────────
        if ($idToken) {
            session(['ucs_id_token' => $idToken]);
        }

        // ── Login ─────────────────────────────────────────────────────────────
        Auth::login($user, remember: true);

        Log::channel('ucs')->info('[UcsLoginController] Login erfolgreich', [
            'user_id'  => $user->id,
            'username' => $user->ucs_username,
        ]);

        // Nutzer ohne E-Mail direkt zu den Einstellungen leiten
        if (empty($user->email)) {
            return redirect()->route('einstellungen')->with([
                'type'    => 'warning',
                'Meldung' => 'Willkommen! Bitte hinterlegen Sie eine E-Mail-Adresse, um Benachrichtigungen und wichtige Mitteilungen zu erhalten.',
            ]);
        }

        return redirect()->intended('/home');
    }

    /**
     * Account-Linking für bereits eingeloggte Nutzer.
     *
     * Schreibt ucs_uuid + ucs_username auf den aktuellen Auth-User,
     * startet einen JIT-Sync und leitet zurück zu /home.
     *
     * @see docs/ucs-kelvin-integration-konzept.md §6.4
     */
    private function handleAccountLinking(\App\Model\User $user, ?string $uuid, ?string $username, ?string $idToken): RedirectResponse
    {
        $updates = [];

        if ($uuid && empty($user->ucs_uuid)) {
            $updates['ucs_uuid'] = $uuid;
        }

        if ($username && empty($user->ucs_username)) {
            $updates['ucs_username'] = $username;
        }

        if (! empty($updates)) {
            $user->update($updates);
            Log::channel('ucs')->info('[UcsLoginController] Account-Linking durchgeführt', [
                'user_id'  => $user->id,
                'updates'  => array_keys($updates),
            ]);
        }

        // id_token für Single-Logout speichern
        if ($idToken) {
            session(['ucs_id_token' => $idToken]);
        }

        // JIT-Sync im Hintergrund anstoßen
        if ($username) {
            \App\Jobs\SyncSingleUcsParentJob::dispatchAfterResponse($username, $user->id);
        }

        return redirect('/home')->with([
            'type'    => 'success',
            'Meldung' => 'Ihr Konto wurde erfolgreich mit dem Schul-Login verknüpft.',
        ]);
    }

    /**
     * "Konto wird vorbereitet"-Seite.
     *
     * Wird angezeigt, wenn der User noch nicht provisioniert ist
     * oder der JIT-Sync fehlschlug / durch den Negativ-Cache blockiert wird.
     * Zugänglich für Gäste UND eingeloggte Nutzer.
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
     * Der id_token_hint ist notwendig, da Keycloak ab v18+ ohne ihn
     * eine explizite Benutzerbestätigung des Logouts verlangt.
     *
     * @see docs/ucs-kelvin-integration-konzept.md §6.3
     */
    public function logout(Request $request): RedirectResponse
    {
        $idToken = $request->session()->pull('ucs_id_token');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Single-Logout-URL zusammenbauen
        /** @var KeyCloakSetting $kc */
        $kc = app(KeyCloakSetting::class);

        if ($idToken && $kc->base_url && $kc->realm) {
            $logoutUrl = rtrim($kc->base_url, '/')
                .'/realms/'.rawurlencode($kc->realm)
                .'/protocol/openid-connect/logout'
                .'?id_token_hint='.urlencode($idToken)
                .'&post_logout_redirect_uri='.urlencode(url('/'));

            Log::channel('ucs')->info('[UcsLoginController] Single-Logout Redirect', [
                'realm' => $kc->realm,
            ]);

            return redirect()->away($logoutUrl);
        }

        return redirect('/');
    }
}

