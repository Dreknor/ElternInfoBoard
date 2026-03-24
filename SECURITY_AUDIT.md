# Security Audit – Elterninfo Laravel-Anwendung
**Erstellt:** 2026-03-07  
**Zuletzt aktualisiert:** 2026-03-07  
**Auditor:** GitHub Copilot (automatisiertes Audit)  
**Basis:** Vollständige Code-Analyse + `composer audit`

---

## Status-Übersicht

| ID | Titel | Priorität | Status |
|----|-------|-----------|--------|
| KRIT-1 | Unauthentifizierter Datei-Download | 🔴 KRITISCH | ✅ Behoben |
| KRIT-2 | API-Key Timing-Attack + Rate-Limiting | 🔴 KRITISCH | ✅ Behoben |
| KRIT-3 | Stundenplan-Import Rate-Limiting | 🔴 KRITISCH | ✅ Behoben |
| KRIT-4 | SchoolYear außerhalb Auth-Middleware | 🔴 KRITISCH | ✅ Behoben |
| KRIT-5 | CVE-2025-64500 Symfony HTTP Foundation | 🔴 KRITISCH | ✅ Bereits auf v7.4.7 |
| HOCH-1 | Magic-Login-Links once-use + Rate-Limit | 🟠 HOCH | ✅ Behoben |
| HOCH-2 | Sanctum Token-Expiration + Prefix | 🟠 HOCH | ✅ Behoben |
| HOCH-3 | E-Mail-Passwort Klartext in DB | 🟠 HOCH | ✅ Behoben (encrypted()) |
| HOCH-4 | XSS in Blade-Templates ({!! !!}) | 🟠 HOCH | ⚠️ OFFEN – manuelle Prüfung nötig |
| HOCH-5 | Sentry SQL-Bindings (DSGVO) | 🟠 HOCH | ✅ Behoben |
| HOCH-6 | Import-Passwörter = aktuelles Datum | 🟠 HOCH | ✅ Behoben |
| HOCH-7 | loginAsUser GET→POST + Middleware | 🟠 HOCH | ✅ Behoben |
| MITTEL-1 | public/docs/ öffentlich | 🟡 MITTEL | ✅ Behoben (Auth-Schutz) |
| MITTEL-2 | iCal Rate-Limiting | 🟡 MITTEL | ✅ Behoben |
| MITTEL-3 | Sucheingabe ohne max-Länge | 🟡 MITTEL | ✅ Behoben (max:100) |
| MITTEL-4 | FileController::delete() ohne Ownership | 🟡 MITTEL | ✅ Behoben |
| MITTEL-5 | Log-Level debug in Produktion | 🟡 MITTEL | ✅ Behoben (password_hash_length entfernt) |
| MITTEL-6 | Keycloak-Config in Logs | 🟡 MITTEL | ✅ Behoben |
| MITTEL-7 | CVE-2026-30838 league/commonmark | 🟡 MITTEL | ✅ Behoben (2.8.1) |
| MITTEL-8 | Passwort-Validierung schwach | 🟡 MITTEL | ✅ Behoben (confirmed + mixedCase) |
| INFO-1 | Abandoned Packages | 🔵 INFO | ⚠️ OFFEN – langfristig migrieren |
| INFO-2 | AWS SDK CVE-2025-14761 | 🔵 INFO | ⚠️ OFFEN – bei Bedarf updaten |
| INFO-3 | Audit-Log sensitive Felder | 🔵 INFO | ✅ Behoben |
| INFO-4 | loginAsUser ohne Audit-Log | 🔵 INFO | ✅ Behoben |
| INFO-5 | me()-Endpoint Datensparsamkeit | 🔵 INFO | ✅ Behoben |

**Behobene Befunde: 21 von 25**

---

## ⚠️ Noch offene Punkte

---

### [HOCH-4] XSS durch unescaped Ausgabe von Nutzerdaten (`{!! !!}`) – MANUELL PRÜFEN

**Dateien:** Mehrere Blade-Templates  
**Status:** Offen – erfordert Entscheidung ob HTML bewusst erlaubt ist

**Kritische Stellen:**

| Datei | Inhalt | Empfehlung |
|-------|--------|------------|
| `nachrichten/nachricht.blade.php` L153,239,255 | `{!! $nachricht->news !!}` | Rich-Text → HTMLPurifier |
| `rueckmeldungen/show.blade.php` L46 | `{!! $userRueckmeldung->text !!}` | Kein HTML nötig → `{{ }}` |
| `pdf/krankmeldungen.blade.php` L34 | `{!! $Meldung->kommentar !!}` | Kein HTML nötig → `{{ }}` |
| `userrueckmeldung/editAbfrage.blade.php` L57 | `{!! $userRueckmeldung->answers->...->answer !!}` | Kein HTML nötig → `{{ }}` |
| `schickzeiten/infos.blade.php` L2 | `{!! $vorgaben->schicken_text !!}` | Admin-Input → HTMLPurifier |
| `nachrichten/edit.blade.php` L94 | `{!! $post->news !!}` | Rich-Text → HTMLPurifier |
| `listen/listenEintragExport.blade.php` L29 | `{!! $liste->comment !!}` | Kein HTML nötig → `{{ }}` |

