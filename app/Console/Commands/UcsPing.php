<?php

namespace App\Console\Commands;

use App\Services\Ucs\Exceptions\KelvinAuthException;
use App\Services\Ucs\Exceptions\KelvinUnavailableException;
use App\Services\Ucs\KelvinClient;
use App\Settings\UcsSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Connectivity-Check gegen die Kelvin REST API.
 *
 * Ruft KelvinClient::ping() auf und gibt die Anzahl der erreichbaren Schulen,
 * die ersten Schulnamen sowie die HTTP-Latenz aus.
 *
 * Exit-Codes:
 *   0 – Verbindung erfolgreich
 *   1 – Fehler / Verbindung nicht möglich
 *
 * Optionen:
 *   --debug   Gibt Konfiguration, DNS-Check und TCP-Check vor dem HTTP-Call aus.
 *
 * @see docs/ucs-kelvin-integration-konzept.md §3, §7.6
 */
class UcsPing extends Command
{
    protected $signature = 'ucs:ping
        {--debug : Ausführliche Debug-Ausgabe (Konfiguration, DNS, TCP, Token-Endpunkt)}
        {--flush-token : Bearer-Token aus dem Cache löschen vor dem Ping}';

    protected $description = 'Kelvin-API-Connectivity-Check: Schulen auflisten, Latenz messen.';

    /** Cache-Key aus KelvinClient (muss synchron gehalten werden). */
    private const TOKEN_CACHE_KEY = 'ucs.kelvin.token';

    public function handle(KelvinClient $client, UcsSetting $settings): int
    {
        $debug = $this->option('debug') || $this->getOutput()->isVerbose();

        $this->info('╔══════════════════════════════════════════════╗');
        $this->info('║          UCS Kelvin Ping – Debug             ║');
        $this->info('╚══════════════════════════════════════════════╝');

        // ------------------------------------------------------------------
        // 1. Konfiguration anzeigen
        // ------------------------------------------------------------------
        if ($debug) {
            $this->section('1. Konfiguration');

            $baseUrl   = rtrim($settings->kelvin_base_url ?? '(nicht gesetzt)', '/').'/';
            $tokenUrl  = preg_replace('#/v\d+/?$#i', '', rtrim($settings->kelvin_base_url ?? '', '/')).'/token';
            $username  = $settings->kelvin_username ?? '(nicht gesetzt)';
            $password  = empty($settings->kelvin_password) ? '(leer / nicht gesetzt)' : str_repeat('*', 8).' (gesetzt)';
            $timeout   = $settings->kelvin_timeout.' s';
            $tokenTtl  = $settings->kelvin_token_ttl.' s';
            $enabled   = $settings->enabled ? '<fg=green>JA</>' : '<fg=red>NEIN</>';
            $tokenInCache = Cache::has(self::TOKEN_CACHE_KEY) ? '<fg=green>vorhanden</>' : '<fg=yellow>nicht vorhanden (wird neu geholt)</>';

            $this->table(
                ['Einstellung', 'Wert'],
                [
                    ['UCS Integration aktiv', $enabled],
                    ['kelvin_base_url (v1)', $baseUrl],
                    ['Token-Endpunkt (abgeleitet)', $tokenUrl],
                    ['kelvin_username', $username],
                    ['kelvin_password', $password],
                    ['kelvin_timeout', $timeout],
                    ['kelvin_token_ttl', $tokenTtl],
                    ['Bearer-Token im Cache', $tokenInCache],
                ],
            );

            if (! $settings->enabled) {
                $this->warn('⚠  UCS-Integration ist in den Einstellungen DEAKTIVIERT.');
            }

            if (empty($settings->kelvin_base_url)) {
                $this->error('✗ kelvin_base_url ist leer. Ping wird trotzdem versucht.');
            }

            // ------------------------------------------------------------------
            // 2. Token ggf. aus Cache löschen
            // ------------------------------------------------------------------
            if ($this->option('flush-token')) {
                Cache::forget(self::TOKEN_CACHE_KEY);
                $this->line('  → Bearer-Token aus Cache gelöscht (--flush-token).');
            }

            // ------------------------------------------------------------------
            // 3. DNS-Auflösung
            // ------------------------------------------------------------------
            $this->section('2. DNS-Auflösung');

            $host = parse_url($settings->kelvin_base_url ?? '', PHP_URL_HOST);

            if (empty($host)) {
                $this->warn('  ⚠  Kein Hostname aus kelvin_base_url extrahierbar – DNS-Check übersprungen.');
            } else {
                $this->line("  Hostname : <fg=cyan>{$host}</>");
                $ip = @gethostbyname($host);

                if ($ip === $host) {
                    $this->error("  ✗ DNS-Auflösung fehlgeschlagen für \"{$host}\" – kein A-Record gefunden.");
                    $this->line('    → Mögliche Ursachen: falscher Hostname, kein DNS-Eintrag,');
                    $this->line('      Firewall blockiert DNS-Traffic vom Testserver.');
                } else {
                    $this->line("  ✓ DNS aufgelöst: <fg=green>{$host}</> → <fg=green>{$ip}</>");
                }

                // ------------------------------------------------------------------
                // 4. TCP-Verbindungscheck
                // ------------------------------------------------------------------
                $this->section('3. TCP-Verbindungscheck (Port 443)');

                $port    = parse_url($settings->kelvin_base_url ?? '', PHP_URL_PORT) ?? 443;
                $timeout = min($settings->kelvin_timeout, 5);

                $this->line("  Ziel    : <fg=cyan>{$host}:{$port}</>");
                $this->line("  Timeout : {$timeout} s");

                $socket = @fsockopen("ssl://{$host}", (int) $port, $errno, $errstr, $timeout);

                if ($socket === false) {
                    $this->error("  ✗ TCP/TLS-Verbindung fehlgeschlagen: [{$errno}] {$errstr}");
                    $this->line('    → Mögliche Ursachen: Firewall, Proxy-Block, Host nicht erreichbar,');
                    $this->line('      ungültiges TLS-Zertifikat oder falsche IP im DNS.');
                } else {
                    fclose($socket);
                    $this->line("  ✓ TCP/TLS-Verbindung zu <fg=green>{$host}:{$port}</> erfolgreich.");
                }
            }
        }

        // ------------------------------------------------------------------
        // 5. Eigentlicher Ping-Request
        // ------------------------------------------------------------------
        if ($debug) {
            $this->section('4. HTTP-Ping (GET /schools/?limit=1)');
        }

        $this->line('  Starte Kelvin-Ping …');
        $start = microtime(true);

        try {
            $schools = $client->ping();
        } catch (KelvinAuthException $e) {
            $this->outputException('Authentifizierung fehlgeschlagen', $e, $debug);
            Log::channel('ucs')->error('[ucs:ping] KelvinAuthException: '.$e->getMessage());

            return self::FAILURE;
        } catch (KelvinUnavailableException $e) {
            $this->outputException('Kelvin nicht erreichbar', $e, $debug);
            Log::channel('ucs')->error('[ucs:ping] KelvinUnavailableException: '.$e->getMessage());

            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->outputException('Unerwarteter Fehler', $e, $debug);
            Log::channel('ucs')->error('[ucs:ping] Unerwarteter Fehler: '.$e->getMessage());

            return self::FAILURE;
        }

        // ------------------------------------------------------------------
        // 6. Erfolgsmeldung
        // ------------------------------------------------------------------
        $latencyMs = round((microtime(true) - $start) * 1000, 1);
        $count     = $schools->count();

        $this->newLine();
        $this->info("✓ Kelvin erreichbar.");
        $this->line("  Schulen zurückgegeben : {$count}");
        $this->line("  Latenz                : {$latencyMs} ms");

        if ($count > 0) {
            $names = $schools->pluck('name')->filter()->take(3)->implode(', ');
            $this->line("  Erste Schul-Namen     : {$names}");
        }

        Log::channel('ucs')->info('[ucs:ping] Erfolgreich.', [
            'schools'    => $count,
            'latency_ms' => $latencyMs,
        ]);

        return self::SUCCESS;
    }

