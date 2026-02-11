<?php

namespace App\Console\Commands;

use App\Services\StundenplanImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportStundenplan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stundenplan:import {file : Path to JSON file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import timetable from Indiware JSON export';

    /**
     * Execute the console command.
     */
    public function handle(StundenplanImportService $importService)
    {
        $filePath = $this->argument('file');

        $this->info("Importing timetable from: {$filePath}");

        // Check if file exists
        if (!File::exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        // Read JSON file
        $this->info("Reading JSON file...");
        $jsonContent = File::get($filePath);
        $jsonData = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error("Invalid JSON: " . json_last_error_msg());
            return 1;
        }

        // Import data
        $this->info("Starting import...");
        $this->newLine();

        $result = $importService->importFromJson($jsonData);

        if ($result['success']) {
            $this->info("✅ Import successful!");
            $this->newLine();

            $stats = $result['stats'];
            $this->table(
                ['Entity', 'Count'],
                [
                    ['Schuljahr', $stats['schuljahr']],
                    ['Klassen', $stats['klassen']],
                    ['Zeitslots', $stats['zeitslots']],
                    ['Lehrer', $stats['lehrer']],
                    ['Räume', $stats['raeume']],
                    ['Fächer', $stats['faecher']],
                    ['Einträge', $stats['eintraege']],
                ]
            );

            if (!empty($stats['errors'])) {
                $this->newLine();
                $this->warn("⚠️  Errors during import:");
                foreach ($stats['errors'] as $error) {
                    $this->line("  - {$error}");
                }
            }

            return 0;
        } else {
            $this->error("❌ Import failed: " . $result['error']);
            return 1;
        }
    }
}


