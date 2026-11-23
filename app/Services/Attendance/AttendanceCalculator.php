<?php

namespace App\Services\Attendance;

use App\Model\Child;
use App\Model\ChildAttendanceEvent;
use App\Model\ChildCheckIn;
use App\Model\Krankmeldungen;
use App\Services\HolidayService;
use App\Settings\CareSetting;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Berechnet den tatsächlichen Betreuungsumfang aus dem Ereignisprotokoll.
 *
 * Regeln (siehe PR „Anwesenheitsverlauf“):
 * - Tagesumfang = Summe aller Anwesenheitsintervalle (Anmeldung → Abmeldung), minutengenau.
 * - Stornierte Ereignisse zählen nicht. Doppelte Anmeldungen ohne Abmeldung dazwischen werden ignoriert.
 * - Vergangener Tag ohne Abmeldung: Intervall bis zum Hortende (CareSetting::end_time), Tag „unvollständig“.
 *   Ohne eingestelltes Hortende zählt das offene Intervall 0 Minuten.
 * - Heute ohne Abmeldung: Intervall läuft bis jetzt.
 * - Abmeldung ohne vorherige Anmeldung macht den Tag ebenfalls „unvollständig“.
 */
class AttendanceCalculator
{
    private ?string $endTime;

    /** @var array<string, bool> */
    private array $holidayCache = [];

    public function __construct(?CareSetting $settings = null, private ?HolidayService $holidays = null)
    {
        $this->endTime = ($settings ?? new CareSetting)->end_time;
    }

    /**
     * @param  Collection<int, ChildAttendanceEvent>  $events  Ereignisse des Tages (auch stornierte)
     */
    public function day(
        Carbon $date,
        Collection $events,
        bool $sick = false,
        ?bool $shouldBe = null,
        ?CarbonInterface $now = null,
    ): AttendanceDay {
        $now = Carbon::instance($now ?? now());
        $date = $date->copy()->startOfDay();

        $active = $events
            ->filter(fn (ChildAttendanceEvent $e) => ! $e->isCancelled())
            ->sortBy([fn ($a, $b) => $a->occurred_at <=> $b->occurred_at, fn ($a, $b) => $a->id <=> $b->id])
            ->values();

        $intervals = [];
        $incomplete = false;
        $running = false;
        $start = null;

        foreach ($active as $event) {
            if ($event->isCheckIn()) {
                $start ??= $event->occurred_at->copy();

                continue;
            }

            if ($start === null) {
                $incomplete = true; // Abmeldung ohne Anmeldung

                continue;
            }

            $intervals[] = ['from' => $start, 'to' => $event->occurred_at->copy(), 'capped' => false, 'running' => false];
            $start = null;
        }

        if ($start !== null) {
            if ($date->isSameDay($now)) {
                $running = true;
                $intervals[] = ['from' => $start, 'to' => $now->copy()->max($start), 'capped' => false, 'running' => true];
            } else {
                $incomplete = true;
                if ($this->endTime) {
                    $end = Carbon::parse($date->toDateString().' '.$this->endTime, config('app.timezone'));
                    $intervals[] = ['from' => $start, 'to' => $end->max($start), 'capped' => true, 'running' => false];
                }
            }
        }

        $minutes = (int) array_sum(array_map(
            fn (array $i) => (int) floor($i['from']->diffInSeconds($i['to'], true) / 60),
            $intervals
        ));

        $status = match (true) {
            $active->isNotEmpty() => AttendanceDay::STATUS_PRESENT,
            $sick => AttendanceDay::STATUS_SICK,
            $shouldBe === false => AttendanceDay::STATUS_NOT_EXPECTED,
            default => AttendanceDay::STATUS_NO_DATA,
        };

        return new AttendanceDay(
            date: $date,
            minutes: $minutes,
            intervals: $intervals,
            incomplete: $incomplete,
            running: $running,
            automatic: $active->contains(fn ($e) => $e->isCheckIn() && $e->source === ChildAttendanceEvent::SOURCE_AUTO),
            holiday: $this->isHoliday($date),
            edited: $events->contains(fn ($e) => $e->isCancelled() || $e->isCorrected() || $e->source === ChildAttendanceEvent::SOURCE_MANUAL),
            status: $status,
            events: $events->sortBy('occurred_at')->values(),
        );
    }

    /**
     * Tage eines Zeitraums für mehrere Kinder (Mo–Fr, höchstens bis heute).
     *
     * @param  Collection<int, Child>  $children
     * @return Collection<int, Collection<int, AttendanceDay>> je child_id die Tage
     */
    public function period(Collection $children, Carbon $from, Carbon $to): Collection
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->startOfDay()->min(today());
        $childIds = $children->pluck('id');

        $events = ChildAttendanceEvent::query()
            ->whereIn('child_id', $childIds)
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString())
            ->with(['user:id,name', 'correctedBy:id,name', 'cancelledBy:id,name'])
            ->get()
            ->groupBy(fn ($e) => $e->child_id.'|'.$e->date->toDateString());

        $sick = Krankmeldungen::query()
            ->whereIn('child_id', $childIds)
            ->whereDate('start', '<=', $to->toDateString())
            ->whereDate('ende', '>=', $from->toDateString())
            ->get(['child_id', 'start', 'ende'])
            ->groupBy('child_id');

        $shouldBe = ChildCheckIn::query()
            ->whereIn('child_id', $childIds)
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString())
            ->get(['child_id', 'date', 'should_be'])
            ->keyBy(fn ($c) => $c->child_id.'|'.$c->date->toDateString());

        return $children->mapWithKeys(function (Child $child) use ($from, $to, $events, $sick, $shouldBe) {
            $days = collect();
            for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
                if ($date->isWeekend()) {
                    continue;
                }
                $key = $child->id.'|'.$date->toDateString();
                $isSick = ($sick->get($child->id) ?? collect())->contains(
                    fn ($k) => $k->start->copy()->startOfDay()->lte($date) && $k->ende->copy()->endOfDay()->gte($date)
                );

                $days->push($this->day($date->copy(), $events->get($key, collect()), $isSick, $shouldBe->get($key)?->should_be));
            }

            return [$child->id => $days];
        });
    }

    /**
     * @param  Collection<int, AttendanceDay>  $days
     * @return array<string, int>
     */
    public static function summarize(Collection $days): array
    {
        $present = $days->filter->isPresent();

        return [
            'days_present' => $present->count(),
            'minutes_total' => $days->sum('minutes'),
            'minutes_school' => $days->reject->holiday->sum('minutes'),
            'minutes_holiday' => $days->filter->holiday->sum('minutes'),
            'incomplete_days' => $days->filter->incomplete->count(),
            'automatic_days' => $days->filter->automatic->count(),
            'edited_days' => $days->filter->edited->count(),
            'sick_days' => $days->where('status', AttendanceDay::STATUS_SICK)->count(),
            'not_expected_days' => $days->where('status', AttendanceDay::STATUS_NOT_EXPECTED)->count(),
            'no_data_days' => $days->where('status', AttendanceDay::STATUS_NO_DATA)->count(),
        ];
    }

    private function isHoliday(Carbon $date): bool
    {
        $key = $date->toDateString();

        return $this->holidayCache[$key] ??= ($this->holidays ??= new HolidayService)->isHoliday($date);
    }
}
