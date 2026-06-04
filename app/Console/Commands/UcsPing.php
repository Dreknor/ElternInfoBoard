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
 * Ruft KelvinClient::ping() auf (GET /schools/{school}) und gibt
 * die Schuldaten sowie die HTTP-Latenz aus.
 *
 * Exit-Codes:
 *   0 – Verbindung erfolgreich
 *   1 – Fehler / Verbindung nicht möglich
 *
 * Optionen:
 *   --debug   Gibt Konfiguration, DNS-Check und TCP-Check vor dem HTTP-Call aus.
 *
 * Hinweis: Der Ping verwendet GET /schools/{school} (nicht GET /schools/?limit=1),
 * da nur ersterer URL-Pfad in der Proxy-Whitelist freigeschaltet ist.
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
        // 1. Konfiguration anzeigen (immer, nicht nur im Debug-Modus)
        // ------------------------------------------------------------------
        $this->section('1. Konfiguration');

        $baseUrl      = rtrim($settings->kelvin_base_url ?? '', '/');
        $baseUrlDisplay = empty($baseUrl) ? '<fg=red>(nicht gesetzt)</>' : $baseUrl.'/';
        $tokenUrl     = empty($baseUrl)
            ? '<fg=red>(nicht ableitbar – kelvin_base_url fehlt)</>'
            : preg_replace('#/v\d+/?$#i', '', $baseUrl).'/token';
        $username     = $settings->kelvin_username ?? '<fg=red>(nicht gesetzt)</>';
        $password     = empty($settings->kelvin_password) ? '<fg=red>(leer / nicht gesetzt)</>' : str_repeat('*', 8).' (gesetzt)';
        $school       = $settings->school ?? '<fg=red>(nicht gesetzt)</>';
        $timeout      = $settings->kelvin_timeout.' s';
        $tokenTtl     = $settings->kelvin_token_ttl.' s';
        $enabled      = $settings->enabled ? '<fg=green>JA</>' : '<fg=red>NEIN</>';
        $tokenInCache = Cache::has(self::TOKEN_CACHE_KEY) ? '<fg=green>vorhanden</>' : '<fg=yellow>nicht vorhanden (wird neu geholt)</>';
        $pingEndpoint = empty($settings->kelvin_base_url) || empty($settings->school)
            ? '<fg=red>(nicht ableitbar)</>'
            : rtrim($settings->kelvin_base_url, '/').'/schools/'.rawurlencode($settings->school ?? '');

        $this->table(
            ['Einstellung', 'Wert'],
            [
                ['UCS Integration aktiv',       $enabled],
                ['kelvin_base_url (v1)',         $baseUrlDisplay],
                ['Token-Endpunkt (abgeleitet)',  $tokenUrl],
                ['Ping-Endpunkt (abgeleitet)',   $pingEndpoint],
                ['kelvin_username',              $username],
                ['kelvin_password',              $password],
                ['school (UCS_SCHOOL)',          $school],
                ['kelvin_timeout',               $timeout],
                ['kelvin_token_ttl',             $tokenTtl],
                ['Bearer-Token im Cache',        $tokenInCache],
            ],
        );

        // ------------------------------------------------------------------
        // 1a. Konfigurationsvalidierung – Abbruch bei fehlenden Pflichtfeldern
        // ------------------------------------------------------------------
        $configErrors = [];

        if (empty($settings->kelvin_base_url)) {
            $configErrors[] = 'kelvin_base_url ist nicht gesetzt.';
        }

        if (empty($settings->kelvin_username)) {
            $configErrors[] = 'kelvin_username ist nicht gesetzt.';
        }

        if (empty($settings->kelvin_password)) {
            $configErrors[] = 'kelvin_password ist nicht gesetzt.';
        }

        if (empty($settings->school)) {
            $configErrors[] = 'school (UCS_SCHOOL) ist nicht gesetzt – wird für GET /schools/{school} benötigt.';
        }

        if (! empty($configErrors)) {
            $this->newLine();
            $this->error('✗ Fehlende Pflicht-Konfiguration – Ping wird ABGEBROCHEN:');

            foreach ($configErrors as $err) {
                $this->line("  • {$err}");
            }

            $this->newLine();
            $this->line('  <fg=yellow>Hintergrund:</>');
            $this->line('  Die UCS-Einstellungen werden über Spatie Laravel Settings in der');
            $this->line('  Datenbank gespeichert. Die Initialwerte kommen aus der <fg=cyan>.env</>-Datei');
            $this->line('  und werden per <fg=cyan>php artisan settings:migrate</> einmalig übernommen.');
            $this->newLine();
            $this->line('  <fg=yellow>Option A – .env setzen und Settings-Migration ausführen:</>');
            $this->line('  1. In der <fg=cyan>.env</> folgende Variablen setzen:');
            $this->line('       UCS_ENABLED=true');
            $this->line('       UCS_KELVIN_BASE_URL=https://<ucs-host>/ucsschool/kelvin/v1');
            $this->line('       UCS_KELVIN_USER=<service-account>');
            $this->line('       UCS_KELVIN_PASSWORD=<passwort>');
            $this->line('       UCS_SCHOOL=<schulname>');
            $this->line('  2. Falls die Settings-Migration noch nicht lief:');
            $this->line('       <fg=cyan>php artisan settings:migrate</>');
            $this->line('  3. Falls die Migration bereits lief (Werte in DB überschreiben):');
            $this->line('       <fg=cyan>php artisan tinker</>');
            $this->line('       >>> $s = app(\App\Settings\UcsSetting::class);');
            $this->line('       >>> $s->kelvin_base_url = \'https://<ucs-host>/ucsschool/kelvin/v1\';');
            $this->line('       >>> $s->kelvin_username = \'<service-account>\';');
            $this->line('       >>> $s->kelvin_password = \'<passwort>\';');
            $this->line('       >>> $s->enabled = true;');
            $this->line('       >>> $s->save();');
            $this->newLine();
            $this->line('  <fg=yellow>Option B – Admin-Panel:</>');
            $this->line('  Admin-Panel öffnen → Einstellungen → UCS-Integration → Felder ausfüllen.');
            $this->newLine();
            $this->line('  Danach erneut ausführen: <fg=cyan>php artisan ucs:ping --debug</>');
            $this->newLine();

            Log::channel('ucs')->error('[ucs:ping] Abgebrochen: Pflicht-Konfiguration fehlt.', [
                'missing' => $configErrors,
            ]);

            return self::FAILURE;
        }

        if (! $settings->enabled) {
            $this->warn('⚠  UCS-Integration ist in den Einstellungen DEAKTIVIERT.');
            $this->line('   Der Ping wird trotzdem ausgeführt, um die Erreichbarkeit zu prüfen.');
        }

        // ------------------------------------------------------------------
        // 1b. Token ggf. aus Cache löschen (unabhängig von --debug)
        // ------------------------------------------------------------------
        if ($this->option('flush-token')) {
            Cache::forget(self::TOKEN_CACHE_KEY);
            $this->line('  → Bearer-Token aus Cache gelöscht (--flush-token).');
        }

        if ($debug) {
            // ------------------------------------------------------------------
            // 2. DNS-Auflösung
            // ------------------------------------------------------------------
            $this->section('2. DNS-Auflösung');

            $host = parse_url($settings->kelvin_base_url, PHP_URL_HOST);

            if (empty($host)) {
                $this->warn('  ⚠  Kein Hostname aus kelvin_base_url extrahierbar – DNS-Check übersprungen.');
                $this->line("  URL war: {$settings->kelvin_base_url}");
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
                // 3. TCP-Verbindungscheck
                // ------------------------------------------------------------------
                $this->section('3. TCP-Verbindungscheck (Port 443)');

                $port       = parse_url($settings->kelvin_base_url, PHP_URL_PORT) ?? 443;
                $tcpTimeout = min($settings->kelvin_timeout, 5);

                $this->line("  Ziel    : <fg=cyan>{$host}:{$port}</>");
                $this->line("  Timeout : {$tcpTimeout} s");

                $socket = @fsockopen("ssl://{$host}", (int) $port, $errno, $errstr, $tcpTimeout);

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
        // 4. Eigentlicher Ping-Request
        // ------------------------------------------------------------------
        $schoolName = $settings->school ?? '';

        if ($debug) {
            $this->section("4. HTTP-Ping (GET /schools/{$schoolName})");
        }

        $this->line("  Starte Kelvin-Ping für Schule \"<fg=cyan>{$schoolName}</>\" …");
        $start = microtime(true);

        try {
            $school = $client->ping();
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
        // 5. Erfolgsmeldung
        // ------------------------------------------------------------------
        $latencyMs = round((microtime(true) - $start) * 1000, 1);

        $this->newLine();
        $this->info('✓ Kelvin erreichbar.');
        $this->line("  Schule (name)         : ".($school['name'] ?? '(kein name-Feld)'));
        $this->line("  Schule (display_name) : ".($school['display_name'] ?? '(kein display_name-Feld)'));
        $this->line("  Latenz                : {$latencyMs} ms");

        if ($debug && ! empty($school)) {
            $this->newLine();
            $this->line('  <fg=yellow>Vollständige API-Antwort:</>');
            foreach ($school as $key => $value) {
                $display = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
                $this->line("    {$key}: {$display}");
            }
        }

        Log::channel('ucs')->info('[ucs:ping] Erfolgreich.', [
            'school'     => $school['name'] ?? null,
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

    /** Liest kelvin_base_url + school aus den Settings (für curl-Hinweis). */
    private function extractBaseUrl(): string
    {
        try {
            /** @var UcsSetting $s */
            $s      = app(UcsSetting::class);
            $base   = rtrim($s->kelvin_base_url ?? '', '/');
            $school = rawurlencode($s->school ?? '<Schul-ID>');

            return "{$base}/schools/{$school}";
        } catch (\Throwable) {
            return '<kelvin-base-url>/schools/<Schul-ID>';
        }
    }
}

