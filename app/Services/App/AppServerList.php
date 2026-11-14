<?php

namespace App\Services\App;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Freigabeliste der ElternInfo-App.
 *
 * Der Herausgeber der App signiert eine Liste der Server, die die App nutzen dürfen
 * (Ed25519, offline). Dieser Server holt sie täglich von APP_SERVER_LIST_URL ab und reicht sie
 * unverändert über /v1/instance an die App weiter – so sprechen die Geräte der Eltern nur mit
 * dem eigenen Schulserver. Durchsetzen tut die App; hier wird die Liste nur zwischengespeichert
 * und für einen Hinweis an Admins ausgewertet.
 *
 * Format: {"payload": "<base64 JSON>", "signature": "<base64 Ed25519>"}
 * Payload: {"version":1,"issued_at":"YYYY-MM-DD","servers":[{"host"|"host_sha256", "kind",
 *           "valid_until" (YYYY-MM-DD oder null = unbefristet), "name"?, "listed"?}]}
 *
 * Gleiche Regeln wie in der App (src/lib/serverLicense.ts):
 * - Sperre frühestens ab ENFORCE_FROM (Schuljahresbeginn)
 * - Ohne valid_until unbefristet; mit Datum (gekündigt/befristet) danach GRACE_DAYS Kulanz
 * Die Liste wird nur bei Änderungen neu signiert; ihr Alter spielt keine Rolle.
 */
class AppServerList
{
    public const ENFORCE_FROM = '2027-08-01';

    public const GRACE_DAYS = 30;

    /** Admin-Hinweis, wenn der letzte erfolgreiche Abruf so lange zurückliegt (ohne Folgen für die App) */
    public const FETCH_WARN_DAYS = 14;

    /** Hinweis an Admins so viele Tage vor dem Ablauf */
    public const WARN_DAYS = 45;

    private const FILE = 'app-server-list.json';

    private const MAX_BYTES = 262144;

    /**
     * Liste vom Herausgeber holen. Ersetzt die gespeicherte nur durch eine gültig signierte,
     * mindestens gleich neue Fassung (kein Zurückspielen alter Listen).
     */
    public function fetch(): bool
    {
        $url = config('services.app.server_list_url');
        if (! $url) {
            return false;
        }

        try {
            $response = Http::timeout(15)->accept('application/json')->get($url);
        } catch (\Throwable $e) {
            Log::warning('App-Freigabeliste: Abruf fehlgeschlagen: '.$e->getMessage());

            return false;
        }

        if (! $response->successful() || strlen($response->body()) > self::MAX_BYTES) {
            Log::warning('App-Freigabeliste: ungültige Antwort (HTTP '.$response->status().').');

            return false;
        }

        $signed = $response->json();
        if (! self::isSignedShape($signed)) {
            Log::warning('App-Freigabeliste: unbekanntes Format.');

            return false;
        }

        $payload = $this->verify($signed);
        if ($this->publicKey() && ! $payload) {
            Log::warning('App-Freigabeliste: Signatur ungültig – Liste verworfen.');

            return false;
        }

        $current = $this->payload();
        if ($payload && $current && ($payload['issued_at'] ?? '') < ($current['issued_at'] ?? '')) {
            Log::warning('App-Freigabeliste: ältere Fassung erhalten – verworfen.');

            return false;
        }

        Storage::disk('local')->put(self::FILE, json_encode([
            'payload' => $signed['payload'],
            'signature' => $signed['signature'],
            'fetched_at' => now()->toIso8601String(),
        ]));
        \Illuminate\Support\Facades\Cache::forget('app_server_list_notice');

        return true;
    }

    /** Gespeicherte Liste im Originalformat (für /v1/instance) oder null. */
    public function current(): ?array
    {
        if (! Storage::disk('local')->exists(self::FILE)) {
            return null;
        }

        $signed = json_decode((string) Storage::disk('local')->get(self::FILE), true);

        return self::isSignedShape($signed) ? ['payload' => $signed['payload'], 'signature' => $signed['signature']] : null;
    }

