<?php

namespace App\Console\Commands;

use App\Services\Ucs\UcsSyncService;
use App\Settings\UcsSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Manueller Auslöser für den UCS@school-Elternsync.
 *
 * – Ohne Optionen: Führt UcsSyncService::run() direkt und synchron aus.
 *   Kein Queue-Worker / Supervisor erforderlich – funktioniert auf Shared-Hosting.
 * – Mit --dry-run: Ruft UcsSyncService::run(dryRun: true) auf,
 *   schreibt keine DB-Änderungen und gibt den Counter-Report auf STDOUT aus.
 *
 * Exit-Codes:
 *   0 – Erfolgreich
 *   1 – Fehler beim Sync
 *   2 – Integration oder Sync deaktiviert (keine Mutation)
 *
 * Hinweis: SyncUcsSchoolJob bleibt erhalten und kann für manuelles asynchrones
 *          Dispatching verwendet werden (z. B. SyncUcsSchoolJob::dispatch() aus
 *          einem Controller). Der Scheduler nutzt diesen Command direkt.
 *
 * @see docs/ucs-kelvin-integration-konzept.md §5.1, §5.4
 */
class SyncUcsParents extends Command
{
    protected $signature = 'sync:ucs-parents
        {--dry-run : Nur zählen, keine DB-Änderungen}';

    protected $description = 'UCS@school-Elternsync direkt und synchron starten (kein Queue-Worker nötig).';

    public function handle(UcsSyncService $svc): int
    {
        /** @var UcsSetting $settings */
        $settings = app(UcsSetting::class);

        if (! $settings->enabled) {
            $this->warn('UCS-Integration ist deaktiviert (UcsSetting::enabled=false). Kein Sync durchgeführt.');
            Log::channel('ucs')->info('[sync:ucs-parents] Abgebrochen: enabled=false.');

            return 2;
        }

        if (! $settings->sync_enabled && ! $this->option('dry-run')) {
            $this->warn('Sync ist deaktiviert (UcsSetting::sync_enabled=false). Kein Sync durchgeführt.');
            Log::channel('ucs')->info('[sync:ucs-parents] Abgebrochen: sync_enabled=false.');

            return 2;
        }

        if ($this->option('dry-run')) {
            $this->info('Dry-Run-Modus: Keine Datenbankänderungen.');
            Log::channel('ucs')->info('[sync:ucs-parents] Dry-Run gestartet.');

            try {
                $counts = $svc->run(dryRun: true);
            } catch (\Throwable $e) {
                $this->error('Dry-Run fehlgeschlagen: '.$e->getMessage());
                Log::channel('ucs')->error('[sync:ucs-parents] Dry-Run Fehler: '.$e->getMessage());

                return self::FAILURE;
            }

            $this->printCounterReport($counts);

            return self::SUCCESS;
        }

        // Echter Sync: direkt synchron, kein Queue-Worker erforderlich
        $this->info('Starte UCS-Elternsync (synchron) …');
        Log::channel('ucs')->info('[sync:ucs-parents] Starte Bulk-Sync direkt (kein Job-Dispatch).');

        try {
            $counts = $svc->run();
        } catch (\Throwable $e) {
            $this->error('Sync fehlgeschlagen: '.$e->getMessage());
            Log::channel('ucs')->error('[sync:ucs-parents] Bulk-Sync fehlgeschlagen.', [
                'error' => $e->getMessage(),
            ]);

            // Fehlerstatus in UcsSetting persistieren (wie SyncUcsSchoolJob::failed())
            try {
                $settings->last_sync_status  = 'failed';
                $settings->last_sync_message = mb_substr($e->getMessage(), 0, 200);
                $settings->save();
            } catch (\Throwable) {
                // Settings-Speicherung darf den Fehlerfluss nicht blockieren
            }

            // Sentry-Capture (wie SyncUcsSchoolJob::failed())
            if (app()->bound('sentry')) {
                app('sentry')->captureException($e);
            }

            return self::FAILURE;
        }

        $this->info('✓ Sync erfolgreich abgeschlossen.');
        Log::channel('ucs')->info('[sync:ucs-parents] Bulk-Sync abgeschlossen.', $counts);

        $this->printCounterReport($counts);

        return self::SUCCESS;
    }

    /**
     * Gibt den Counter-Report formatiert auf STDOUT aus.
     *
     * @param  array<string, mixed>  $counts
     */
    private function printCounterReport(array $counts): void
    {
        $this->line('');
        $this->line('=== Sync-Report ===');
        $this->line("Schule:               ".($counts['school'] ?? '–'));
        $this->line("Dry-Run:              ".($counts['dry_run'] ? 'ja' : 'nein'));
        $this->line("Eltern verarbeitet:   ".($counts['parents_processed'] ?? 0));
        $this->line("  neu angelegt:       ".($counts['parents_created'] ?? 0));
        $this->line("  aktualisiert:       ".($counts['parents_updated'] ?? 0));
        $this->line("  deaktiviert:        ".($counts['parents_deactivated'] ?? 0));
        $this->line("Kinder neu:           ".($counts['children_created'] ?? 0));
        $this->line("Kinder aktualisiert:  ".($counts['children_updated'] ?? 0));
        $this->line("Kinder lokal skip:    ".($counts['children_skipped_local'] ?? 0));
        $this->line("Link-Kandidaten:      ".($counts['link_candidates_created'] ?? 0));
        $this->line("Gruppen provisioniert:".($counts['groups_provisioned'] ?? 0));
        $this->line("Fehler (Eltern):      ".($counts['failed_parents'] ?? 0));
        $this->line("Dauer:                ".($counts['duration_seconds'] ?? 0)." s");
        $this->line('===================');
    }
}
