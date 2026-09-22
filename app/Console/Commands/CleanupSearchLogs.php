<?php

namespace App\Console\Commands;

use App\Model\SearchLog;
use Illuminate\Console\Command;

class CleanupSearchLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'search-logs:cleanup
                            {--days=90 : Aufbewahrungsdauer in Tagen}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Löscht protokollierte Suchanfragen (search_logs), die älter als X Tage sind (Standard: 90).';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $deleted = SearchLog::where('created_at', '<', $cutoff)->delete();

        $this->info("✓ {$deleted} search_logs-Einträge gelöscht (vor {$cutoff->format('d.m.Y')}).");

        return self::SUCCESS;
    }
}
