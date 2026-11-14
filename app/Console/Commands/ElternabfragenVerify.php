<?php

namespace App\Console\Commands;

use App\Model\Inquiry;
use App\Model\InquiryReceipt;
use App\Services\Elternabfragen\ReceiptService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Prüft die Quittungskette (Erklärungsquittungen) einer oder aller Abfragen.
 * Exit-Code 1, sobald eine Kette fehlerhaft ist. Fehler werden zusätzlich protokolliert
 * (nur IDs und Anzahl, keine personenbezogenen Daten).
 */
class ElternabfragenVerify extends Command
{
    protected $signature = 'elternabfragen:verify
                            {inquiry? : ID der Abfrage}
                            {--all : Alle Abfragen mit Quittungen prüfen}';

    protected $description = 'Prüft die Erklärungsquittungen (Hash-Kette, Wortlaut, Antworten) von Elternabfragen.';

    public function handle(ReceiptService $receipts): int
    {
        if (! Schema::hasTable('inquiry_receipts')) {
            $this->warn('Tabelle für Erklärungsquittungen fehlt – bitte zuerst migrieren.');

            return self::FAILURE;
        }

        $id = $this->argument('inquiry');
        if ($id === null && ! $this->option('all')) {
            $this->error('Bitte die ID einer Abfrage angeben oder --all verwenden.');

            return self::INVALID;
        }

        if ($id !== null) {
            $inquiry = Inquiry::withTrashed()->find((int) $id);
            if ($inquiry === null) {
                $this->error('Abfrage #'.$id.' nicht gefunden.');

                return self::FAILURE;
            }
            $inquiries = collect([$inquiry]);
        } else {
            $inquiries = Inquiry::withTrashed()
                ->whereIn('id', InquiryReceipt::query()->select('inquiry_id')->distinct())
                ->orderBy('id')
                ->get(['id', 'title', 'deleted_at']);
        }

        $failed = 0;
        $checked = 0;
        foreach ($inquiries as $inquiry) {
            $result = $receipts->verifyChain($inquiry);
            $checked++;

            if ($result['valid']) {
                $this->line('<info>OK</info>     #'.$inquiry->id.' '.$inquiry->title.' ('.$result['count'].' '.($result['count'] === 1 ? 'Quittung' : 'Quittungen').')');

                continue;
            }

            $failed++;
            $this->line('<error>FEHLER</error> #'.$inquiry->id.' '.$inquiry->title.' ('.$result['count'].' Quittungen, '.count($result['errors']).' Fehler)');
            foreach ($result['errors'] as $error) {
                $this->line('         - '.$error);
            }
            Log::warning('Elternabfragen: Quittungskette fehlerhaft', [
                'inquiry_id' => $inquiry->id,
                'receipts' => $result['count'],
                'errors' => count($result['errors']),
            ]);
        }

        if ($checked === 0) {
            $this->info('Keine Abfragen mit Quittungen vorhanden.');
        } else {
            $this->newLine();
            $this->line($checked.' '.($checked === 1 ? 'Abfrage' : 'Abfragen').' geprüft, '.$failed.' mit Fehlern.');
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
