# TODO-03: `KelvinClient` (HTTP-Service)

> Konzept: §3 (Kelvin-Endpunkte), §7.1 (N+1), §7.5 (Token-Verschlüsselung), §7.6 (Robustheit)

## Ziel

Einen abgekapselten HTTP-Client `App\Services\Ucs\KelvinClient`
implementieren, der **die einzige** Stelle in der Codebase ist, die
direkt mit der Kelvin-REST-API spricht. Alle anderen Schichten
(SyncService, JIT-Login, Connectivity-Test, künftige Webhooks) nutzen
ausschließlich diesen Client.

## Scope / Anforderungen

- Bearer-Token-Auth gegen `/auth/`, Token gecached und **explizit**
  via `Crypt::encryptString()` verschlüsselt.
- TTL-basiertes Re-Auth (Default `UcsSetting::kelvin_token_ttl - 60 s`).
- Pagination respektieren (Kelvin liefert i. d. R. `limit`/`offset`).
- Retry mit Exponential Backoff für `5xx`/`429` (`Http::retry(3, 500)`).
- TLS-Pflicht (`verify => true`).
- Hard-Timeout aus `UcsSetting::kelvin_timeout`, für JIT-Login
  überschreibbar.
- Logging über Channel `ucs` mit Korrelations-ID pro Aufruf.
- **Keine** Geschäftslogik – nur Transport, Deserialisierung und
  ggf. typisierte DTOs.

## Abhängigkeiten

- 01 (Migrationen) – nicht zwingend.
- 02 (UcsSetting, Logging-Channel, Crypt) – **zwingend**.

## Aufgaben

- [ ] **Klasse `App\Services\Ucs\KelvinClient`** anlegen.
  - Konstruktor: `__construct(private UcsSetting $settings)`.
  - Private Helper `client(): PendingRequest` – baut den
    `Http::baseUrl()`-Request mit Bearer, Timeout, Retry, `Accept: application/json`.
- [ ] **Token-Handling:**
  - `private function token(): string`
  - Cache-Key: `ucs.kelvin.token` (Wert verschlüsselt).
  - Bei Cache-Miss: `POST /auth/` mit Form-Body
    `username`/`password` (aus `UcsSetting`), Bearer aus Response
    extrahieren, mit TTL `kelvin_token_ttl - 60` cachen
    (`Crypt::encryptString()`).
  - Bei `401`/`403` einmaliger Force-Refresh, sonst Exception.
- [ ] **Public Methoden:**
  - `ping(): Collection` – `GET /schools/?limit=1`, gibt die
    Schul-Collection zurück (für Status-Karte und Test-Button).
  - `listSchools(): Collection` – `GET /schools/`.
  - `listParents(string $school): Generator` – iteriert paginiert über
    `GET /users/?role=legal_guardian&school={school}`,
    `yield`s pro Eltern-Datensatz (Memory-schonend).
  - `listStudents(string $school): Generator` – analog, `role=student`.
  - `listClasses(string $school): Collection` – `GET /classes/?school={school}`.
  - `findUser(string $username): ?array` – `GET /users/{username}`,
    `null` bei `404`, für JIT-Login.
- [ ] **Pagination-Helper:**
  - Erkennt entweder Header `Next-Page`/`Link` oder Query-Pattern
    `limit`/`offset` aus `UcsSetting::kelvin_page_size`.
  - Schutz vor Endlos-Loops: Hard-Cap 200 Seiten.
- [ ] **DTOs (optional, aber empfohlen):**
  - `App\Services\Ucs\Dto\KelvinUserDto` und
    `App\Services\Ucs\Dto\KelvinStudentDto` als `readonly`-Klassen,
    statischer Factory `fromArray()`.
  - Wenn weggelassen: Array-Returns sauber dokumentieren (PHPDoc).
- [ ] **Fehlerklassen:**
  - `App\Services\Ucs\Exceptions\KelvinAuthException`
  - `App\Services\Ucs\Exceptions\KelvinUnavailableException`
  - `App\Services\Ucs\Exceptions\KelvinRateLimitException`
- [ ] **Korrelations-ID-Logging:**
  - Jeder Aufruf erhält eine UUID, die als `X-Correlation-Id`-Header
    mitgeschickt und in jedem `Log::channel('ucs')`-Eintrag mitgegeben
    wird (für Cross-Linking mit Sentry-Breadcrumbs).
- [ ] **`Http::fake()`-Fixtures** im selben PR mitliefern (kommt aus
  `tests/Fixtures/kelvin/`, siehe Paket 09 – Skelett genügt: zwei
  JSON-Files für `legal_guardian` und `student`).

## Gelingenskriterien

1. Unit-Test mit `Http::fake()` für `/auth/` zeigt: zweite Methode auf
   demselben Client ruft **kein** zweites `/auth/` mehr auf (Token-Cache).
2. Unit-Test: nach TTL-Ablauf wird ein neues `/auth/` ausgelöst.
3. Unit-Test: bei `401` ohne Cache-Treffer → genau ein Re-Auth, dann
   ein zweiter Original-Call; bei zweitem `401` →
   `KelvinAuthException`.
4. Unit-Test: 500er-Response → drei Retries (`Http::retry(3, 500)`),
   danach `KelvinUnavailableException`.
5. Cache-Eintrag `ucs.kelvin.token` enthält **nicht** den Klartext-
   Token (manueller `Cache::get()`-Read im Test prüft `decryptString()`
   nötig).
6. `listParents('GS-XY')` über `Generator` traversiert 3 fake Seiten
   à 200 Records korrekt (gesamt 600 Yields), ohne den gesamten
   Datensatz in Memory zu halten.
7. `ping()` gegen einen Fake mit `404` wirft `KelvinUnavailableException`
   mit aussagekräftiger Message.
8. Alle Log-Einträge im Channel `ucs` enthalten eine Korrelations-ID.

## Out of Scope

- Sync-Logik (Upsert, Pivots, Cleanup) → Paket 04.
- Job-Wrapping → Paket 05.
- Connectivity-Button-UI → Paket 07.

## Aufwand

M – ca. 1–2 Personentage.

