# Kelvin REST API – Endpunkte & OIDC-Login-Flow

Dieses Dokument beschreibt alle HTTP-Aufrufe, die das System gegen die
**UCS@school Kelvin REST API** sowie gegen den **Keycloak/Konnect-IdP** durchführt.

> **Implementierung:** `App\Services\Ucs\KelvinClient` (Kelvin-Aufrufe)  
> **Implementierung:** `App\Http\Controllers\Auth\UcsLoginController` (OIDC-Flow)  
> **Konfiguration:** `App\Settings\UcsSetting` · `App\Settings\KeyCloakSetting`

---

## Inhaltsverzeichnis

1. [Token-Authentifizierung (Kelvin)](#1-token-authentifizierung)
2. [Schulen auflisten](#2-schulen-auflisten)
3. [Eltern auflisten – GET /users/ (legal_guardian)](#3-eltern-auflisten-get-users--legal_guardian)
4. [Schüler auflisten – GET /users/ (student)](#4-schüler-auflisten-get-users--student)
5. [Einzelner Benutzer – GET /users/{username}](#5-einzelner-benutzer-get-users-username)
6. [Klassen auflisten (optional / Phase 2)](#6-klassen-auflisten-optional--phase-2)
7. [OIDC-Login-Flow (Keycloak / UCS-SSO)](#7-oidc-login-flow-keycloak--ucs-sso)
8. [Netzwerk-Freigabe & Sicherheitshinweise](#8-netzwerk-freigabe--sicherheitshinweise)
9. [Fehlerbehandlung & Retry-Strategie](#9-fehlerbehandlung--retry-strategie)

---

## 1. Token-Authentifizierung

**Nur für Kelvin-API-Zugriff. Kein OIDC-Flow.**

```
POST https://<ucs-fqdn>/ucsschool/kelvin/token
Content-Type: application/x-www-form-urlencoded
```

### Request-Body

| Feld       | Typ    | Pflicht | Beschreibung                                    |
|------------|--------|---------|-------------------------------------------------|
| `username` | string | ✅       | Service-Account-Benutzername (`UcsSetting::kelvin_username`) |
| `password` | string | ✅       | Service-Account-Passwort (`UcsSetting::kelvin_password`, AES-verschlüsselt in DB) |

### Response (200 OK)

```json
{
  "access_token": "<JWT>",
  "token_type": "bearer"
}
```

### Besonderheiten

- Der Token-Endpunkt liegt **außerhalb** des `/v1/`-Namespace.
- `kelvin_base_url` = `https://ucs-host/ucsschool/kelvin/v1`  
  → tokenUrl() schneidet `/v1` ab → `https://ucs-host/ucsschool/kelvin/token`
- Der Token wird **AES-verschlüsselt** im Cache gespeichert (Key: `ucs.kelvin.token`).
- TTL: `UcsSetting::kelvin_token_ttl` − 60 Sekunden (Puffer). Standard: 3240 s (54 min).
- Bei **HTTP 401** nach einem gültigen Token: einmaliger Force-Refresh, dann Exception.
- Bei **HTTP 401/403** auf den Token-Endpunkt: `KelvinAuthException`.

### Code-Referenz

```
KelvinClient::token()
KelvinClient::tokenUrl()
```

---

## 2. Schulen auflisten

Wird als **Connectivity-Check** (Ping) und für die vollständige Schulliste genutzt.

```
GET https://<ucs-fqdn>/ucsschool/kelvin/v1/schools/
Authorization: Bearer <token>
Accept: application/json
```

### Query-Parameter (Ping)

| Parameter | Wert | Beschreibung                       |
|-----------|------|------------------------------------|
| `limit`   | `1`  | Nur ein Eintrag – reiner Ping-Test |

### Query-Parameter (vollständige Liste)

| Parameter | Wert                            | Beschreibung              |
|-----------|---------------------------------|---------------------------|
| `limit`   | `UcsSetting::kelvin_page_size`  | Anzahl Schulen pro Seite (Standard: 200) |

### Response (200 OK)

```json
[
  {
    "name": "GS-XY",
    "url": "https://ucs-host/ucsschool/kelvin/v1/schools/GS-XY",
    "display_name": "Grundschule XY",
    "educational_servers": ["ucs-eddu01"],
    "ou_admin_group": "...",
    "class_share_file_server": "...",
    "home_share_file_server": "...",
    "udm_properties": {}
  }
]
```

### Code-Referenz

```
KelvinClient::ping()        → GET /schools/?limit=1
KelvinClient::listSchools() → GET /schools/?limit=<page_size>
```

---

## 3. Eltern auflisten – GET /users/ (legal_guardian)

Wird im **Bulk-Sync** (`UcsSyncService::run()`) verwendet.  
Der Endpunkt liefert **alle Einträge auf einmal** (kein serverseitiges Paging).

```
GET https://<ucs-fqdn>/ucsschool/kelvin/v1/users/
Authorization: Bearer <token>
Accept: application/json
```

### Query-Parameter

| Parameter | Wert              | Pflicht | Beschreibung                               |
|-----------|-------------------|---------|--------------------------------------------|
| `role`    | `legal_guardian`  | ✅       | Filtert auf Erziehungsberechtigte          |
| `school`  | z. B. `GS-XY`     | ✅       | Schul-Kürzel (`UcsSetting::school`)        |

> ⚠️ `limit`/`offset` werden von der Kelvin-API für `/users/` **ignoriert** und  
> werden daher **nicht** gesendet. Die API liefert immer alle Einträge als ein JSON-Array.

### Response (200 OK)

```json
[
  {
    "username": "max.mustermann",
    "record_uid": "550e8400-e29b-41d4-a716-446655440000",
    "firstname": "Max",
    "lastname": "Mustermann",
    "email": "max.mustermann@example.de",
    "school": "GS-XY",
    "roles": ["legal_guardian"],
    "legal_wards": [
      "https://ucs-host/ucsschool/kelvin/v1/users/lisa.mustermann"
    ],
    "url": "https://ucs-host/ucsschool/kelvin/v1/users/max.mustermann"
  }
]
```

### DTO-Mapping

Response-Array wird in `KelvinUserDto::fromArray()` deserialisiert:

| JSON-Feld      | DTO-Property   | Typ              |
|----------------|----------------|------------------|
| `username`     | `username`     | `string`         |
| `record_uid`   | `recordUid`    | `string`         |
| `firstname`    | `firstname`    | `string`         |
| `lastname`     | `lastname`     | `string`         |
| `email`        | `email`        | `?string`        |
| `school`       | `school`       | `string`         |
| `roles`        | `roles`        | `list<string>`   |
| `legal_wards`  | `legalWards`   | `list<string>`   |
| `url`          | `url`          | `?string`        |

### Code-Referenz

```
KelvinClient::listParents(string $school) → Generator<KelvinUserDto>
KelvinClient::paginateUsers('legal_guardian', $school)
```

---

## 4. Schüler auflisten – GET /users/ (student)

Wird im **Bulk-Sync** (`UcsSyncService::buildStudentMap()`) verwendet.  
Wie bei Eltern: die API liefert alle Schüler auf einmal ohne Pagination.

```
GET https://<ucs-fqdn>/ucsschool/kelvin/v1/users/
Authorization: Bearer <token>
Accept: application/json
```

### Query-Parameter

| Parameter | Wert        | Pflicht | Beschreibung                          |
|-----------|-------------|---------|---------------------------------------|
| `role`    | `student`   | ✅       | Filtert auf Schüler                   |
| `school`  | z. B. `GS-XY` | ✅     | Schul-Kürzel (`UcsSetting::school`)   |

### Response (200 OK)

```json
[
  {
    "username": "lisa.mustermann",
    "record_uid": "660f9500-f30c-52e5-b827-557766551111",
    "firstname": "Lisa",
    "lastname": "Mustermann",
    "school": "GS-XY",
    "roles": ["student"],
    "school_classes": {
      "GS-XY": ["3a"]
    },
    "url": "https://ucs-host/ucsschool/kelvin/v1/users/lisa.mustermann"
  }
]
```

### DTO-Mapping

Response-Array wird in `KelvinStudentDto::fromArray()` deserialisiert:

| JSON-Feld        | DTO-Property     | Typ                            |
|------------------|------------------|--------------------------------|
| `username`       | `username`       | `string`                       |
| `record_uid`     | `recordUid`      | `string`                       |
| `firstname`      | `firstname`      | `string`                       |
| `lastname`       | `lastname`       | `string`                       |
| `school`         | `school`         | `string`                       |
| `roles`          | `roles`          | `list<string>`                 |
| `school_classes` | `schoolClasses`  | `array<string, list<string>>`  |
| `url`            | `url`            | `?string`                      |

**Kombiklassen:** Wenn `school_classes["GS-XY"]` mehr als einen Klassennamen enthält,
wird die erste Klasse (alphabetisch sortiert) als `class_id` gesetzt.

### Code-Referenz

```
KelvinClient::listStudents(string $school) → Generator<KelvinStudentDto>
KelvinClient::paginateUsers('student', $school)
UcsSyncService::buildStudentMap(string $school) → Collection<string, KelvinStudentDto>
```

---

## 5. Einzelner Benutzer – GET /users/{username}

Wird beim **JIT-Sync** (OIDC-Login) und beim **Laden der legal_wards** eines Elternteils
genutzt.

```
GET https://<ucs-fqdn>/ucsschool/kelvin/v1/users/{username}
Authorization: Bearer <token>
Accept: application/json
```

### Path-Parameter

| Parameter  | Beschreibung                                            |
|------------|---------------------------------------------------------|
| `username` | UCS-Benutzername (URL-encoded via `rawurlencode()`)    |

### Timeout

Beim JIT-Login im OIDC-Callback wird ein **harter Timeout-Cap** gesetzt:  
`UcsSetting::on_login_timeout` (Standard: 5 Sekunden) – überschreibt den normalen `kelvin_timeout`.

### Response (200 OK)

Identisch mit einem Eintrag aus den Listen-Endpoints (Abschnitt 3 oder 4).

### Response (404 Not Found)

```json
{ "detail": "No UcsUser object found for username <username>." }
```

→ `KelvinClient::findUser()` gibt `null` zurück.  
→ Der Aufrufer setzt einen Negativ-Cache-Eintrag (Key: `ucs.jit.miss:<username>`, TTL: 60 s).

### Code-Referenz

```
KelvinClient::findUser(string $username, ?int $timeout = null): ?array
UcsSyncService::syncSingleParent(string $username)   → JIT-Sync
```

---

## 6. Klassen auflisten (optional / Phase 2)

Derzeit **nicht im Haupt-Sync-Pfad**. Wird nur bei explizitem Aufruf genutzt
(z. B. Admin-Diagnose). Unterstützt serverseitige **Pagination** mit `limit`/`offset`.

```
GET https://<ucs-fqdn>/ucsschool/kelvin/v1/classes/
Authorization: Bearer <token>
Accept: application/json
```

### Query-Parameter

| Parameter | Typ     | Pflicht | Beschreibung                                    |
|-----------|---------|---------|-------------------------------------------------|
| `school`  | string  | ✅       | Schul-Kürzel                                    |
| `limit`   | integer | ✅       | Einträge pro Seite (`UcsSetting::kelvin_page_size`) |
| `offset`  | integer | ✅       | Seitenversatz (beginnt bei 0)                   |

### Pagination-Logik

- `KelvinClient::listClasses()` iteriert in Schritten von `limit` über alle Seiten.
- Hard-Cap: **200 Seiten** (`KelvinClient::MAX_PAGES`).
- Loop-Abbruch: leere Seite **oder** Seite kleiner als `limit`.
- Bei Erreichen des Hard-Caps: Warning-Log.

### Response (200 OK)

```json
[
  {
    "name": "GS-XY-3a",
    "school": "GS-XY",
    "url": "https://ucs-host/ucsschool/kelvin/v1/classes/GS-XY-3a",
    "users": [
      "https://ucs-host/ucsschool/kelvin/v1/users/lisa.mustermann"
    ],
    "udm_properties": {}
  }
]
```

### Code-Referenz

```
KelvinClient::listClasses(string $school): Collection
```

---

## 7. OIDC-Login-Flow (Keycloak / UCS-SSO)

Diese Endpunkte sind **lokal** in der Laravel-App registriert. Sie implementieren
den OAuth2/OIDC-Authorization-Code-Flow via `laravel/socialite` +
`socialiteproviders/keycloak`.

**Konfiguration:** `App\Settings\KeyCloakSetting` (DB-Settings, Gruppe `keycloak`)  
**Socialite-Driver:** `ucs` (Alias auf `keycloak`-Provider)

---

### 7.1 Redirect zum IdP

```
GET /auth/ucs/redirect
```

| Eigenschaft  | Wert                                            |
|--------------|-------------------------------------------------|
| Route-Name   | `auth.ucs.redirect`                             |
| Middleware   | `guest`                                         |
| Controller   | `UcsLoginController::redirect()`                |

**Ablauf:**
1. Prüft `UcsSetting::enabled` und `KeyCloakSetting::enabled`.
2. Leitet den Browser via Socialite zu folgender URL weiter:

```
https://<keycloak-base_url>/realms/<realm>/protocol/openid-connect/auth
  ?client_id=<client_id>
  &redirect_uri=<redirect_uri>
  &response_type=code
  &scope=openid profile email
  &state=<csrf-state>
```

---

### 7.2 Callback-Verarbeitung

```
GET /auth/ucs/callback
```

| Eigenschaft  | Wert                                                          |
|--------------|---------------------------------------------------------------|
| Route-Name   | `auth.ucs.callback`                                           |
| Middleware   | `throttle:ucs-jit`, `guest`                                   |
| Controller   | `UcsLoginController::callback()`                              |

**IdP-seitiger Token-Exchange (intern via Socialite):**

```
POST https://<keycloak-base_url>/realms/<realm>/protocol/openid-connect/token
Content-Type: application/x-www-form-urlencoded

grant_type=authorization_code
&code=<auth-code>
&client_id=<client_id>
&client_secret=<client_secret>
&redirect_uri=<redirect_uri>
```

**Extrahierte Claims:**

| OIDC-Claim           | Verwendung                                           |
|----------------------|------------------------------------------------------|
| `sub`                | Primärer Match: `users.ucs_uuid`                     |
| `preferred_username` | Sekundärer Match / JIT-Sync: `users.ucs_username`    |
| `id_token`           | Single-Logout-Hint, in Session gespeichert           |

**Match-Reihenfolge (§6.2):**

```
1. users.ucs_uuid == sub                         (kein API-Call)
2. users.ucs_username == preferred_username      (UUID-Backfill)
3. JIT: UcsSyncService::syncSingleParent()       (nur wenn on_login_fallback=true)
4. Kein Match → Weiterleitung zu /auth/ucs/pending
```

> ⚠️ **Nie per E-Mail matchen** – E-Mail-Adressen können in UCS dupliziert sein.

**Modus B – Account-Linking** (eingeloggter User ruft Endpunkt auf):  
Schreibt `ucs_uuid` + `ucs_username` auf den aktuellen Auth-User und startet
`SyncSingleUcsParentJob::dispatchAfterResponse()`.

---

### 7.3 Pending-Seite

```
GET /auth/ucs/pending
```

| Eigenschaft  | Wert                                         |
|--------------|----------------------------------------------|
| Route-Name   | `auth.ucs.pending`                           |
| Middleware   | keine (Gäste **und** eingeloggte Nutzer)      |
| Controller   | `UcsLoginController::pending()`              |

Zeigt `resources/views/auth/ucs/pending.blade.php` – eine Warteseite mit
Auto-Refresh, wenn das Konto noch nicht provisioniert ist.

---

### 7.4 Single-Logout

```
POST /auth/ucs/logout
```

| Eigenschaft  | Wert                                         |
|--------------|----------------------------------------------|
| Route-Name   | `auth.ucs.logout`                            |
| Middleware   | `auth`                                       |
| Controller   | `UcsLoginController::logout()`               |

**Ablauf:**
1. Lokaler Laravel-Logout (`Auth::logout()`, Session-Invalidierung).
2. Redirect zum Keycloak Single-Logout-Endpunkt (falls `id_token` in Session):

```
GET https://<keycloak-base_url>/realms/<realm>/protocol/openid-connect/logout
  ?id_token_hint=<id_token>
  &post_logout_redirect_uri=<app-root-url>
```

> `id_token_hint` ist ab Keycloak 18+ für stummen Single-Logout erforderlich.

---

### 7.5 Legacy Keycloak-Routes (Rückwärtskompatibilität)

> Diese Routen existieren für ältere Deployments, die noch den direkten Keycloak-Flow nutzen.
> **Für neue Installationen wird ausschließlich der UCS-OIDC-Flow (7.1–7.4) empfohlen.**

| Methode | Pfad                       | Controller                                       |
|---------|----------------------------|--------------------------------------------------|
| GET     | `/login/keycloak`          | `LoginController::redirectToKeycloak()`          |
| GET     | `/login/keycloak/callback` | `LoginController::handleKeycloakCallback()`      |

**Matching-Logik (Legacy):** Suche per `users.email`. Bei fehlender E-Mail oder
nicht erlaubter Domain → Fehler. Neuer User wird automatisch angelegt.

---

## 8. Netzwerk-Freigabe & Sicherheitshinweise

| Von           | Nach                                        | Port  | Protokoll | Zweck                        |
|---------------|---------------------------------------------|-------|-----------|------------------------------|
| App-Server    | `<ucs-fqdn>`                                | 443   | HTTPS/TLS | Kelvin-API + Token-Endpunkt  |
| App-Server    | `<keycloak-base_url>`                       | 443   | HTTPS/TLS | OIDC Token-Exchange          |
| Browser       | `<keycloak-base_url>`                       | 443   | HTTPS/TLS | OIDC Redirect / SLO          |

**TLS ist immer Pflicht** (`verify: true`). `verify: false` darf **niemals** in der Produktion
gesetzt werden.

**Kelvin-Credentials** (`kelvin_password`) werden AES-verschlüsselt in der Datenbank gespeichert
(Spatie Settings `addEncrypted()`). Im Cache wird der Token ebenfalls via `Crypt::encryptString()`
verschlüsselt abgelegt.

---

## 9. Fehlerbehandlung & Retry-Strategie

### Exception-Typen (Kelvin)

| Exception                   | HTTP-Status    | Bedeutung                                              |
|-----------------------------|----------------|--------------------------------------------------------|
| `KelvinAuthException`       | 401, 403        | Authentifizierung/Autorisierung fehlgeschlagen          |
| `KelvinRateLimitException`  | 429             | Rate-Limit erschöpft nach allen Retry-Versuchen        |
| `KelvinUnavailableException`| 5xx, Timeout, DNS | API nicht erreichbar (Netzwerk, Proxy, Server-Fehler) |

### Retry-Logik (alle Kelvin-GET-Anfragen)

```
Http::retry(3, 500ms, retryOn: [5xx, 429, ConnectionException], throw: false)
```

- 3 Versuche insgesamt, 500 ms Basisverzögerung (exponentiell durch Laravel).
- Nach 3 Fehlschlägen: letzte Response an `assertTypedException()` übergeben.
- Netzwerkfehler (Timeout, DNS, Proxy): ebenfalls Retry.

### Token-Force-Refresh

Bei **HTTP 401** auf einem API-Endpunkt (nicht Token-Endpunkt):

1. Cache-Eintrag löschen (`Cache::forget('ucs.kelvin.token')`).
2. Neuen Token holen (`token(forceRefresh: true)`).
3. Request **einmal** wiederholen.
4. Erneut 401/403 → `KelvinAuthException`.

### Negativ-Cache (JIT-Sync)

Nach einem fehlgeschlagenen JIT-Sync (`syncSingleParent()`) oder einem 404:

- Cache-Key: `ucs.jit.miss:<username>`
- TTL: 60 s (UcsLoginController), 5 min (Fehler in syncSingleParent), 15 min (404 in syncSingleParent)
- Solange der Negativ-Cache aktiv ist, wird kein weiterer JIT-Sync versucht →
  sofortige Weiterleitung zur Pending-Seite.

### Korrelations-ID

Jede `KelvinClient`-Instanz erzeugt beim Konstruktor eine UUID (`Str::uuid()`).
Diese wird als `X-Correlation-Id`-Header mit **jedem** Request gesendet und in
alle Log-Einträge eingebettet → ermöglicht die Verfolgung eines kompletten
Aufruf-Chains im Log.

**Log-Channel:** `ucs` (konfiguriert in `config/logging.php`)

---

*Letzte Aktualisierung: 2026-05-27*