    // =========================================================================
    // Hilfsmethoden
    // =========================================================================

    /** Gibt eine Abschnittsüberschrift aus. */
    private function section(string $title): void
    {
        $this->newLine();
        $this->line("  <fg=yellow;options=bold>── {$title}</>");
        $this->newLine();
    }

    /**
     * Gibt eine Exception mit optionaler vollständiger Fehlerkette aus.
     *
     * Im Debug-Modus werden alle verketteten Exceptions und der Stack-Trace
     * der innersten Ursache ausgegeben.
     */
    private function outputException(string $label, \Throwable $e, bool $debug): void
    {
        $this->newLine();
        $this->error("✗ {$label}: {$e->getMessage()}");

        if (! $debug) {
            $this->line('  Tipp: Starten Sie den Befehl mit <fg=cyan>--debug</> für eine ausführliche Diagnose.');

            return;
        }

        // Vollständige Exception-Kette ausgeben
        $depth = 0;
        $cause = $e->getPrevious();

        while ($cause !== null) {
            $depth++;
            $prefix = str_repeat('  ', $depth);
            $this->line("{$prefix}<fg=red>Verursacht durch [".get_class($cause)."]:</> {$cause->getMessage()}");
            $cause = $cause->getPrevious();
        }

        // Stack-Trace der ursprünglichen Exception (erste 20 Zeilen)
        $this->newLine();
        $this->line('  <fg=yellow>Stack-Trace (ursprüngliche Exception):</>');
        $traceLines = explode("\n", $e->getTraceAsString());

        foreach (array_slice($traceLines, 0, 20) as $line) {
            $this->line('  '.$line);
        }

        if (count($traceLines) > 20) {
            $this->line('  … ('.( count($traceLines) - 20).' weitere Frames, --vvv für vollständigen Trace)');
        }

        // Bei ConnectionException: Tipps ausgeben
        if (str_contains($e->getMessage(), 'cURL') || str_contains($e->getMessage(), 'Connection')) {
            $this->newLine();
            $this->line('  <fg=yellow>Diagnose-Hinweise:</>');
            $this->line('  • cURL-Fehler deuten auf Netzwerkprobleme hin (Firewall, Proxy, DNS).');
            $this->line('  • Prüfen Sie die Proxy-Whitelist: docs/kelvin-api-endpunkte.md#proxy-whitelist');
            $this->line('  • Testen Sie manuell: <fg=cyan>curl -v '.($this->extractBaseUrl()).'</>');
            $this->line('  • TLS-Zertifikat des UCS-Servers prüfen (Self-Signed → Zertifikat importieren).');
        }
    }

    /** Liest kelvin_base_url direkt aus den Settings (für Hinweistexte). */
    private function extractBaseUrl(): string
    {
        try {
            /** @var UcsSetting $s */
            $s = app(UcsSetting::class);

            return rtrim($s->kelvin_base_url ?? '<URL>', '/').'/../schools/?limit=1';
        } catch (\Throwable) {
            return '<kelvin-base-url>/schools/?limit=1';
        }
    }
}

