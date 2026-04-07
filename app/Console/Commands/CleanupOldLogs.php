<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use danielme85\LaravelLogToDB\LogToDB;
use Illuminate\Console\Command;

class CleanupOldLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:cleanup
                            {--days=30 : Anzahl der Tage, nach denen Logs gelöscht werden}
                            {--level= : Nur Logs mit diesem Level löschen (z.B. DEBUG, INFO)}
                            {--dry-run : Zeige nur an, was gelöscht würde, ohne zu löschen}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Löscht alte Log-Einträge aus der Datenbank';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $level = $this->option('level');
        $dryRun = $this->option('dry-run');

        $date = Carbon::now()->subDays($days);

        $this->info("🗑️  Bereinige Logs, die älter als {$days} Tage sind (vor {$date->format('d.m.Y H:i:s')})");

        if ($level) {
            $this->info("📊 Filtere nach Level: {$level}");
        }

        if ($dryRun) {
            $this->warn('⚠️  DRY-RUN Modus: Es werden keine Daten gelöscht!');
        }

        // Build query
        $query = LogToDB::model()->where('created_at', '<', $date);

        if ($level) {
            $query->where('level_name', strtoupper($level));
        }

        // Count logs to be deleted
        $count = $query->count();

        if ($count === 0) {
            $this->info('✓ Keine Logs zum Löschen gefunden.');

            return Command::SUCCESS;
        }

        // Show statistics by level
        $this->info("\n📈 Statistik der zu löschenden Logs:");
        $stats = LogToDB::model()
            ->where('created_at', '<', $date)
            ->selectRaw('level_name, COUNT(*) as count')
            ->groupBy('level_name')
            ->orderBy('count', 'desc')
            ->get();

        $table = [];
        foreach ($stats as $stat) {
            $table[] = [$stat->level_name, $stat->count];
        }
        $this->table(['Level', 'Anzahl'], $table);

        // Confirm deletion
        if (! $dryRun) {
            if (! $this->confirm("Möchten Sie {$count} Log-Einträge wirklich löschen?", true)) {
                $this->warn('Abgebrochen.');

                return Command::FAILURE;
            }

            // Delete logs
            $this->info("\n🔄 Lösche Logs...");
            $progressBar = $this->output->createProgressBar($count);
            $progressBar->start();

            // Delete in chunks to avoid memory issues
            $deleted = 0;
            while (true) {
                $chunk = LogToDB::model()
                    ->where('created_at', '<', $date)
                    ->when($level, function ($q) use ($level) {
                        return $q->where('level_name', strtoupper($level));
                    })
                    ->limit(1000)
                    ->get();

                if ($chunk->isEmpty()) {
                    break;
                }

                foreach ($chunk as $log) {
                    $log->delete();
                    $deleted++;
                    $progressBar->advance();
                }
            }

            $progressBar->finish();
            $this->newLine(2);
            $this->info("✓ Erfolgreich {$deleted} Log-Einträge gelöscht!");
        } else {
            $this->info("\n✓ DRY-RUN abgeschlossen. Es würden {$count} Log-Einträge gelöscht werden.");
        }

        // Show remaining logs count
        $remaining = LogToDB::model()->count();
        $this->info("📊 Verbleibende Logs in der Datenbank: {$remaining}");

        return Command::SUCCESS;
    }
}
