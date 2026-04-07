<?php

namespace App\Console\Commands;

use App\Model\Child;
use App\Model\Schickzeiten;
use App\Settings\CareSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupSchickzeitenNonCareChildren extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schickzeiten:cleanup-non-care
                            {--dry-run : Zeige nur an, was gelöscht würde, ohne zu löschen}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Löscht alle Schickzeiten von Kindern, die nicht mehr in einer Gruppe oder Klasse des Care-Moduls sind';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $careSettings = new CareSetting();
        $allowedGroups = $careSettings->groups_list;
        $allowedClasses = $careSettings->class_list;

        if (empty($allowedGroups) || empty($allowedClasses)) {
            $this->warn('⚠️  Keine Gruppen oder Klassen im Care-Modul konfiguriert. Abbruch.');
            return Command::FAILURE;
        }

        if ($dryRun) {
            $this->warn('⚠️  DRY-RUN Modus: Es werden keine Daten gelöscht!');
        }

        $this->info("🔍 Suche Kinder, die nicht mehr im Care-Modul sind...");
        $this->info("   Erlaubte Gruppen: " . implode(', ', $allowedGroups));
        $this->info("   Erlaubte Klassen: " . implode(', ', $allowedClasses));

        // Finde alle Kinder, die NICHT in den erlaubten Gruppen UND Klassen sind
        $nonCareChildren = Child::query()
            ->where(function ($query) use ($allowedGroups, $allowedClasses) {
                $query->whereNotIn('group_id', $allowedGroups)
                    ->orWhereNotIn('class_id', $allowedClasses)
                    ->orWhereNull('group_id')
                    ->orWhereNull('class_id');
            })
            ->whereHas('schickzeiten')
            ->with('schickzeiten')
            ->get();

        if ($nonCareChildren->isEmpty()) {
            $this->info('✓ Keine betroffenen Kinder mit Schickzeiten gefunden.');
            return Command::SUCCESS;
        }

        $this->info("\n📈 Betroffene Kinder: {$nonCareChildren->count()}");

        $totalCount = 0;
        $tableData = [];

        foreach ($nonCareChildren as $child) {
            $count = $child->schickzeiten()->count();
            $totalCount += $count;
            $tableData[] = [
                "{$child->first_name} {$child->last_name}",
                $child->group_id ?? 'keine',
                $child->class_id ?? 'keine',
                $count,
            ];
        }

        $this->table(
            ['Kind', 'Gruppe', 'Klasse', 'Anzahl Schickzeiten'],
            $tableData
        );

        $this->info("Gesamt zu löschende Schickzeiten: {$totalCount}");

        if (!$dryRun) {
            // Im nicht-interaktiven Modus (z. B. Scheduler) ohne Rückfrage ausführen
            if ($this->input->isInteractive()) {
                if (!$this->confirm("Möchten Sie {$totalCount} Schickzeiten für {$nonCareChildren->count()} Kinder wirklich löschen?", true)) {
                    $this->warn('Abgebrochen.');
                    return Command::FAILURE;
                }
            }

            $deleted = 0;
            $this->info("\n🔄 Lösche Schickzeiten...");
            $progressBar = $this->output->createProgressBar($totalCount);
            $progressBar->start();

            foreach ($nonCareChildren as $child) {
                // Alle Schickzeiten des Kindes abrufen (ohne den Datumsfilter der Relation)
                $schickzeiten = Schickzeiten::where('child_id', $child->id)->get();

                foreach ($schickzeiten as $schickzeit) {
                    $schickzeit->delete();
                    $deleted++;
                    $progressBar->advance();
                }

                Log::info("Schickzeiten für Kind {$child->first_name} {$child->last_name} (ID: {$child->id}) gelöscht – Kind ist nicht mehr im Care-Modul.");
            }

            $progressBar->finish();
            $this->newLine();
            $this->info("✓ {$deleted} Schickzeiten erfolgreich gelöscht.");
        } else {
            $this->info("\n✓ DRY-RUN abgeschlossen. {$totalCount} Einträge würden gelöscht werden.");
        }

        return Command::SUCCESS;
    }
}


