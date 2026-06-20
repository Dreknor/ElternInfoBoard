<?php

namespace App\Services\Ucs;

use App\Services\Ucs\Dto\KelvinStudentDto;
use App\Services\Ucs\Dto\KelvinUserDto;
use App\Services\Ucs\Exceptions\KelvinAuthException;
use App\Services\Ucs\Exceptions\KelvinRateLimitException;
use App\Services\Ucs\Exceptions\KelvinUnavailableException;
use App\Settings\UcsSetting;
use Generator;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * HTTP-Client für die UCS@school Kelvin REST API.
 *
 * Dies ist die **einzige** Stelle in der Codebase, die direkt mit der
 * Kelvin REST API kommuniziert. Alle anderen Schichten (UcsSyncService,
 * JIT-Login, Connectivity-Test) nutzen ausschließlich diesen Client.
 *
 * – Bearer-Token-Auth gegen /ucsschool/kelvin/token (außerhalb /v1/), verschlüsselt im Cache (§7.5)
 * – TTL-basiertes Re-Auth mit Crypt::encryptString()
 * – /users/-Endpunkt liefert alle Einträge auf einmal (keine Pagination); Generator für lazy Processing
 * – /classes/-Endpunkt unterstützt Pagination mit limit/offset (Hard-Cap: MAX_PAGES Seiten)
 * – Retry mit Exponential Backoff für 5xx/429 (Http::retry(3, 500))
 * – TLS-Pflicht (verify=true), kein verify=false in Produktion
 * – Hard-Timeout aus UcsSetting::kelvin_timeout
 * – Logging über Channel 'ucs' mit Korrelations-ID pro Aufruf-Chain
 * – Keine Geschäftslogik – nur Transport, Deserialisierung, DTOs
 *
 * @see docs/kelvin-api-endpunkte.md
 */
class KelvinClient
{
    /** Cache-Key für den Bearer-Token (Wert liegt AES-verschlüsselt vor). */
    private const CACHE_KEY = 'ucs.kelvin.token';

    /** Hard-Cap für Pagination-Schleifen (schützt vor Endlos-Loops). */
    private const MAX_PAGES = 200;

    /**
     * Laufzeit-Cache für aufgelöste Schulnamen (Input → kanonischer Name aus der API).
     * Verhindert wiederholte GET /schools/-Aufrufe pro Sync-Lauf.
     *
     * @var array<string, string>
     */
    private array $resolvedSchoolNames = [];

    /**
     * Korrelations-ID für diese Client-Instanz.
     * Wird mit jedem Log-Eintrag und per X-Correlation-Id-Header mitgesendet.
     */
    private string $correlationId;

    public function __construct(private readonly UcsSetting $settings)
    {
        $this->correlationId = (string) Str::uuid();
    }

    // =========================================================================
    // Public API
    // =========================================================================

    /**
     * Connectivity-Check: GET /schools/{school}
     *
     * Ruft die konfigurierte Schule (UcsSetting::school) direkt per
     * Name ab.
     *
     * Dient als Smoke-Test (Status-Karte im UI, ucs:ping-Command).
     * Wirft KelvinUnavailableException bei jedem HTTP-Fehler inkl. 404.
     *
     * @return array<string, mixed>
     *
     * @throws KelvinAuthException
     * @throws KelvinUnavailableException
     *
     * @see docs/kelvin-api-endpunkte.md#2-schulen-auflisten
     */
    public function ping(): array
    {
        $school   = $this->settings->school ?? '';
        $response = $this->executeGet('schools/'.rawurlencode($school));

        return $response->json() ?? [];
    }

    /**
     * Alle Schulen auflisten: GET /schools/
     *
     * @return Collection<int, array<string, mixed>>
     *
     * @throws KelvinAuthException
     * @throws KelvinUnavailableException
     *
     * @see docs/kelvin-api-endpunkte.md#2-schulen-auflisten
     */
    public function listSchools(): Collection
    {
        $response = $this->executeGet('schools/', [
            'limit' => $this->settings->kelvin_page_size,
        ]);

        return collect($response->json() ?? []);
    }

