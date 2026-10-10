<?php

namespace App\Console\Commands;

use App\Services\Updater\UpdateService;
use Illuminate\Console\Command;
use RuntimeException;

class UpdaterRun extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'updater:run
                            {--full : Alle Schritte ausführen, auch ohne neue Commits (z. B. nach einem fehlgeschlagenen Update)}
                            {--if-requested : Nur ausführen, wenn über die Oberfläche ein Update angefordert wurde (Scheduler)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aktualisiert die Installation aus dem Git-Repository (Online-Updater)';

    public function handle(UpdateService $updater): int
    {
        if ($this->option('if-requested') && ! $updater->isRequested()) {
            return self::SUCCESS;
        }

        try {
            $success = $updater->run(
                full: (bool) $this->option('full'),
                output: fn (string $text) => $this->output->write($text),
            );
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return $success ? self::SUCCESS : self::FAILURE;
    }
}
