# TODO-06: OIDC-Login (Socialite-Driver, Callback, JIT, Single-Logout)

> Konzept: §6 (OIDC-Login), §6.4 (JIT-Sync), §7.4 (Identifier), §15.5 (E-Mail-Konflikt)

## Ziel

Eltern können sich ausschließlich via UCS-IdP (Keycloak/Kopano Konnect)
anmelden. Match erfolgt über die stabile UCS-UUID, niemals über E-Mail.
Unbekannte Eltern werden via JIT-Sync nachgeladen.

## Scope / Anforderungen

- Socialite-Driver `ucs` (Alias auf `keycloak`), Werte aus
  `KeycloakSetting`.
- Callback-Controller mit drei Match-Stufen (UUID → Username → JIT).
- `id_token` in Session für Single-Logout.
- RateLimiter `ucs-jit` auf den Callback.
- Negativ-Cache (60 s) für unbekannte Usernames.
- Hard-Timeout 5 s für JIT-Lookup.
- Pending-Seite für „Konto wird vorbereitet“-Flow.

## Abhängigkeiten

- 02 (`UcsServiceProvider` registriert Driver, Permissions) **zwingend**.
- 04 (`UcsSyncService::syncSingleParent`) **zwingend**.
- 01 (`users.ucs_uuid/ucs_username/is_active`) **zwingend**.

## Aufgaben

### Socialite-Driver

- [ ] `composer require socialiteproviders/keycloak`.
- [ ] In `EventServiceProvider::$listen` den
  `SocialiteWasCalled`-Listener registrieren (siehe §6.1).
- [ ] In `UcsServiceProvider::boot()` (aus Paket 02):
  - Driver-Alias `ucs` definieren, der intern `keycloak` aufruft, mit
    Config aus `KeycloakSetting`.

### Routing

- [ ] In `routes/web.php`:
  ```php
  Route::get('/auth/ucs/redirect', [UcsLoginController::class, 'redirect'])
       ->name('auth.ucs.redirect');
  Route::get('/auth/ucs/callback', [UcsLoginController::class, 'callback'])
       ->middleware('throttle:ucs-jit')
       ->name('auth.ucs.callback');
  Route::get('/auth/ucs/pending', [UcsLoginController::class, 'pending'])
       ->name('auth.ucs.pending');
  Route::post('/auth/ucs/logout', [UcsLoginController::class, 'logout'])
       ->middleware('auth')->name('auth.ucs.logout');
  ```
- [ ] In `RouteServiceProvider::configureRateLimiting()`:
  ```php
  RateLimiter::for('ucs-jit', fn (Request $r) =>
      Limit::perMinute(30)->by($r->ip()));
  ```

### Controller

- [ ] `app/Http/Controllers/Auth/UcsLoginController.php` anlegen.
  - `redirect()` → `Socialite::driver('ucs')->redirect()`.
  - `callback()`:
    1. `$oidc = Socialite::driver('ucs')->user();`
    2. Match `ucs_uuid` (Primärschlüssel).
    3. Fallback `ucs_username` (Backfill `ucs_uuid` setzen).
    4. JIT-Sync via `UcsSyncService::syncSingleParent(preferred_username)`
       **nur wenn** `UcsSetting::on_login_fallback === true`.
       Negativ-Cache vorher prüfen (`ucs.jit.miss:<sha1>`).
    5. Wenn weiterhin kein User: Redirect `auth.ucs.pending`.
    6. `abort_unless($user->is_active, 403, 'Konto deaktiviert.');`
    7. `session(['ucs_id_token' => $oidc->accessTokenResponseBody['id_token'] ?? null]);`
    8. `Auth::login($user, remember: true);`
    9. `return redirect()->intended('/dashboard');`
  - `pending()`: View `auth.ucs.pending` mit Hinweis
    „Konto wird vorbereitet, bitte in einigen Minuten erneut versuchen“,
    Auto-Refresh nach 60 s.
  - `logout()`:
    - `$idToken = session()->pull('ucs_id_token');`
    - `Auth::logout()`, Session invalidieren.
    - Redirect zu
      `KeycloakSetting::base_url . '/realms/' . realm . '/protocol/openid-connect/logout?id_token_hint=' . $idToken . '&post_logout_redirect_uri=' . url('/')`.

### View

- [ ] `resources/views/auth/ucs/pending.blade.php` (minimaler Hinweistext + Refresh).

### Login-Seite

- [ ] Auf der bestehenden Login-Seite Button **„Anmelden mit Schul-Login (UCS)“** ergänzen, sichtbar **nur** wenn
  `KeycloakSetting::enabled === true`.
- [ ] Klassisches Passwort-Login bleibt parallel verfügbar
  (Konzept §11 Pkt. 8).

### Sicherheit

- [ ] Negativ-Cache: `ucs.jit.miss:<sha1(lowercase username)>`, TTL 60 s,
  bei erfolgreichem Sync `Cache::forget()`.
- [ ] State-Validierung des OIDC-Flows (von Socialite default abgedeckt; in PR-Description erwähnen).

## Gelingenskriterien

1. **Primary-Match-Test:** User existiert mit `ucs_uuid='abc'`, OIDC liefert `sub='abc'` → `Auth::user()` ist der korrekte User, kein API-Call an Kelvin.
2. **Secondary-Match-Test:** User existiert nur mit `ucs_username='foo'`, OIDC liefert `sub='abc', preferred_username='foo'` → User wird gefunden, `ucs_uuid='abc'` ist anschließend gesetzt.
3. **JIT-Test:** Unbekannter User, `UcsSyncService` (gemockt) liefert frisch angelegten User → Login erfolgreich.
4. **Pending-Test:** Unbekannter User, `UcsSyncService` wirft Timeout-Exception → Redirect auf `auth.ucs.pending`, **kein** 500.
5. **Negativ-Cache:** Zweiter Login-Versuch mit demselben unbekannten Username innerhalb 60 s ruft **nicht** erneut `KelvinClient::findUser` auf.
6. **`is_active=false` blockt:** Provisionierter, aber deaktivierter User → HTTP 403.
7. **Single-Logout-URL:** wird korrekt zusammengesetzt, enthält `id_token_hint` und `post_logout_redirect_uri`.
8. **RateLimit:** 31. Request innerhalb 60 s gegen `/auth/ucs/callback` von derselben IP → HTTP 429.
9. **`Feature-Flag aus`:** Bei `UcsSetting::on_login_fallback=false` wird JIT übersprungen; unbekannter User landet sofort auf Pending.
10. **E-Mail-Match passiert nie** (Regression-Assertion): Test mit OIDC-Payload, der eine bekannte lokale E-Mail enthält, aber unbekannte UUID/Username → kein Login, Redirect Pending.

## Out of Scope

- Bulk-Sync → Paket 04/05.
- UI für `UcsSetting` → Paket 07.
- E-Mail-Konflikt-Dashboard-Widget (§15.5) → eigenes Folge-Ticket.

## Aufwand

M – ca. 2 Personentage.