    /** Geprüfter Inhalt der gespeicherten Liste – null ohne Liste, ohne Schlüssel oder bei falscher Signatur. */
    public function payload(): ?array
    {
        $signed = $this->current();

        return $signed ? $this->verify($signed) : null;
    }

    /**
     * Freigabe dieses Servers für den Admin-Hinweis.
     *
     * @return array{state: string, deadline: ?CarbonImmutable, valid_until: ?string, enforced: bool}
     *               state: ok | expiring | unlisted | expired | fetch_failing | missing | unconfigured
     */
    public function statusForThisServer(?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();
        $enforceFrom = CarbonImmutable::parse(self::ENFORCE_FROM)->startOfDay();
        $result = fn (string $state, ?CarbonImmutable $deadline = null, ?string $validUntil = null) => [
            'state' => $state,
            'deadline' => $deadline ? max($deadline, $enforceFrom) : null,
            'valid_until' => $validUntil,
            'enforced' => $now->greaterThanOrEqualTo($enforceFrom),
        ];

        if (! $this->publicKey() || ! config('services.app.server_list_url')) {
            return $result('unconfigured');
        }

        $payload = $this->payload();
        if (! $payload) {
            return $result('missing', $enforceFrom);
        }

        $entry = $this->findEntry($payload, self::hostLabel((string) config('app.url')));
        if (! $entry) {
            return $result('unlisted', $enforceFrom);
        }

        $validUntil = $entry['valid_until'] ? CarbonImmutable::parse($entry['valid_until'])->endOfDay() : null;
        if ($validUntil && $now->greaterThan($validUntil)) {
            return $result('expired', $validUntil->addDays(self::GRACE_DAYS), $entry['valid_until']);
        }

        // Abruf klappt seit Längerem nicht: Verlängerungen kämen nicht an (nur Hinweis)
        $fetchedAt = $this->fetchedAt();
        if ($fetchedAt && $now->greaterThan($fetchedAt->addDays(self::FETCH_WARN_DAYS))) {
            return $result('fetch_failing', $validUntil?->addDays(self::GRACE_DAYS), $entry['valid_until']);
        }

        if ($validUntil && $now->addDays(self::WARN_DAYS)->greaterThan($validUntil)) {
            return $result('expiring', $validUntil->addDays(self::GRACE_DAYS), $entry['valid_until']);
        }

        return $result('ok', null, $entry['valid_until']);
    }

    /**
     * Hinweis für Admins auf der Startseite oder null. Nur wenn die App hier tatsächlich genutzt
     * wird (App-Anmeldungen vorhanden) – Schulen ohne App sehen nichts.
     *
     * @return array{type: string, title: string, text: string}|null
     */
    public function adminNotice(): ?array
    {
        return \Illuminate\Support\Facades\Cache::remember('app_server_list_notice', 3600, function () {
            $status = $this->statusForThisServer();
            if ($status['state'] === 'ok' || ! $this->appInUse()) {
                return null;
            }

            $date = fn (?CarbonImmutable $d) => $d?->locale('de')->isoFormat('D. MMMM YYYY');
            $validUntil = $status['valid_until'] ? $date(CarbonImmutable::parse($status['valid_until'])) : null;
            $deadline = $date($status['deadline']);
            $title = 'ElternInfo-App';

            return match ($status['state']) {
                'expiring' => ['type' => 'info', 'title' => $title, 'text' => "Die Freischaltung dieses Servers für die ElternInfo-App endet am {$validUntil}. Danach funktioniert die App noch 30 Tage. Bei Fragen wenden Sie sich bitte an den Herausgeber der App."],
                'expired' => ['type' => 'warning', 'title' => $title, 'text' => "Die Freischaltung für die ElternInfo-App ist am {$validUntil} ausgelaufen. Die App funktioniert noch bis zum {$deadline}. Bitte wenden Sie sich an den Herausgeber der App."],
                'fetch_failing' => ['type' => 'warning', 'title' => $title, 'text' => "Die Freigabeliste der ElternInfo-App konnte seit über ".self::FETCH_WARN_DAYS." Tagen nicht abgerufen werden. Bitte prüfen Sie, ob der Cronjob läuft und dieser Server {$this->listHost()} erreichen kann. Sonst kommen Änderungen an der Freischaltung nicht an.".($validUntil ? " Sie gilt bisher bis zum {$validUntil}." : '')],
                'unlisted', 'missing' => ['type' => 'warning', 'title' => $title, 'text' => "Dieser Server ist nicht für die ElternInfo-App freigeschaltet. Ab dem {$deadline} können sich Eltern nicht mehr mit der App anmelden; die Weboberfläche bleibt nutzbar. Bitte wenden Sie sich an den Herausgeber der App."],
                default => ['type' => 'warning', 'title' => $title, 'text' => 'Die Freigabeliste der ElternInfo-App ist nicht eingerichtet (APP_SERVER_LIST_URL und APP_SERVER_LIST_PUBLIC_KEY in der .env). Ab dem '.$date(CarbonImmutable::parse(self::ENFORCE_FROM)).' funktioniert die App sonst nicht mehr.'],
            };
        });
    }

