<?php

namespace App\Console\Commands;

use App\Jobs\SyncUcsSchoolJob;
use App\Services\Ucs\UcsSyncService;
use App\Settings\UcsSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Manueller Auslöser für den UCS@school-Elternsync.
 *
 * – Ohne Optionen: Dispatcht SyncUcsSchoolJob synchron (dispatchSync()),
 *   damit das Ergebnis in der Konsole sichtbar ist.
 * – Mit --dry-run: Ruft UcsSyncService::run(dryRun: true) direkt auf,
 *   schreibt keine DB-Änderungen und gibt den Counter-Report auf STDOUT aus.
 *
 * Exit-Codes:
 *   0 – Erfolgreich
 *   1 – Fehler beim Sync
 *   2 – Integration oder Sync deaktiviert (keine Mutation)
 *
 * @see docs/ucs-kelvin-integration-konzept.md §5.1, §5.4
 */
class SyncUcsParents extends Command
{
    protected $signature = 'sync:ucs-parents
        {--dry-run : Nur zählen, keine DB-Änderungen}';

    protected $description = 'UCS@school-Elternsync manuell starten (synchron).';

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

        // Echter Sync: synchron dispatchen, damit die Konsole das Ergebnis sieht
        $this->info('Starte UCS-Elternsync (synchron) …');
        Log::channel('ucs')->info('[sync:ucs-parents] SyncUcsSchoolJob::dispatchSync().');

        try {
            SyncUcsSchoolJob::dispatchSync();
        } catch (\Throwable $e) {
            $this->error('Sync-Job fehlgeschlagen: '.$e->getMessage());
            Log::channel('ucs')->error('[sync:ucs-parents] Job fehlgeschlagen: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('✓ Sync-Job erfolgreich abgeschlossen.');

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
        $this->line('=== Dry-Run-Report ===');
        $this->line("Schule:             ".($counts['school'] ?? '–'));
        $this->line("Dry-Run:            ".($counts['dry_run'] ? 'ja' : 'nein'));
        $this->line("Eltern verarbeitet: ".($counts['parents_processed'] ?? 0));
        $this->line("  neu angelegt:     ".($counts['parents_created'] ?? 0));
        $this->line("  aktualisiert:     ".($counts['parents_updated'] ?? 0));
        $this->line("  deaktiviert:      ".($counts['parents_deactivated'] ?? 0));
        $this->line("Kinder neu:         ".($counts['children_created'] ?? 0));
        $this->line("Kinder aktualisiert:".($counts['children_updated'] ?? 0));
        $this->line("Kinder lokal skip:  ".($counts['children_skipped_local'] ?? 0));
        $this->line("Link-Kandidaten:    ".($counts['link_candidates_created'] ?? 0));
        $this->line("Gruppen provisioniert:".($counts['groups_provisioned'] ?? 0));
        $this->line("Fehler (Eltern):    ".($counts['failed_parents'] ?? 0));
        $this->line("Dauer:              ".($counts['duration_seconds'] ?? 0)." s");
        $this->line('======================');
    }
}