    /**
     * Alle Erziehungsberechtigten der Schule paginiert abrufen (Generator).
     *
     * Jede Seite wird sofort verarbeitet (Memory-schonend).
     * Yields ein KelvinUserDto pro Elternteil.
     *
     * @return Generator<int, KelvinUserDto>
     *
     * @throws KelvinAuthException
     * @throws KelvinUnavailableException
     *
     * @see docs/kelvin-api-endpunkte.md#3-eltern-auflisten-get-users--legal_guardian
     */
    public function listParents(string $school): Generator
    {
        return $this->paginateUsers('legal_guardian', $school);
    }

    /**
     * Alle Schüler der Schule paginiert abrufen (Generator).
     *
     * Yields ein KelvinStudentDto pro Schüler.
     *
     * @return Generator<int, KelvinStudentDto>
     *
     * @throws KelvinAuthException
     * @throws KelvinUnavailableException
     *
     * @see docs/kelvin-api-endpunkte.md#4-schüler-auflisten-get-users--student
     */
    public function listStudents(string $school): Generator
    {
        return $this->paginateUsers('student', $school);
    }

    /**
     * Alle Klassen der Schule abrufen (optional, Phase 2).
     *
     * Derzeit nicht im Haupt-Sync-Pfad; wird nur bei explizitem Aufruf genutzt.
     *
     * @return Collection<int, array<string, mixed>>
     *
     * @throws KelvinAuthException
     * @throws KelvinUnavailableException
     *
     * @see docs/kelvin-api-endpunkte.md#6-klassen-auflisten-optional--phase-2
     */
    public function listClasses(string $school): Collection
    {
        $results = collect();
        $offset  = 0;
        $limit   = $this->settings->kelvin_page_size;

        for ($page = 0; $page < self::MAX_PAGES; $page++) {
            $response = $this->executeGet('classes/', [
                'school' => $school,
                'limit'  => $limit,
                'offset' => $offset,
            ]);

            $batch = $response->json() ?? [];

            if (empty($batch)) {
                break;
            }

            $results = $results->merge($batch);
            $offset += $limit;

            if (count($batch) < $limit) {
                break;
            }
        }

        if ($page >= self::MAX_PAGES) {
            $this->log('warning', 'listClasses: Hard-Cap erreicht', [
                'school' => $school,
                'pages'  => $page,
            ]);
        }

        return $results;
    }

    /**
     * Einzelnen UCS-Account per Username abrufen.
     *
     * Gibt null zurück, wenn der User in UCS nicht gefunden wird (HTTP 404).
     * Der Aufrufer ist verantwortlich, einen Negativ-Cache-Eintrag zu setzen.
     *
     * Wird für JIT-Login (OIDC-Callback) und ucs:link-child-CLI genutzt.
     *
     * @param  int|null  $timeout  Überschreibt UcsSetting::kelvin_timeout (z. B. on_login_timeout für JIT).
     *
     * @return array<string, mixed>|null
     *
     * @throws KelvinAuthException
     * @throws KelvinUnavailableException
     *
     * @see docs/kelvin-api-endpunkte.md#5-einzelner-benutzer-get-users-username
     */
    public function findUser(string $username, ?int $timeout = null): ?array
    {
        $this->log('info', 'findUser', ['username' => $username]);

        $token    = $this->token();
        $response = $this->buildClient($token, $timeout)->get('users/'.rawurlencode($username));

        // 401: einmaliger Force-Refresh
        if ($response->status() === 401) {
            $this->log('warning', 'findUser: 401 – Token-Force-Refresh', ['username' => $username]);
            Cache::forget(self::CACHE_KEY);
            $token    = $this->token(forceRefresh: true);
            $response = $this->buildClient($token, $timeout)->get('users/'.rawurlencode($username));

            if ($response->status() === 401 || $response->status() === 403) {
                throw new KelvinAuthException(
                    'Kelvin findUser: Auch nach Token-Refresh HTTP '.$response->status()
                    .' für "'.$username.'".'
                );
            }
        }

        // 404 = legitimer Nicht-Fund → null (Negativ-Cache beim Aufrufer!)
        if ($response->status() === 404) {
            $this->log('info', 'findUser: 404 – User nicht gefunden', ['username' => $username]);

            return null;
        }

        $this->assertTypedException($response, 'findUser');

        return $response->json();
    }