**Empfohlene Maßnahmen:**
```bash
composer require ezyang/htmlpurifier
```

Für alle Stellen wo kein HTML nötig ist (Kommentare, Nutzerantworten):
```blade
{{-- Vorher: --}}
{!! $rueckmeldung->text !!}
{{-- Nachher: --}}
{{ $rueckmeldung->text }}
```

Für Rich-Text-Inhalte (Nachrichten-Body):
```php
// app/Helpers/HtmlHelper.php
use HTMLPurifier;
use HTMLPurifier_Config;

function purify(string $html): string {
    $config = HTMLPurifier_Config::createDefault();
    $config->set('HTML.Allowed', 'b,i,u,strong,em,p,br,ul,ol,li,a[href],h1,h2,h3');
    return (new HTMLPurifier($config))->purify($html);
}
```

```blade
{{-- In nachrichten/nachricht.blade.php: --}}
{!! purify($nachricht->news) !!}
```

---

### [INFO-1] Abandoned Packages – langfristig migrieren

| Package | Empfohlener Ersatz |
|---------|--------------------|
| `spatie/data-transfer-object` | `spatie/laravel-data` |
| `web-token/jwt-*` (5 Pakete) | `web-token/jwt-library` |
| `fgrosse/phpasn1` | Kein direkter Ersatz |

---

### [INFO-2] AWS SDK CVE-2025-14761

Nur relevant wenn S3-Verschlüsselung (`SseC` oder `CSE`) eingesetzt wird.

```bash
composer update aws/aws-sdk-php  # Ziel: >= 3.368.0
```

---

## Notwendige .env-Einstellungen für Produktion

Diese Einstellungen **müssen** auf dem Produktionsserver gesetzt sein:

```env
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=warning

SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

LPL_USE_ONCE=true

# Sanctum
SANCTUM_TOKEN_EXPIRATION=43200
SANCTUM_TOKEN_PREFIX=elterninfo_

# Sentry
SENTRY_SQL_BINDINGS=false

# Import-Passwörter – MÜSSEN gesetzt werden (kein Fallback mehr)!
PW_IMPORT_ELTERN=<random-64-char-string>
PW_IMPORT_AUFNAHME=<random-64-char-string>
PW_IMPORT_MITARBEITER=<random-64-char-string>
PW_IMPORT_VEREIN=<random-64-char-string>
```

---

## Notwendige DB-Migration ausführen

Nach dem Deployment muss folgende Migration ausgeführt werden, um das bestehende E-Mail-Passwort zu verschlüsseln:

```bash
php artisan migrate
```

Die Migration `2026_03_07_085230_encrypt_mail_password_in_settings` verschlüsselt das SMTP-Passwort in der `settings`-Tabelle automatisch.



## 🔴 KRITISCH – Sofortiger Handlungsbedarf

---

### [KRIT-1] Unauthentifizierter Dateizugriff via UUID – Alle Mediendateien öffentlich abrufbar

**Datei:** `routes/api.php` Zeile 62, `app/Http/Controllers/API/ImageController.php`  
**CVSS-Schätzung:** 9.1 (Critical)

**Problem:**  
Die Route `GET /api/files/{media_uuid}/download` ist **ohne jede Authentifizierung** zugänglich. Jeder, der eine UUID kennt (oder sie rät/bruteforced), kann beliebige Mediendateien herunterladen – PDFs, Bilder, Dokumente, Krankmeldungs-Anhänge, etc.

```php
// routes/api.php Zeile 62 – außerhalb jeder auth:sanctum-Gruppe!
Route::get('files/{media_uuid}/download', [ImageController::class, 'getFileByUuid'])->name('api.files.download');
```

```php
// app/Http/Controllers/API/ImageController.php
// Kommentar '$this->middleware('auth:sanctum');' ist auskommentiert!
public function __construct()
{
    // $this->middleware('auth:sanctum');
}
public function getFileByUuid(Request $request, $uuid)
{
    $media = Media::where('uuid', $uuid)->firstOrFail(); // keine Auth-Prüfung!
    return response()->file($media->getPath(), [...]);
}
```

**Risiko:** Datenleck aller hochgeladenen Dateien, darunter potenziell DSGVO-relevante Dokumente (Krankmeldungen mit ärztlichen Bescheinigungen, Sorgerechts-Dokumente etc.).

**Fix:**
```php
// routes/api.php – Route in die auth:sanctum-Gruppe verschieben
Route::middleware('auth:sanctum')->group(function () {
    // ... bestehende Routen ...
    Route::get('files/{media_uuid}/download', [ImageController::class, 'getFileByUuid'])
        ->name('api.files.download');
});
```

```php
// app/Http/Controllers/API/ImageController.php – Middleware wieder aktivieren
// UND: Ownership-Prüfung hinzufügen: Darf dieser User diese Datei sehen?
public function getFileByUuid(Request $request, $uuid)
{
    $media = Media::where('uuid', $uuid)->firstOrFail();
    // TODO: Prüfen ob der authentifizierte User Zugriff auf diese Datei hat
    // (z.B. ob die Datei zu einer Gruppe gehört, in der der User Mitglied ist)
    return response()->file($media->getPath(), [...]);
}
```

