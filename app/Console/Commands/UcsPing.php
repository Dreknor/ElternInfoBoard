<?php

namespace App\Console\Commands;

use App\Services\Ucs\Exceptions\KelvinAuthException;
use App\Services\Ucs\Exceptions\KelvinUnavailableException;
use App\Services\Ucs\KelvinClient;
use Illuminate\Console\Command;
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
 * @see docs/ucs-kelvin-integration-konzept.md §3, §7.6
 */
class UcsPing extends Command
{
    protected $signature = 'ucs:ping';

    protected $description = 'Kelvin-API-Connectivity-Check: Schulen auflisten, Latenz messen.';

    public function handle(KelvinClient $client): int
    {
        $this->info('UCS Kelvin Ping …');

        $start = microtime(true);

        try {
            $schools = $client->ping();
        } catch (KelvinAuthException $e) {
            $this->error('Authentifizierung fehlgeschlagen: '.$e->getMessage());
            Log::channel('ucs')->error('[ucs:ping] KelvinAuthException: '.$e->getMessage());

            return self::FAILURE;
        } catch (KelvinUnavailableException $e) {
            $this->error('Kelvin nicht erreichbar: '.$e->getMessage());
            Log::channel('ucs')->error('[ucs:ping] KelvinUnavailableException: '.$e->getMessage());

            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error('Unerwarteter Fehler: '.$e->getMessage());
            Log::channel('ucs')->error('[ucs:ping] Unerwarteter Fehler: '.$e->getMessage());

            return self::FAILURE;
        }

        $latencyMs = round((microtime(true) - $start) * 1000, 1);
        $count     = $schools->count();

        $this->info("✓ Kelvin erreichbar.");
        $this->line("  Schulen zurückgegeben: {$count}");
        $this->line("  Latenz: {$latencyMs} ms");

        if ($count > 0) {
            $names = $schools->pluck('name')->filter()->take(3)->implode(', ');
            $this->line("  Erste Schul-Namen: {$names}");
        }

        Log::channel('ucs')->info('[ucs:ping] Erfolgreich.', [
            'schools' => $count,
            'latency_ms' => $latencyMs,
        ]);

        return self::SUCCESS;
    }
}

