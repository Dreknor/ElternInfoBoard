<?php

namespace App\Console\Commands;

use App\Model\ChildCheckIn;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CleanupOldCheckIns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'checkins:cleanup
                            {--months=3 : Anzahl der Monate, nach denen CheckIns gelöscht werden}
                            {--dry-run : Zeige nur an, was gelöscht würde, ohne zu löschen}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Löscht alte CheckIn-Einträge, die älter als X Monate sind';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $months = (int) $this->option('months');
        $dryRun = $this->option('dry-run');

        $date = Carbon::now()->subMonths($months);

        $this->info("🗑️  Bereinige CheckIns, die älter als {$months} Monate sind (vor {$date->format('d.m.Y')})");

        if ($dryRun) {
            $this->warn('⚠️  DRY-RUN Modus: Es werden keine Daten gelöscht!');
        }

        // Build query - basierend auf dem Datum der CheckIns
        $query = ChildCheckIn::where('date', '<', $date);

        // Count checkIns to be deleted
        $count = $query->count();

        if ($count === 0) {
            $this->info('✓ Keine CheckIns zum Löschen gefunden.');

            return Command::SUCCESS;
        }

        // Show statistics
        $this->info("\n📈 Statistik:");
        $this->info("Zu löschende CheckIns: {$count}");

        // Show date range
        $oldest = ChildCheckIn::where('date', '<', $date)->orderBy('date', 'asc')->first();
        if ($oldest) {
            $this->info("Ältester Eintrag: {$oldest->date->format('d.m.Y')}");
        }

        // Confirm deletion
        if (!$dryRun) {
            if (!$this->confirm("Möchten Sie {$count} CheckIn-Einträge wirklich löschen?", true)) {
                $this->warn('Abgebrochen.');

                return Command::FAILURE;
            }

            // Delete checkIns
            $this->info("\n🔄 Lösche CheckIns...");
            $progressBar = $this->output->createProgressBar($count);
            $progressBar->start();

            // Delete in chunks to avoid memory issues
            $deleted = 0;
            $chunkSize = 100;

            while (true) {
                $chunk = ChildCheckIn::where('date', '<', $date)
                    ->limit($chunkSize)
                    ->get();

                if ($chunk->isEmpty()) {
                    break;
                }

                foreach ($chunk as $checkIn) {
                    $checkIn->delete();
                    $deleted++;
                    $progressBar->advance();
                }
            }

            $progressBar->finish();

            $this->newLine();
            $this->info("✓ {$deleted} CheckIn-Einträge erfolgreich gelöscht.");

            return Command::SUCCESS;
        } else {
            $this->info("\n✓ DRY-RUN abgeschlossen. {$count} Einträge würden gelöscht werden.");

            return Command::SUCCESS;
        }
    }
}