---

### [KRIT-2] API-Schlüssel im Request-Body – Timing-Attack und Plaintext-Übertragung

**Datei:** `app/Http/Requests/ApiImportVertretungsRequest.php`, alle `ApiImport*`-Requests, `routes/api.php` Zeilen 24–34  
**CVSS-Schätzung:** 8.6 (High/Critical)

**Problem 1 – Loose-Comparison:**  
```php
if ($vertretung['key'] == config('app.api_key')) { // == statt ===
```
PHP's `==` kann bei bestimmten Typen zu Typ-Coercion führen. Muss `===` sein.

**Problem 2 – Kein Rate-Limiting:**  
Die 13 Routen des `VertretungsplanConnectController` (POST/PUT/DELETE auf `/api/vertretungen/`, `/api/news/`, `/api/week/`, `/api/absences/`) haben **kein eigenes Rate-Limiting** (das globale API-Limit ist 60 req/min). Ein Angreifer kann den API-Key per Brute-Force ermitteln.

**Problem 3 – API-Key im JSON-Body:**  
Der Schlüssel wird im Request-Body übertragen. Er erscheint in Logs, Load-Balancer-Aufzeichnungen etc.

**Problem 4 – API-Key als simpler String:**  
`config('app.api_key')` kommt aus der `.env`, wird nicht gehasht verglichen.

**Fix:**
```php
// app/Http/Requests/ApiImportVertretungsRequest.php
public function authorize(): bool
{
    // 1. Key aus Header (sicherer als Body)
    $apiKey = $this->header('X-API-Key') ?? $this->bearerToken();
    if (!$apiKey) {
        return false;
    }
    // 2. Timing-sicherer Vergleich
    return hash_equals(config('app.api_key'), $apiKey);
}
```

```php
// app/Providers/AppServiceProvider.php – eigenes Rate-Limit für Vertretungsplan-API
RateLimiter::for('vertretungsplan-api', function (Request $request) {
    return Limit::perMinute(20)->by($request->ip());
});
```

```php
// routes/api.php
Route::middleware('throttle:vertretungsplan-api')->group(function () {
    Route::post('vertretungen/', [...]);
    // ... alle anderen Vertretungsplan-Routen
});
```

---

### [KRIT-3] Stundenplan-Import ohne Authentifizierung zugänglich

**Datei:** `routes/api.php` Zeilen 39–41, `app/Http/Requests/ApiImportStundenplanRequest.php`  
**CVSS-Schätzung:** 8.1 (High)

**Problem:**  
```php
// routes/api.php – ebenfalls ohne auth:sanctum!
Route::post('stundenplan/import', [\App\Http\Controllers\API\StundenplanImportController::class, 'import']);
Route::get('stundenplan/status', [\App\Http\Controllers\API\StundenplanImportController::class, 'status']);
```

`/api/stundenplan/import` akzeptiert beliebig großen JSON-Body (`StundenplanDataAdapter::normalize()`). Der API-Key kann als URL-Parameter (`?key=xxx`), Header, Bearer-Token oder im JSON-Body übergeben werden. Besonders problematisch: Der Key als URL-Parameter landet garantiert in Server-Logs.

**Risiko:** 
- DoS durch übermäßig große JSON-Payloads
- Manipulation des Stundenplans (falsche Informationen an Schüler/Eltern)
- Key-Leakage über Server-Logs bei URL-Parameter-Nutzung

**Fix:**
```php
// app/Http/Requests/ApiImportStundenplanRequest.php – URL-Parameter-Support entfernen
$apiKey = $this->header('X-API-Key') ?? $this->bearerToken();

// routes/api.php – Rate-Limiting hinzufügen
Route::post('stundenplan/import', [...])
    ->middleware('throttle:10,1'); // max 10 Requests pro Minute
```

---

### [KRIT-4] Schuljahreswechsel ohne Authentifizierungs-Middleware – Massenlesung möglich

**Datei:** `routes/web.php` Zeilen 552–558, `app/Http/Controllers/SchoolYearController.php`  
**CVSS-Schätzung:** 8.4 (High)

**Problem:**  
Die drei SchoolYear-Routen befinden sich **außerhalb aller `Route::middleware('auth')`-Gruppen**:
```php
// routes/web.php – ganz am Ende, ohne auth-Middleware!
Route::get('settings/schoolyear', ...)->name('schoolyear.index');
Route::post('settings/schoolyear/process', ...)->name('schoolyear.process');
Route::delete('settings/schoolyear/massDelete', ...)->name('schoolyear.massDelete');
```

Der Controller prüft zwar intern `auth()->user()->can('schoolyear.change')`, aber wenn kein User eingeloggt ist, wirft `auth()->user()` eine Null-Pointer-Exception – statt einer ordentlichen Weiterleitung zum Login. Das `massDelete` führt Massen-Löschoperationen an Usern durch.

