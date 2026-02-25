<?php

namespace App\Console\Commands;

use App\Model\ChildNotice;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CleanupOldChildNotices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'child-notices:cleanup
                            {--months=3 : Anzahl der Monate, nach denen Child Notices gelöscht werden}
                            {--dry-run : Zeige nur an, was gelöscht würde, ohne zu löschen}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Löscht alte Child Notice-Einträge, die älter als X Monate sind';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $months = (int) $this->option('months');
        $dryRun = $this->option('dry-run');

        $date = Carbon::now()->subMonths($months);

        $this->info("🗑️  Bereinige Child Notices, die älter als {$months} Monate sind (vor {$date->format('d.m.Y')})");

        if ($dryRun) {
            $this->warn('⚠️  DRY-RUN Modus: Es werden keine Daten gelöscht!');
        }

        // Build query - basierend auf dem Datum der Child Notices
        $query = ChildNotice::where('date', '<', $date);

        // Count child notices to be deleted
        $count = $query->count();

        if ($count === 0) {
            $this->info('✓ Keine Child Notices zum Löschen gefunden.');

            return Command::SUCCESS;
        }

        // Show statistics
        $this->info("\n📈 Statistik:");
        $this->info("Zu löschende Child Notices: {$count}");

        // Show date range
        $oldest = ChildNotice::where('date', '<', $date)->orderBy('date', 'asc')->first();
        if ($oldest) {
            $this->info("Ältester Eintrag: {$oldest->date->format('d.m.Y')}");
        }

        // Show breakdown by child
        $this->info("\n📊 Aufschlüsselung nach Anzahl Einträge pro Kind:");
        $childStats = ChildNotice::where('date', '<', $date)
            ->selectRaw('child_id, COUNT(*) as count')
            ->groupBy('child_id')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        if ($childStats->isNotEmpty()) {
            $table = [];
            foreach ($childStats as $stat) {
                $childName = $stat->child ? $stat->child->firstname . ' ' . $stat->child->lastname : "Kind #{$stat->child_id}";
                $table[] = [$childName, $stat->count];
            }
            $this->table(['Kind', 'Anzahl'], $table);
        }

        // Confirm deletion
        if (!$dryRun) {
            if (!$this->confirm("Möchten Sie {$count} Child Notice-Einträge wirklich löschen?", true)) {
                $this->warn('Abgebrochen.');

                return Command::FAILURE;
            }

            // Delete child notices
            $this->info("\n🔄 Lösche Child Notices...");
            $progressBar = $this->output->createProgressBar($count);
            $progressBar->start();

            // Delete in chunks to avoid memory issues
            $deleted = 0;
            $chunkSize = 100;

            while (true) {
                $chunk = ChildNotice::where('date', '<', $date)
                    ->limit($chunkSize)
                    ->get();

                if ($chunk->isEmpty()) {
                    break;
                }

                foreach ($chunk as $notice) {
                    $notice->delete();
                    $deleted++;
                    $progressBar->advance();
                }
            }

            $progressBar->finish();

            $this->newLine();
            $this->info("✓ {$deleted} Child Notice-Einträge erfolgreich gelöscht.");

            return Command::SUCCESS;
        } else {
            $this->info("\n✓ DRY-RUN abgeschlossen. {$count} Einträge würden gelöscht werden.");

            return Command::SUCCESS;
        }
    }
}

