<?php

namespace App\Console\Commands;

use App\Model\Schickzeiten;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CleanupOldSchickzeiten extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schickzeiten:cleanup
                            {--weeks=2 : Anzahl der Wochen, nach denen spezifische Schickzeiten gelöscht werden}
                            {--dry-run : Zeige nur an, was gelöscht würde, ohne zu löschen}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Löscht Schickzeiten mit spezifischem Datum, die älter als X Wochen sind';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $weeks = (int) $this->option('weeks');
        $dryRun = $this->option('dry-run');

        $date = Carbon::now()->subWeeks($weeks);

        $this->info("🗑️  Bereinige Schickzeiten mit spezifischem Datum, die älter als {$weeks} Wochen sind (vor {$date->format('d.m.Y')})");

        if ($dryRun) {
            $this->warn('⚠️  DRY-RUN Modus: Es werden keine Daten gelöscht!');
        }

        // Build query - nur Schickzeiten mit spezifischem Datum (nicht die wiederkehrenden)
        $query = Schickzeiten::whereNotNull('specific_date')
            ->where('specific_date', '<', $date);

        // Count schickzeiten to be deleted
        $count = $query->count();

        if ($count === 0) {
            $this->info('✓ Keine Schickzeiten zum Löschen gefunden.');

            return Command::SUCCESS;
        }

        // Show statistics
        $this->info("\n📈 Statistik:");
        $this->info("Zu löschende Schickzeiten: {$count}");

        // Show date range
        $oldest = Schickzeiten::whereNotNull('specific_date')
            ->where('specific_date', '<', $date)
            ->orderBy('specific_date', 'asc')
            ->first();

        if ($oldest) {
            $this->info("Ältestes spezifisches Datum: {$oldest->specific_date->format('d.m.Y')}");
        }

        // Show breakdown by type
        $this->info("\n📊 Aufschlüsselung nach Typ:");
        $types = Schickzeiten::whereNotNull('specific_date')
            ->where('specific_date', '<', $date)
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get();

        $table = [];
        foreach ($types as $typeData) {
            $table[] = [$typeData->type ?? 'Ohne Typ', $typeData->count];
        }
        $this->table(['Typ', 'Anzahl'], $table);

        // Confirm deletion
        if (!$dryRun) {
            if (!$this->confirm("Möchten Sie {$count} Schickzeiten wirklich löschen?", true)) {
                $this->warn('Abgebrochen.');

                return Command::FAILURE;
            }

            // Delete schickzeiten
            $this->info("\n🔄 Lösche Schickzeiten...");
            $progressBar = $this->output->createProgressBar($count);
            $progressBar->start();

            // Delete in chunks to avoid memory issues
            $deleted = 0;
            $chunkSize = 100;

            while (true) {
                $chunk = Schickzeiten::whereNotNull('specific_date')
                    ->where('specific_date', '<', $date)
                    ->limit($chunkSize)
                    ->get();

                if ($chunk->isEmpty()) {
                    break;
                }

                foreach ($chunk as $schickzeit) {
                    $schickzeit->delete();
                    $deleted++;
                    $progressBar->advance();
                }
            }

            $progressBar->finish();

            $this->newLine();
            $this->info("✓ {$deleted} Schickzeiten erfolgreich gelöscht.");

            return Command::SUCCESS;
        } else {
            $this->info("\n✓ DRY-RUN abgeschlossen. {$count} Einträge würden gelöscht werden.");

            return Command::SUCCESS;
        }
    }
}