**Fix:**
```php
// routes/web.php – in die auth-Middleware-Gruppe verschieben
Route::middleware(['auth', 'password_expired', 'permission:schoolyear.change'])->group(function () {
    Route::get('settings/schoolyear', [SchoolYearController::class, 'index'])->name('schoolyear.index');
    Route::post('settings/schoolyear/process', [SchoolYearController::class, 'process'])->name('schoolyear.process');
    Route::delete('settings/schoolyear/massDelete', [SchoolYearController::class, 'massDelete'])->name('schoolyear.massDelete');
});
```

---

### [KRIT-5] Symfony HTTP Foundation – PATH_INFO Authorization Bypass (CVE-2025-64500)

**Datei:** `composer.json` / `composer.lock`  
**CVE:** CVE-2025-64500 | **CVSS:** 8.1 (High)

**Problem:**  
Das Paket `symfony/http-foundation` enthält eine Schwachstelle, bei der `PATH_INFO` so falsch geparst wird, dass URL-Pfade nicht mit `/` beginnen. Das kann Autorisierungsprüfungen umgehen, die auf dem führenden `/` basieren. Laravel nutzt dieses Paket für alle HTTP-Request-Operationen.

```
symfony/http-foundation >=7.0.0,<7.3.7 – betroffen laut composer audit
```

**Fix:**
```bash
composer update symfony/http-foundation
```
Ziel: `>= 7.3.7` (oder `>= 6.4.29` bei 6.x-Verwendung).

---

## 🟠 HOCH – Zeitnaher Handlungsbedarf

---

### [HOCH-1] Magic-Login-Links sind mehrfach verwendbar (Sicherheitsrisiko bei E-Mail-Kompromittierung)

**Datei:** `config/laravel-passwordless-login.php`

**Problem:**  
```php
'login_use_once' => env('LPL_USE_ONCE', false), // Default: false!
```
Ein abgefangener Magic-Login-Link (z.B. durch E-Mail-Forwarding, unsicheres E-Mail-Konto, Browser-History) kann beliebig oft verwendet werden, bis er nach 30 Minuten abläuft.

Zusätzlich: Es gibt kein Rate-Limiting für den Endpunkt `/magic-login` selbst. Ein Angreifer kann für beliebige E-Mail-Adressen Magic-Links anfordern (E-Mail-Bombing).

**Fix:**
```env
# .env
LPL_USE_ONCE=true
```

```php
// routes/web.php oder bootstrap/app.php – Rate-Limit für Magic-Login-Request
Route::post('login', [LoginController::class, 'login'])
    ->middleware('throttle:5,1'); // max 5 Versuche pro Minute
```

---

### [HOCH-2] Sanctum-Tokens laufen nie ab

**Datei:** `config/sanctum.php` Zeile 51

**Problem:**  
```php
'expiration' => null, // Tokens laufen NIEMALS ab
```
Ein einmal ausgestelltes Token (z.B. für ein gestohlenes Gerät) bleibt für immer gültig, bis der User es manuell löscht. Beim Logout wird nur das *aktuelle* Token invalidiert (`currentAccessToken()->delete()`). Alle anderen Token (andere Geräte) bleiben aktiv.

**Fix:**
```php
// config/sanctum.php
'expiration' => 60 * 24 * 30, // 30 Tage Ablaufzeit

// config/sanctum.php – Token-Prefix für Secret-Scanning aktivieren
'token_prefix' => env('SANCTUM_TOKEN_PREFIX', 'elterninfo_'),
```

Außerdem sollte eine Route zum Widerruf aller Tokens eines Users bei Kompromittierung existieren:
```php
// Für Admins: alle Tokens eines Users widerrufen
$user->tokens()->delete();
```

---

### [HOCH-3] E-Mail-Passwort im Klartext in der Datenbank (Settings-Tabelle)

**Datei:** `app/Settings/EmailSetting.php`, `app/Http/Controllers/SettingsController.php` Zeilen 198–208

**Problem:**  
Das SMTP-Passwort wird ohne Verschlüsselung in der `settings`-Tabelle gespeichert:
```php
public ?string $mail_password = null; // Kein 'encrypted' Cast!
```

Jeder mit Datenbankzugriff (DB-Backup, DB-Admin, SQL-Injection) kann das E-Mail-Passwort im Klartext lesen.

**Fix:**
```php
// app/Settings/EmailSetting.php
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

// Option 1: spatie/laravel-settings unterstützt verschlüsselte Properties
// Migrationsskript für bestehende Daten notwendig!
class EmailSetting extends Settings
{
    // Passwort als encrypted markieren (Spatie Settings v3 unterstützt Eloquent-Casts)
    protected $encrypted = ['mail_password'];
    // ... oder auf Umgebungsvariablen umstellen:
    public ?string $mail_password = null; // aus .env lesen, nicht aus DB speichern
}
```

**Empfohlen:** SMTP-Credentials sollten in `.env` verbleiben, nicht in der Datenbank gespeichert werden. Alternativ: `encrypt()`/`decrypt()` beim Lesen/Schreiben einsetzen.

---

### [HOCH-4] XSS durch unescaped Ausgabe von Nutzerdaten (`{!! !!}`)