    // =========================================================================
    // Token-Handling
    // =========================================================================

    /**
     * Gibt einen gültigen Bearer-Token zurück.
     *
     * Der Token wird AES-verschlüsselt im Cache gehalten (§7.5 Konzept).
     * Bei Cache-Miss oder forceRefresh=true:
     *   POST /ucsschool/kelvin/token  (außerhalb des /v1/-Namespace!)
     *   Content-Type: application/x-www-form-urlencoded
     *
     * ⚠️  Der Token-Endpunkt liegt NICHT unter /v1/. Die baseUrl()
     *     (= kelvin_base_url, z. B. „…/kelvin/v1") darf hier NICHT
     *     verwendet werden. Stattdessen liefert tokenUrl() die korrekte
     *     absolute URL, indem es den Versions-Pfad (/v1) abschneidet.
     *
     * @throws KelvinAuthException
     * @throws KelvinUnavailableException
     *
     * @see docs/kelvin-api-endpunkte.md#1-token-authentifizierung
     */
    private function token(bool $forceRefresh = false): string
    {
        if (! $forceRefresh && Cache::has(self::CACHE_KEY)) {
            return Crypt::decryptString(Cache::get(self::CACHE_KEY));
        }

        $url = $this->tokenUrl();
        $this->log('info', "Token-Refresh: POST {$url}");

        try {
            $response = Http::withOptions(['verify' => true])
                ->timeout($this->settings->kelvin_timeout)
                ->withHeaders(['X-Correlation-Id' => $this->correlationId])
                ->asForm()
                ->post($url, [
                    'username' => $this->settings->kelvin_username,
                    'password' => $this->settings->kelvin_password,
                ]);
        } catch (ConnectionException $e) {
            throw new KelvinUnavailableException(
                'Kelvin /token nicht erreichbar: '.$e->getMessage()
                .' – Proxy-Block? Freigabeliste in docs/kelvin-api-endpunkte.md prüfen.',
                0,
                $e
            );
        }

        if ($response->status() === 401 || $response->status() === 403) {
            throw new KelvinAuthException(
                'Kelvin-Authentifizierung fehlgeschlagen (HTTP '.$response->status().'): '
                .($response->json('detail') ?? $response->body())
            );
        }

        if (! $response->successful()) {
            throw new KelvinUnavailableException(
                'Kelvin /token antwortete mit HTTP '.$response->status()
                .' – Proxy-Block? Freigabeliste in docs/kelvin-api-endpunkte.md prüfen.'
            );
        }

        $token = $response->json('access_token');

        if (empty($token)) {
            throw new KelvinAuthException('Kelvin /token lieferte keinen access_token.');
        }

        $ttl = max(1, $this->settings->kelvin_token_ttl - 60);
        Cache::put(self::CACHE_KEY, Crypt::encryptString($token), $ttl);

        $this->log('info', 'Neuer Token gecacht', ['ttl_seconds' => $ttl]);

        return $token;
    }

    // =========================================================================
    // HTTP-Client Builder
    // =========================================================================

    /**
     * Baut einen PendingRequest mit Bearer-Token, TLS, Timeout, Retry und
     * Korrelations-ID-Header.
     *
     * Retry-Verhalten:
     * – 3 Versuche insgesamt (Http::retry(3, 500))
     * – Retry bei 5xx-Responses und HTTP 429 (Rate Limit)
     * – Nach Erschöpfung aller Versuche: Rückgabe der letzten Response
     *   (throw: false), Fehlerbehandlung in assertTypedException()
     * – Connection-Exceptions werden ebenfalls per Retry behandelt
     */
    private function buildClient(string $token, ?int $timeout = null): PendingRequest
    {
        return Http::baseUrl($this->baseUrl())
            ->withToken($token)
            ->withOptions(['verify' => true])
            ->withHeaders([
                'Accept'           => 'application/json',
                'X-Correlation-Id' => $this->correlationId,
            ])
            ->timeout($timeout ?? $this->settings->kelvin_timeout)
            ->retry(3, 500, function (\Throwable $e): bool {
                if ($e instanceof RequestException) {
                    $status = $e->response->status();
                    // 5xx: retry; 429: retry (RateLimitException nach allen Versuchen)
                    return $e->response->serverError() || $status === 429;
                }
                // Netzwerkfehler (Timeout, DNS, Proxy-Block): ebenfalls retry
                return $e instanceof ConnectionException;
            }, throw: false);
    }

