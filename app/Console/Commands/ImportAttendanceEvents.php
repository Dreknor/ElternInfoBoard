<?php

namespace App\Console\Commands;

use App\Model\ChildAttendanceEvent;
use App\Model\ChildCheckIn;
use App\Services\HolidayService;
use App\Settings\CareSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Übernimmt die vorhandenen Tageszeilen (child_check_ins) einmalig in das Ereignisprotokoll.
 *
 * Je Tag entsteht höchstens ein Intervall aus checked_in_at/checked_out_at, markiert als
 * „importiert“. Frühere Mehrfach-An-/Abmeldungen lassen sich nicht wiederherstellen.
 * Tage, für die bereits Ereignisse existieren, werden übersprungen – der Befehl kann
 * daher gefahrlos mehrfach laufen.
 */
class ImportAttendanceEvents extends Command
{
    protected $signature = 'attendance:import-checkins {--dry-run : Nur zählen, nichts schreiben}';

    protected $description = 'Übernimmt bestehende Check-Ins einmalig in den An-/Abmeldeverlauf';

    public function handle(): int
    {
        $settings = new CareSetting;
        $holidays = new HolidayService($settings->bundesland);
        $dryRun = (bool) $this->option('dry-run');
        $created = 0;
        $days = 0;

        ChildCheckIn::query()
            ->where(fn ($q) => $q->where('checked_in', true)->orWhere('checked_out', true))
            ->whereDate('date', '<=', today())
            ->orderBy('id')
            ->chunkById(500, function ($checkIns) use (&$created, &$days, $dryRun, $settings, $holidays) {
                $existing = ChildAttendanceEvent::query()
                    ->whereIn('child_id', $checkIns->pluck('child_id')->unique())
                    ->whereDate('date', '>=', $checkIns->min(fn ($c) => $c->date->toDateString()))
                    ->whereDate('date', '<=', $checkIns->max(fn ($c) => $c->date->toDateString()))
                    ->get(['child_id', 'date'])
                    ->map(fn ($e) => $e->child_id.'|'.$e->date->toDateString())
                    ->flip();

                foreach ($checkIns as $checkIn) {
                    $date = $checkIn->date->toDateString();
                    if ($existing->has($checkIn->child_id.'|'.$date)) {
                        continue;
                    }

                    $rows = [];
                    $checkedInAt = $checkIn->checked_in_at
                        ?? $this->fallbackCheckInTime($checkIn, $settings, $holidays);

                    if ($checkedInAt) {
                        $rows[] = $this->row($checkIn->child_id, $date, ChildAttendanceEvent::TYPE_CHECK_IN, $checkedInAt);
                    }
                    if ($checkIn->checked_out && $checkIn->checked_out_at) {
                        $rows[] = $this->row($checkIn->child_id, $date, ChildAttendanceEvent::TYPE_CHECK_OUT, $checkIn->checked_out_at);
                    }

                    if ($rows === []) {
                        continue;
                    }

                    $days++;
                    $created += count($rows);
                    if (! $dryRun) {
                        ChildAttendanceEvent::query()->insert($rows);
                    }
                }
            });

        $this->info(($dryRun ? '[Dry-Run] ' : '')."{$created} Ereignisse für {$days} Tage übernommen.");

        return self::SUCCESS;
    }

    /**
     * Auto-Check-Ins wurden bisher ohne checked_in_at angelegt: dann den Zeitpunkt der Zeile,
     * ersatzweise die eingestellte Auto-Check-In-Uhrzeit verwenden.
     */
    private function fallbackCheckInTime(ChildCheckIn $checkIn, CareSetting $settings, HolidayService $holidays): ?Carbon
    {
        if (! $checkIn->checked_in && ! $checkIn->checked_out) {
            return null;
        }

        if ($checkIn->created_at && $checkIn->created_at->isSameDay($checkIn->date)) {
            return $checkIn->created_at;
        }

        $time = $holidays->isHoliday($checkIn->date) ? $settings->auto_checkin_time_ferien : $settings->auto_checkin_time_schulzeit;

        return $time ? Carbon::parse($checkIn->date->toDateString().' '.$time, config('app.timezone')) : null;
    }

    private function row(int $childId, string $date, string $type, Carbon $at): array
    {
        return [
            'child_id' => $childId,
            'date' => $date,
            'type' => $type,
            'occurred_at' => $at->copy()->timezone(config('app.timezone'))->toDateTimeString(),
            'source' => ChildAttendanceEvent::SOURCE_IMPORT,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