**Dateien:** Mehrere Blade-Templates  
**Betroffen:** Mind. 20 Stellen mit `{!! !!}` (unescaped HTML-Ausgabe)

**Kritische Stellen (Nutzerdaten direkt ausgegeben):**

| Datei | Zeile | Inhalt | Risiko |
|-------|-------|--------|--------|
| `resources/views/nachrichten/nachricht.blade.php` | 153, 239, 255 | `{!! $nachricht->news !!}` | Beitrags-Inhalt (vom Admin/Autor eingegeben) |
| `resources/views/rueckmeldungen/show.blade.php` | 46 | `{!! $userRueckmeldung->text !!}` | Nutzerantworten auf Rückmeldungen |
| `resources/views/pdf/krankmeldungen.blade.php` | 34 | `{!! $Meldung->kommentar !!}` | Kommentar zu Krankmeldungen |
| `resources/views/userrueckmeldung/editAbfrage.blade.php` | 57 | `{!! $userRueckmeldung->answers->...->answer !!}` | Antworten von Nutzern |
| `resources/views/schickzeiten/infos.blade.php` | 2 | `{!! $vorgaben->schicken_text !!}` | Admin-konfigurierter Text |

**Problem:** Wenn ein Angreifer (oder ein Nutzer mit Schreibrecht) JavaScript in diese Felder einschleust, wird es im Browser aller Nutzer ausgeführt (Stored XSS).

**Fix für jede Stelle – Abwägung erforderlich:**
- Wenn HTML **bewusst** erlaubt ist (Rich-Text-Editor): Einen HTML-Sanitizer einsetzen, z.B. `HTMLPurifier` oder `league/html-to-markdown`.
- Wenn kein HTML nötig: `{{ $nachricht->news }}` (escaped) verwenden.

```bash
composer require ezyang/htmlpurifier
```

```php
// app/Helpers/HtmlHelper.php
function purify(string $html): string {
    $config = HTMLPurifier_Config::createDefault();
    $config->set('HTML.Allowed', 'b,i,u,strong,em,p,br,ul,ol,li,a[href]');
    $purifier = new HTMLPurifier($config);
    return $purifier->purify($html);
}
```

---

### [HOCH-5] Sentry erfasst SQL-Query-Parameter (potentiell sensitive Daten)

**Datei:** `config/sentry.php` Zeile 12

**Problem:**  
```php
'sql_bindings' => true, // SQL-Parameter werden an Sentry gesendet!
```

SQL-Query-Parameter enthalten u.a. E-Mail-Adressen, Namen, Kommentare aus Krankmeldungen und andere DSGVO-relevante Daten. Diese werden an den externen Sentry-Dienst übertragen.

**Risiko:** DSGVO-Verletzung (Übermittlung personenbezogener Daten an Dritte ohne explizite Grundlage), Datenleck bei Sentry-Kompromittierung.

**Fix:**
```php
// config/sentry.php
'breadcrumbs' => [
    'sql_bindings' => env('SENTRY_SQL_BINDINGS', false), // Default: false für Produktion
],
```

---

### [HOCH-6] Import-Passwörter basieren auf dem aktuellen Datum

**Datei:** `config/app.php` Zeilen 30–36

**Problem:**  
```php
'import_eltern' => env('PW_IMPORT_ELTERN', Carbon::now()->format('dmY')),
'import_aufnahme' => env('PW_IMPORT_AUFNAHME', Carbon::now()->format('dmY')),
'import_mitarbeiter' => env('PW_IMPORT_MITARBEITER', Carbon::now()->format('dmY')),
'import_verein' => env('PW_IMPORT_VEREIN', Carbon::now()->format('dmY')),
```

Wenn die Umgebungsvariablen **nicht gesetzt** sind, ist das Importpasswort das aktuelle Datum im Format `ddMMYYYY` (z.B. `07032026`). Das ist:
- Trivial vorhersagbar (256 mögliche Werte pro Jahr)
- Kein Rate-Limiting auf den Import-Endpunkten vorhanden

**Risiko:** Unbefugter Import von Nutzerdaten, Massenanlage/Überschreibung von Benutzerkonten.

**Fix:**
```env
# .env – starke, zufällige Passwörter setzen
PW_IMPORT_ELTERN=<random-64-char-string>
PW_IMPORT_AUFNAHME=<random-64-char-string>
PW_IMPORT_MITARBEITER=<random-64-char-string>
PW_IMPORT_VEREIN=<random-64-char-string>
```

```php
// config/app.php – Kein Fallback auf Datum, Fehler werfen wenn nicht gesetzt
'import_eltern' => env('PW_IMPORT_ELTERN') ?? throw new RuntimeException('PW_IMPORT_ELTERN nicht gesetzt'),
```

---

### [HOCH-7] `loginAsUser` – Fehlerhafter Route-Middleware-Aufruf, keine Schutzmechanismen

**Datei:** `routes/web.php` Zeilen 412–414, `app/Http/Controllers/UserController.php` Zeilen 315–325

**Problem 1 – Falsche Middleware-Syntax:**  
```php
Route::group(['middlewareGroups' => ['can:loginAsUser']], function () {
    Route::get('showUser/{id}', [UserController::class, 'loginAsUser']);
});
```