    // =========================================================================
    // Pagination
    // =========================================================================

    /**
     * Generischer Generator für GET /users/ mit einer bestimmten Rolle.
     *
     * Der Endpunkt liefert alle Einträge der Rolle auf einmal als großes JSON-Array.
     * limit/offset-Parameter werden von der API ignoriert und daher NICHT gesendet.
     *
     *
     * @return Generator<int, KelvinUserDto|KelvinStudentDto>
     *
     * @throws KelvinAuthException
     * @throws KelvinUnavailableException
     */
    private function paginateUsers(string $role, string $school): Generator
    {
        $this->log('info', "listUsers: start [{$role}]", ['school' => $school]);

        // Die Kelvin REST API erwartet den Parameter „roles" (Plural) mit dem
        // vollständigen Rollen-String im Format „{role}:school:{school}".
        // Der Schulname muss exakt der kanonischen Schreibweise in UCS entsprechen
        // (case-sensitiv). Da UcsSetting::school in beliebiger Schreibweise
        // gespeichert sein kann, wird der Name hier über GET /schools/ aufgelöst.
        $canonicalSchool = $this->resolveCanonicalSchoolName($school);

        $response = $this->executeGet('users/', [
            'roles' => $role.':school:'.$canonicalSchool,
        ]);

        $users        = $response->json() ?? [];
        $totalYielded = 0;

        foreach ($users as $item) {
            yield $role === 'student'
                ? KelvinStudentDto::fromArray($item)
                : KelvinUserDto::fromArray($item);
            $totalYielded++;
        }

        $this->log('info', "listUsers: done [{$role}]", [
            'school' => $school,
            'total'  => $totalYielded,
        ]);
    }

    // =========================================================================
    // Execute-Wrapper mit 401-Force-Refresh
    // =========================================================================

    /**
     * Führt einen GET-Request aus, inkl. einmaligem Token-Force-Refresh bei 401.
     *
     * Retry-Logik für 5xx/429 liegt in buildClient() (Http::retry(3, 500)).
     *
     * @param  array<string, mixed>  $query
     *
     * @throws KelvinAuthException
     * @throws KelvinRateLimitException
     * @throws KelvinUnavailableException
     */
    private function executeGet(string $endpoint, array $query = [], ?int $timeout = null): Response
    {
        $token    = $this->token();
        $response = $this->buildClient($token, $timeout)->get($endpoint, $query);

        // 401: einmaliger Force-Refresh
        if ($response->status() === 401) {
            $this->log('warning', "executeGet: 401 – Token-Force-Refresh [{$endpoint}]");
            Cache::forget(self::CACHE_KEY);
            $token    = $this->token(forceRefresh: true);
            $response = $this->buildClient($token, $timeout)->get($endpoint, $query);

            if ($response->status() === 401 || $response->status() === 403) {
                throw new KelvinAuthException(
                    "Kelvin {$endpoint}: Auch nach Token-Refresh HTTP ".$response->status().'.'
                );
            }
        }

        //404: Log-Ausgabe, aber nicht als Exception
        if ($response->status() === 404) {
            Log::debug("Kelvin {$endpoint}: 404 – Resource nicht gefunden", [
                'endpoint' => $endpoint,
                'query'    => $query,
            ]);
        }

        $this->assertTypedException($response, $endpoint);

        return $response;
    }

    // =========================================================================
    // Fehlerbehandlung
    // =========================================================================

