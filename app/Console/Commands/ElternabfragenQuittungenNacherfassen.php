<?php

namespace App\Console\Commands;

use App\Model\Inquiry;
use App\Services\Elternabfragen\ReceiptBackfill;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Erklärungsquittungen für den Bestand nacherfassen (Erklärungen aus der Zeit vor den
 * Quittungen). Idempotent; läuft nach dem Update automatisch über den Scheduler, solange
 * Erklärungen ohne Quittung vorhanden sind. Versendet keine E-Mails.
 */
class ElternabfragenQuittungenNacherfassen extends Command
{
    protected $signature = 'elternabfragen:quittungen-nacherfassen
                            {--dry-run : Nur anzeigen, was nacherfasst würde}
                            {--limit= : Höchstens so viele Quittungen je Lauf (Abfragen werden immer vollständig erfasst)}';

    protected $description = 'Legt fehlende Erklärungsquittungen für bereits abgegebene Antworten und Bestätigungen an.';

    public function handle(ReceiptBackfill $backfill): int
    {
        if (! Schema::hasTable('inquiry_receipts')) {
            $this->warn('Tabelle für Erklärungsquittungen fehlt – bitte zuerst migrieren.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $limit = $this->option('limit') !== null ? max(1, (int) $this->option('limit')) : null;

        $stats = $backfill->run($dryRun, $limit, function (Inquiry $inquiry, int $count, bool $appended) use ($dryRun) {
            $this->line(($dryRun ? 'würde erfassen' : 'erfasst').': #'.$inquiry->id.' '.$inquiry->title.' – '.$count.' '
                .($count === 1 ? 'Quittung' : 'Quittungen').($appended ? ' (an bestehende Kette angehängt)' : ''));
        });

        $this->newLine();
        $this->info(($dryRun ? 'Probelauf: ' : '').$stats['receipts'].' '.($stats['receipts'] === 1 ? 'Quittung' : 'Quittungen')
            .' in '.$stats['inquiries'].' '.($stats['inquiries'] === 1 ? 'Abfrage' : 'Abfragen').($dryRun ? ' würden nacherfasst.' : ' nacherfasst.'));
        if ($stats['failed'] > 0) {
            $this->error($stats['failed'].' '.($stats['failed'] === 1 ? 'Abfrage' : 'Abfragen').' konnten nicht nacherfasst werden (siehe Log).');
        }

        if (! $dryRun && ($stats['receipts'] > 0 || $stats['failed'] > 0)) {
            Log::info('Elternabfragen: Quittungen nacherfasst', $stats);
        }

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