`middlewareGroups` ist **kein** gültiger Route-Parameter in Laravel. Die Route nutzt `middleware` (nicht `middlewareGroups`). Das bedeutet: Die `can:loginAsUser`-Prüfung wird möglicherweise **nicht** durch die Route selbst erzwungen, sondern greift nur durch den manuellen Check im Controller.

**Problem 2 – Kein CSRF-Schutz bei GET-Request:**  
Die Aktion "Als User anmelden" wird via `GET` ausgeführt. GET-Anfragen sind nicht CSRF-geschützt. Ein Angreifer kann mit einem `<img src="/showUser/1">` in einem beliebigen Dokument diese Aktion triggern, falls der Admin gerade eingeloggt ist.

**Fix:**
```php
// routes/web.php – korrekte Middleware-Syntax
Route::middleware(['auth', 'permission:loginAsUser'])->group(function () {
    Route::post('showUser/{id}', [UserController::class, 'loginAsUser']); // POST statt GET!
});
```

---

## 🟡 MITTEL – Systematisch beheben

---

### [MITTEL-1] Öffentliche API-Dokumentation in Produktion zugänglich (`public/docs/`)

**Datei:** `public/docs/` (existiert und enthält `openapi.yaml`, `collection.json`, `index.html`)

**Problem:**  
Die vollständige API-Dokumentation (inkl. aller Endpunkte, Parameter, Authentifizierungsdetails) ist unter `https://example.com/docs/` öffentlich zugänglich. Sie enthält Details wie `auth:sanctum`-Endpunkte, Felder-Namen, URL-Strukturen usw. – wertvolle Informationen für Angreifer.

**Fix (Nginx/Apache):**
```nginx
# Nginx
location /docs {
    deny all;
    return 403;
}
```

```php
// Oder: In .htaccess oder per Laravel-Route-Schutz
Route::get('docs/{any}', function() { abort(403); })->where('any', '.*');
```

**Langfristig:** `public/docs/` nicht deployen oder nur für eingeloggte Admins zugänglich machen.

---

### [MITTEL-2] iCal-Endpunkte exponieren Termindaten ohne vollständige Prüfung

**Datei:** `routes/web.php` Zeilen 63–64, `app/Http/Controllers/ICalController.php`

**Problem 1 – `{uuid}/ical` ohne Auth:**  
Jeder mit einer User-UUID kann den Kalender dieser Person abrufen – auch wenn `releaseCalendar = false`. Der Controller prüft `$user->releaseCalendar`, aber die Route ist ohne Auth zugänglich. Die UUID ist lang (UUIDv4), aber wenn sie anderweitig exponiert wird (Logs, URLs), ist der Kalender zugänglich.

**Problem 2 – `ical/publicEvents` ohne Auth:**  
Gibt alle als `public` markierten Termine zurück (Name, Datum, Uhrzeit). Ob das gewünscht ist, muss geprüft werden.

**Fix:**
```php
// routes/web.php – iCal mit Auth schützen (oder zumindest Rate-Limiting)
Route::get('{uuid}/ical', [ICalController::class, 'createICal'])
    ->middleware('throttle:30,1'); // Rate-Limiting um Enumeration zu erschweren
```

---

### [MITTEL-3] Sucheingabe ohne Längenbeschränkung – ReDoS / Performance-Angriff möglich

**Datei:** `app/Http/Requests/searchRequest.php`, `app/Http/Controllers/SearchController.php`, `app/Providers/AppServiceProvider.php`

**Problem:**  
Die `searchRequest`-Validierung begrenzt die Suche nicht auf eine maximale Länge:
```php
'suche' => ['required', 'string'], // Kein 'max:...'!
```

Der `whereLike`-Macro in `AppServiceProvider` verwendet `"%{$searchTerm}%"` direkt in mehreren LIKE-Abfragen gleichzeitig. Eine sehr lange Suchanfrage kann zu exzessiver Datenbank-Last führen.

**Fix:**
```php
// app/Http/Requests/searchRequest.php
'suche' => ['required', 'string', 'max:100'],
```

---

### [MITTEL-4] `FileController::delete()` prüft keine Eigentümerschaft

**Datei:** `app/Http/Controllers/FileController.php` Zeilen 41–47, `routes/web.php` Zeile 346

**Problem:**  
```php
Route::delete('file/{file}', [FileController::class, 'delete']); // nur 'auth'-Middleware!

public function delete(Media $file) {
    $file->delete(); // Keine Prüfung ob der User diese Datei besitzen/löschen darf!
    return response()->json(['message' => 'Gelöscht']);
}
```

Jeder eingeloggte User kann beliebige Mediendateien löschen, solange er die ID kennt.

**Fix:**
```php
public function delete(Media $file) {
    // Prüfen ob der User Zugriff auf das zugehörige Model hat
    $model = $file->model;
    if ($model && !auth()->user()->can('delete', $model)) {
        abort(403);
    }
    $file->delete();
    return response()->json(['message' => 'Gelöscht']);
}
```

---