    /**
     * Wirft eine typisierte Exception für alle nicht-erfolgreichen Responses.
     *
     * @throws KelvinAuthException
     * @throws KelvinRateLimitException
     * @throws KelvinUnavailableException
     */
    private function assertTypedException(Response $response, string $context): void
    {
        if ($response->successful()) {
            return;
        }

        $status = $response->status();

        $this->log('error', "HTTP {$status} in [{$context}]", [
            'body' => mb_substr($response->body(), 0, 500),
        ]);

        match (true) {
            $status === 401 || $status === 403 => throw new KelvinAuthException(
                "Kelvin {$context}: HTTP {$status} – ".($response->json('detail') ?? $response->body())
            ),
            $status === 429 => throw new KelvinRateLimitException(
                "Kelvin {$context}: Rate-Limit (HTTP 429) nach allen Retry-Versuchen."
            ),
            default => throw new KelvinUnavailableException(
                "Kelvin {$context}: HTTP {$status} – ".mb_substr($response->body(), 0, 200)
                .' – Proxy-Block? Freigabeliste in docs/kelvin-api-endpunkte.md prüfen.'
            ),
        };
    }

    // =========================================================================
    // Hilfsmethoden
    // =========================================================================

    /**
     * Löst den kanonischen Schulnamen (exakte Schreibweise laut Kelvin-API) auf.
     *
     * Das UcsSetting::school kann in beliebiger Groß-/Kleinschreibung gespeichert
     * sein (z. B. „evsr"), die Kelvin-API ist jedoch case-sensitiv und erwartet
     * beim roles-Filter den exakten Namen (z. B. „EVSR").
     *
     * Vorgehen:
     *  1. GET /schools/{school} – der Endpunkt ist im UCS-Kelvin-Stack case-insensitiv
     *     (LDAP-DNs sind case-insensitiv), liefert aber den kanonischen Namen zurück.
     *  2. Das „name"-Feld der Antwort wird als kanonischer Name verwendet.
     *  3. Ergebnis wird pro Client-Instanz gecacht (kein doppelter HTTP-Call).
     *
     * @throws KelvinAuthException
     * @throws KelvinUnavailableException
     */
    private function resolveCanonicalSchoolName(string $school): string
    {
        if (isset($this->resolvedSchoolNames[$school])) {
            return $this->resolvedSchoolNames[$school];
        }

        $response  = $this->executeGet('schools/'.rawurlencode($school));
        $canonical = $response->json('name') ?? $school;

        $this->log('info', 'resolveCanonicalSchoolName', [
            'input'     => $school,
            'canonical' => $canonical,
        ]);

        return $this->resolvedSchoolNames[$school] = $canonical;
    }

    /**
     * Basis-URL für alle v1-Ressourcen-Endpunkte (/users/, /schools/, /classes/, …).
     *
     * Entspricht dem konfigurierten kelvin_base_url, z. B.
     * „https://ucs-host/ucsschool/kelvin/v1/".
     */
    private function baseUrl(): string
    {
        return rtrim($this->settings->kelvin_base_url ?? '', '/').'/';
    }

    /**
     * Absolute URL des Token-Endpunkts.
     *
     * Der Token-Endpunkt liegt AUSSERHALB des /v1/-Namespace:
     *   POST https://<fqdn>/ucsschool/kelvin/token
     *
     * Ableitung: kelvin_base_url ohne den Versions-Pfad (/v1, /v2, …)
     * plus „/token".
     *
     * Beispiel:
     *   kelvin_base_url = „https://ucs-host/ucsschool/kelvin/v1"
     *   tokenUrl()      = „https://ucs-host/ucsschool/kelvin/token"
     */
    private function tokenUrl(): string
    {
        $base = rtrim($this->settings->kelvin_base_url ?? '', '/');
        // Versions-Suffix /v1, /v2, … entfernen (case-insensitive)
        $base = preg_replace('#/v\d+$#i', '', $base);

        return $base.'/token';
    }

    /**
     * Schreibt einen Log-Eintrag im Channel 'ucs' mit Korrelations-ID.
     *
     * @param  array<string, mixed>  $context
     */
    private function log(string $level, string $message, array $context = []): void
    {
        Log::channel('ucs')->{$level}($message, array_merge(
            ['correlation_id' => $this->correlationId],
            $context
        ));
    }
}

