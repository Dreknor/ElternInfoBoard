<?php

namespace App\Console\Commands;

use App\Model\ChildAttendanceEvent;
use App\Settings\CareSetting;
use Illuminate\Console\Command;

/**
 * Löscht An-/Abmeldeereignisse nach Ablauf der eingestellten Aufbewahrungsfrist
 * (Einstellungen → Betreuung, Standard 6 Monate).
 */
class CleanupAttendanceEvents extends Command
{
    protected $signature = 'attendance:cleanup {--dry-run : Nur zählen, nichts löschen}';

    protected $description = 'Löscht den An-/Abmeldeverlauf nach Ablauf der Aufbewahrungsfrist';

    public function handle(): int
    {
        $months = max(1, (int) (new CareSetting)->attendance_history_months);
        $before = today()->subMonths($months)->toDateString();

        $query = ChildAttendanceEvent::query()->whereDate('date', '<', $before);

        if ($this->option('dry-run')) {
            $this->info("[Dry-Run] {$query->count()} Ereignisse vor dem {$before} würden gelöscht.");

            return self::SUCCESS;
        }

        $deleted = 0;
        do {
            $batch = (clone $query)->limit(1000)->delete();
            $deleted += $batch;
        } while ($batch > 0);

        $this->info("{$deleted} Ereignisse vor dem {$before} gelöscht (Aufbewahrung: {$months} Monate).");

        return self::SUCCESS;
    }
}