### [MITTEL-5] Log-Level in Produktion auf `debug` gesetzt

**Datei:** `config/logging.php` Zeile 27

**Problem:**  
```php
'level' => env('LOG_LEVEL', 'debug'), // Debug als Default!
```

Der `AuthController` loggt bei Login u.a. `password_hash_length` und E-Mail-Adressen. Der `NachrichtenController` loggt nur im Debug-Modus (`if (config('app.debug'))`), aber der Log-Level ist trotzdem `debug` per Default. Sensible Informationen können in Log-Dateien und der `database`-Log-Tabelle landen.

**Fix:**
```env
# .env Produktion
LOG_LEVEL=warning
```

```php
// app/Http/Controllers/API/AuthController.php
// 'password_hash_length' aus dem Log entfernen:
Log::debug('User lookup result', [
    'email' => $email,
    'user_found' => $user !== null,
    // 'password_hash_length' => ... // ENTFERNEN
]);
```

---

### [MITTEL-6] Keycloak-Konfiguration wird im Login-Log exponiert

**Datei:** `app/Http/Controllers/Auth/LoginController.php` Zeilen 133–137

**Problem:**  
```php
Log::info('Redirecting to Keycloak', [
    'client_id' => env('KEYCLOAK_CLIENT_ID'),   // Client-ID geloggt
    'base_url' => env('KEYCLOAK_BASE_URL'),      // URL geloggt
    'realm' => env('KEYCLOAK_REALM'),           // Realm geloggt
]);
```

Keycloak-Konfiguration wird in Logs geschrieben. Wer die Logs lesen kann, kennt die vollständige Keycloak-Konfiguration. Außerdem: `env()` direkt im Controller statt `config()` zu verwenden, umgeht den Config-Cache.

**Fix:**
```php
// Log ohne sensitive Details
Log::info('Redirecting to Keycloak');
// Konfiguration über config() ansprechen statt env()
config('services.keycloak.client_id') // statt env('KEYCLOAK_CLIENT_ID')
```

---

### [MITTEL-7] CVE-2026-30838 – league/commonmark XSS-Bypass

**Datei:** `composer.json`  
**CVE:** CVE-2026-30838 | **CVSS:** Medium  
**Betroffen:** `league/commonmark <= 2.8.0`

**Problem:**  
Die `DisallowedRawHtml`-Extension kann umgangen werden, indem Whitespace in HTML-Tag-Namen eingefügt wird (z.B. `<script\n>`). Falls die Anwendung Markdown-Rendering mit dieser Extension nutzt, ist ein XSS-Angriff möglich.

**Fix:**
```bash
composer update league/commonmark
# Ziel: >= 2.8.1
```

---

### [MITTEL-8] Passwort-Validierung ohne Mindestanforderungen (Benutzereinstellungen)

**Datei:** `app/Http/Requests/editUserRequest.php` Zeilen 73–79

**Problem:**  
```php
'password' => [
    'nullable', 'sometimes', 'string',
    'min:8', // Nur Mindestlänge – keine Komplexitätsanforderung!
],
```

Kein `confirmed`-Rule (Passwortwiederholung), keine Komplexitätsanforderung. Der Controller prüft zwar `if ($request->password == $request->password_confirmation)`, aber das ist im Controller und nicht in der Validierungsregel.

**Fix:**
```php
// app/Http/Requests/editUserRequest.php
use Illuminate\Validation\Rules\Password;

'password' => [
    'nullable', 'sometimes',
    'confirmed', // password_confirmation-Feld prüfen
    Password::min(8)->mixedCase()->numbers(),
],
```

---

## 🔵 NIEDRIG / INFO – Langfristig adressieren

---

### [INFO-1] Abandoned Packages mit Sicherheitsrisiken

**Datei:** `composer.json`

| Package | Status | Empfohlener Ersatz |
|---------|--------|--------------------|
| `fgrosse/phpasn1` | Abandoned | Kein direkter Ersatz angegeben |
| `spatie/data-transfer-object` | Abandoned | `spatie/laravel-data` |
| `web-token/jwt-*` (5 Packages) | Abandoned | `web-token/jwt-library` |

Abandoned Packages erhalten keine Sicherheitsupdates mehr.

**Empfehlung:**
```bash
composer audit  # regelmäßig ausführen
composer update  # Dependencies aktuell halten
```

---

### [INFO-2] AWS SDK – CVE-2025-14761 (S3 Encryption Key Commitment)

**Datei:** `composer.json`  
**CVE:** CVE-2025-14761 | **Severity:** Medium

Falls S3-Verschlüsselung (`SseC` oder `CSE`) genutzt wird, ist ein Key-Commitment-Angriff möglich.

**Fix:**
```bash
composer update aws/aws-sdk-php
# Ziel: >= 3.368.0
```

---

### [INFO-3] Audit-Log schließt sensitive Felder nicht aus

**Datei:** `config/audit.php` Zeile 88

**Problem:**  
```php
'exclude' => [], // Alle Felder werden auditiert!
```

Das `password`-Feld des `User`-Models sollte aus den Audit-Logs excluded werden, auch wenn es gehasht ist (vermeidet unnötige Datenspeicherung von Hashes).

