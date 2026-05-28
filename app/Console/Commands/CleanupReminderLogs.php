<?php

namespace App\Console\Commands;

use App\Model\ReminderLog;
use Illuminate\Console\Command;

class CleanupReminderLogs extends Command
{
    protected $signature = 'reminder-logs:cleanup
                            {--days=365 : Aufbewahrungsdauer in Tagen}';

    protected $description = 'Löscht Erinnerungs-Protokolleinträge älter als X Tage (Standard: 365).';

    public function handle(): int
    {
        $days   = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $deleted = ReminderLog::where('sent_at', '<', $cutoff)->delete();

        $this->info("✓ {$deleted} reminder_logs-Einträge gelöscht (vor {$cutoff->format('d.m.Y')}).");

        return self::SUCCESS;
    }
}

