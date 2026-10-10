<?php

namespace App\Console\Commands;

use App\Services\Updater\UpdateService;
use Illuminate\Console\Command;

class UpdaterCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'updater:check {--no-fetch : Remote nicht abfragen, nur mit lokalem Stand vergleichen}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prüft, ob im Git-Repository eine neue Version verfügbar ist';

    public function handle(UpdateService $updater): int
    {
        $result = $updater->check(! $this->option('no-fetch'));

        if ($result['error']) {
            $this->error('Prüfung fehlgeschlagen: '.$result['error']);

            return self::FAILURE;
        }
        if ($result['fetch_error']) {
            $this->warn('Remote konnte nicht abgefragt werden: '.$result['fetch_error']);
        }

        $this->line("Branch: {$result['remote']}/{$result['branch']}");
        if ($result['current_branch'] !== $result['branch']) {
            $this->warn("Ausgecheckt ist „{$result['current_branch']}“ – Updates sind nur auf „{$result['branch']}“ möglich (UPDATER_BRANCH).");
        }
        $this->line("Installiert: {$result['current']['short']} ({$result['current']['date']}) {$result['current']['subject']}");

        if ($result['behind'] === 0) {
            $this->info('Die Installation ist aktuell.');

            return self::SUCCESS;
        }

        $this->info("{$result['behind']} neue(r) Commit(s) verfügbar:");
        foreach ($result['commits'] as $commit) {
            $this->line("  {$commit['short']}  {$commit['subject']}");
        }
        if ($result['migrations']) {
            $this->line("Enthält {$result['migrations']} Datenbank-Migration(en).");
        }
        if ($result['local_changes']) {
            $this->warn('Lokal geänderte Dateien: '.implode(', ', $result['local_changes']));
        }

        return self::SUCCESS;
    }
}
