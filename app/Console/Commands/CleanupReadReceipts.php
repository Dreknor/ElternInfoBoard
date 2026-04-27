<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanupReadReceipts extends Command
{
    protected $signature = 'read-receipts:cleanup
                            {--days=365 : Aufbewahrungsdauer in Tagen}';

    protected $description = 'Löscht Lesebestätigungen älter als X Tage (Standard: 365) sowie verwaiste Receipts.';

    public function handle(): int
    {
        if (! Schema::hasTable('read_receipts')) {
            $this->warn('Tabelle "read_receipts" existiert nicht – Skip.');

            return self::SUCCESS;
        }

        $days   = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $byAge = DB::table('read_receipts')
            ->where('created_at', '<', $cutoff)
            ->delete();

        // Verwaiste Receipts (Post bereits gelöscht) entfernen
        $orphans = DB::table('read_receipts')
            ->leftJoin('posts', 'read_receipts.post_id', '=', 'posts.id')
            ->whereNull('posts.id')
            ->delete();

        $this->info("✓ {$byAge} Receipts älter als {$days} Tage gelöscht, {$orphans} verwaiste Receipts entfernt.");

        return self::SUCCESS;
    }
}

