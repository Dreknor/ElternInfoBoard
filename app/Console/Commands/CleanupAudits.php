<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanupAudits extends Command
{
    protected $signature = 'audits:cleanup
                            {--days=365 : Aufbewahrungsdauer in Tagen}
                            {--dry-run : Nur zählen, nicht löschen}';

    protected $description = 'Löscht Einträge der Audit-Tabelle (owen-it/laravel-auditing) älter als X Tage (Standard: 365).';

    public function handle(): int
    {
        if (! Schema::hasTable('audits')) {
            $this->warn('Tabelle "audits" existiert nicht – Skip.');

            return self::SUCCESS;
        }

        $days   = (int) $this->option('days');
        $cutoff = now()->subDays($days);
        $dry    = (bool) $this->option('dry-run');

        $count = DB::table('audits')->where('created_at', '<', $cutoff)->count();

        $this->info("Audit-Einträge älter als {$days} Tage (vor {$cutoff->format('d.m.Y')}): {$count}");

        if ($dry) {
            $this->warn('Dry-Run: keine Änderungen.');

            return self::SUCCESS;
        }

        if ($count === 0) {
            return self::SUCCESS;
        }

        // Chunked Delete, um große Tabellen ressourcenschonend zu bereinigen.
        $deleted = 0;
        do {
            $batch = DB::table('audits')
                ->where('created_at', '<', $cutoff)
                ->limit(1000)
                ->delete();
            $deleted += $batch;
        } while ($batch > 0);

        $this->info("✓ {$deleted} Audit-Einträge gelöscht.");

        return self::SUCCESS;
    }
}

