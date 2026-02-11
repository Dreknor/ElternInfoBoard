<?php

namespace App\Console\Commands;

use App\Model\Stundenplan\Schuljahr;
use App\Services\StundenplanDatabaseImporter;
use Illuminate\Console\Command;

class ClearStundenplanCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stundenplan:clear
                            {--all : Clear all Schuljahre (deletes Schuljahr records)}
                            {--inactive : Clear only inactive Schuljahre}
                            {--data-only : Clear only data (Einträge, Klassen, Zeitslots) but keep Schuljahr record}
                            {--force : Force deletion without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear stundenplan data from database (for re-import after fixes)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $clearAll = $this->option('all');
        $clearInactive = $this->option('inactive');
        $dataOnly = $this->option('data-only');
        $force = $this->option('force');

        $importer = new StundenplanDatabaseImporter();

        // Option 1: Clear only inactive Schuljahre
        if ($clearInactive) {
            $count = Schuljahr::where('is_active', false)->count();

            if ($count === 0) {
                $this->info('No inactive Schuljahre found.');
                return 0;
            }

            if (!$force && !$this->confirm("Delete {$count} inactive Schuljahre?")) {
                $this->info('Cancelled.');
                return 1;
            }

            $deleted = $importer->clearInactiveSchuljahre();
            $this->info("✓ Deleted {$deleted} inactive Schuljahre.");

            return 0;
        }

        // Option 2: Clear all Schuljahre
        if ($clearAll) {
            $schuljahre = Schuljahr::all();
            $count = $schuljahre->count();

            if ($count === 0) {
                $this->info('No Schuljahre found.');
                return 0;
            }

            if (!$force && !$this->confirm("Are you sure you want to delete ALL {$count} Schuljahre?")) {
                $this->info('Cancelled.');
                return 1;
            }

            foreach ($schuljahre as $schuljahr) {
                $this->info("Deleting Schuljahr: {$schuljahr->name}");
                $importer->clearSchuljahr($schuljahr);
            }

            $this->info("✓ Deleted {$count} Schuljahre completely.");

        } else {
            // Option 3: Clear only active Schuljahr (default)
            $schuljahr = Schuljahr::where('is_active', true)->first();

            if (!$schuljahr) {
                $this->info('No active Schuljahr found.');
                return 0;
            }

            $action = $dataOnly ? 'Clear data for' : 'Delete';
            if (!$force && !$this->confirm("{$action} active Schuljahr '{$schuljahr->name}'?")) {
                $this->info('Cancelled.');
                return 1;
            }

            $this->info("Processing Schuljahr: {$schuljahr->name}");

            if ($dataOnly) {
                // Only clear data, keep Schuljahr record
                $importer->clearSchuljahrData($schuljahr);
                $this->info("✓ Cleared data for Schuljahr '{$schuljahr->name}' (Schuljahr record kept).");
            } else {
                // Delete everything including Schuljahr
                $importer->clearSchuljahr($schuljahr);
                $this->info("✓ Deleted Schuljahr '{$schuljahr->name}' completely.");
            }
        }

        $this->newLine();
        $this->info('You can now re-import the stundenplan data.');

        return 0;
    }
}