**Fix:**
```php
// config/audit.php
'exclude' => ['password', 'remember_token'],
```

Alternativ im `User`-Model:
```php
protected array $auditExclude = ['password', 'remember_token'];
```

---

### [INFO-4] `loginAsUser`-Funktion ohne IP/Zeit-Log

**Datei:** `app/Http/Controllers/UserController.php` Zeilen 315–325

**Problem:**  
Wenn ein Admin sich als anderer User einloggt, wird das nicht geloggt. Bei Datenschutzvorfällen ist nicht nachvollziehbar, welcher Admin wann auf welches Konto zugegriffen hat.

**Fix:**
```php
public function loginAsUser(Request $request, $id)
{
    // ... Berechtigungsprüfung ...
    Log::warning('Admin login as user', [
        'admin_id' => $request->user()->id,
        'admin_email' => $request->user()->email,
        'target_user_id' => $id,
        'ip' => $request->ip(),
        'timestamp' => now()->toIso8601String(),
    ]);
    // ... Login-Logik ...
}
```

---

### [INFO-5] DSGVO – Datensparsamkeit bei `me`-Endpoint

**Datei:** `app/Http/Controllers/API/AuthController.php` Zeile 134

**Problem:**  
```php
public function me(Request $request) {
    return response()->json($request->user()); // Gibt das komplette User-Objekt zurück!
}
```

Das vollständige User-Objekt enthält möglicherweise interne Felder. Obwohl `password` und `remember_token` in `$hidden` sind, könnten andere interne Felder (z.B. `changePassword`, `track_login`, `lastEmail`) für die Mobile-App nicht notwendig sein.

**Fix:**
```php
public function me(Request $request) {
    $user = $request->user();
    return response()->json([
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'uuid' => $user->uuid,
        // Nur die tatsächlich benötigten Felder
    ]);
}
```

---

## Checkliste: Sofortmaßnahmen für Produktionsumgebung

Folgende Punkte können **ohne Code-Änderungen** sofort in der `.env` gesetzt werden:

```env
# Sicherheitsrelevante ENV-Variablen für Produktion
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=warning

# Session-Sicherheit (in config/session.php nachsehen und setzen)
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

# Sanctum
# Keine direkte ENV – in config/sanctum.php: 'expiration' => 60 * 24 * 30

# Passwortloser Login: Links nur einmal verwendbar
LPL_USE_ONCE=true

# Import-Passwörter (MÜSSEN gesetzt werden – nicht das Datum als Fallback!)
PW_IMPORT_ELTERN=<random-strong-password>
PW_IMPORT_AUFNAHME=<random-strong-password>
PW_IMPORT_MITARBEITER=<random-strong-password>
PW_IMPORT_VEREIN=<random-strong-password>

# Sentry: Keine SQL-Parameter an externe Dienste
# In config/sentry.php: 'sql_bindings' => false
```

---

## Priorisierte Reihenfolge der Umsetzung

| # | ID | Titel | Aufwand | Auswirkung |
|---|-----|-------|---------|------------|
| 1 | KRIT-1 | Unauthentifizierter Datei-Download | 30 Min | Datenleck aller Uploads |
| 2 | KRIT-5 | Symfony CVE-2025-64500 | 5 Min | Auth-Bypass möglich |
| 3 | ENV | Sofortmaßnahmen in .env | 10 Min | Mehrere Risiken reduziert |
| 4 | KRIT-2 | API-Key-Vergleich + Rate-Limiting | 2 Std | Brute-Force-Schutz |
| 5 | KRIT-3 | Stundenplan-Import absichern | 1 Std | Manipulation + DoS |
| 6 | KRIT-4 | SchoolYear außerhalb Auth-Gruppe | 30 Min | Unauth. Massenlöschung |
| 7 | HOCH-1 | Magic-Link once-use + Rate-Limit | 30 Min | Session-Hijacking |
| 8 | HOCH-2 | Sanctum Token-Expiration | 15 Min | Dauerhafter Zugriff |
| 9 | HOCH-3 | E-Mail-Passwort verschlüsseln | 3 Std | Credential-Leak |
| 10 | HOCH-4 | XSS in Blade-Templates | 4 Std | Stored XSS |
| 11 | HOCH-5 | Sentry SQL-Bindings deaktivieren | 5 Min | DSGVO-Verletzung |
| 12 | HOCH-6 | Import-Passwörter sichern | 15 Min | Unbefugter Import |
| 13 | HOCH-7 | loginAsUser GET→POST + Middleware | 1 Std | CSRF-Angriff |
| 14 | MITTEL-1 | docs/ sperren | 15 Min | Informationsleakage |
| 15 | MITTEL-4 | File-Delete Ownership-Check | 1 Std | Unbef. Dateilöschung |
| 16 | MITTEL-7 | league/commonmark Update | 5 Min | XSS via Markdown |
| 17 | MITTEL-2–8 | Weitere mittlere Befunde | je 1–4 Std | Divese Risiken |
| 18 | INFO-1–5 | Niedrig-Befunde | nach Kapazität | Qualitätsverbesserung |

