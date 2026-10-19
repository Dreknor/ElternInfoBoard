<?php

namespace App\Console\Commands;

use App\Model\Module;
use App\Services\Elternabfragen\ExportService;
use App\Services\Elternabfragen\InquiryService;
use App\Services\Elternabfragen\ReminderService;
use App\Services\Elternabfragen\RetentionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Zeitgesteuerte Abläufe der Elternabfragen: geplante Abfragen starten, automatische
 * Erinnerungen, automatischer Abschluss, abgelaufene Exporte und Löschfristen.
 */
class ElternabfragenProcess extends Command
{
    protected $signature = 'elternabfragen:process
                            {--dry-run : Nur anzeigen, was gelöscht würde (Starts und Erinnerungen werden nicht ausgeführt)}';

    protected $description = 'Startet geplante Elternabfragen, versendet Erinnerungen und wendet Lösch- und Exportfristen an.';

    public function handle(InquiryService $inquiries, ReminderService $reminders, ExportService $exports, RetentionService $retention): int
    {
        if (! Schema::hasTable('inquiries')) {
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $due = $retention->due();
            $this->info(count($due).' Abfrage(n) mit abgelaufener Aufbewahrungsfrist:');
            foreach ($due as $inquiry) {
                $this->line(' - #'.$inquiry->id.' '.$inquiry->title.' (abgeschlossen '.$inquiry->closed_at->format('d.m.Y').')');
            }

            return self::SUCCESS;
        }

        // Löschfristen und Exportablauf gelten auch bei deaktiviertem Modul
        $this->info('Exportdateien gelöscht: '.$exports->purgeExpired());
        $this->info('Abfragen nach Aufbewahrungsfrist gelöscht: '.$retention->run());

        $module = Module::where('setting', 'Elternabfragen')->first();
        $active = $module?->options['active'] ?? false;
        if (! ($active === true || $active === 1 || $active === '1')) {
            $this->warn('Modul „Elternabfragen“ ist nicht aktiv – keine Starts und Erinnerungen.');

            return self::SUCCESS;
        }

        $result = $inquiries->runSchedule();
        $this->info('Gestartet: '.$result['started'].', automatisch abgeschlossen: '.$result['closed']);
        $this->info('Automatische Erinnerungen: '.$reminders->runAutomatic());

        return self::SUCCESS;
    }
}