    /** Zeitpunkt des letzten erfolgreichen Abrufs */
    private function fetchedAt(): ?CarbonImmutable
    {
        if (! Storage::disk('local')->exists(self::FILE)) {
            return null;
        }
        $value = json_decode((string) Storage::disk('local')->get(self::FILE), true)['fetched_at'] ?? null;

        return is_string($value) ? CarbonImmutable::parse($value) : null;
    }

    private function appInUse(): bool
    {
        return \App\Model\PersonalAccessToken::query()->where('name', 'like', '%ElternInfo-App%')->exists();
    }

    private function listHost(): string
    {
        return parse_url((string) config('services.app.server_list_url'), PHP_URL_HOST) ?: 'den Herausgeber';
    }

    /**
     * Server-Kennung wie in der App: Host (+ Port) + Pfad, klein, ohne Schema und Schrägstrich am Ende.
     * `https://Eltern.Schule.de/eib/` → `eltern.schule.de/eib`
     */
    public static function hostLabel(string $url): string
    {
        $parts = parse_url(trim($url));
        $host = strtolower($parts['host'] ?? '');
        if (isset($parts['port'])) {
            $host .= ':'.$parts['port'];
        }
        $path = rtrim($parts['path'] ?? '', '/');

        return $host.$path;
    }

    private function findEntry(array $payload, string $label): ?array
    {
        $hash = hash('sha256', $label);
        foreach ($payload['servers'] ?? [] as $entry) {
            // valid_until: Datum oder null (unbefristet); fehlt der Schlüssel, ist der Eintrag ungültig
            if (! is_array($entry) || ! array_key_exists('valid_until', $entry)) {
                continue;
            }
            if (($entry['host'] ?? null) === $label || ($entry['host_sha256'] ?? null) === $hash) {
                return $entry;
            }
        }

        return null;
    }

    private function verify(array $signed): ?array
    {
        $key = $this->publicKey();
        $payload = base64_decode($signed['payload'], true);
        $signature = base64_decode($signed['signature'], true);
        if (! $key || $payload === false || $signature === false || strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES) {
            return null;
        }

        try {
            if (! sodium_crypto_sign_verify_detached($signature, $payload, $key)) {
                return null;
            }
        } catch (\SodiumException) {
            return null;
        }

        $data = json_decode($payload, true);

        return is_array($data) && isset($data['issued_at'], $data['servers']) && is_array($data['servers']) ? $data : null;
    }

    /** Öffentlicher Schlüssel (32 Byte, base64 oder base64url) oder null. */
    private function publicKey(): ?string
    {
        $value = (string) config('services.app.server_list_public_key');
        if ($value === '') {
            return null;
        }
        $raw = base64_decode(strtr($value, '-_', '+/'), true);

        return $raw !== false && strlen($raw) === SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES ? $raw : null;
    }

    private static function isSignedShape(mixed $value): bool
    {
        return is_array($value) && is_string($value['payload'] ?? null) && is_string($value['signature'] ?? null);
    }
}
