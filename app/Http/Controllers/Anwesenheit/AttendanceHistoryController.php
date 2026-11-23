<?php

namespace App\Http\Controllers\Anwesenheit;

use App\Exports\AttendanceReportExport;
use App\Http\Controllers\Controller;
use App\Model\Child;
use App\Model\ChildAttendanceEvent;
use App\Model\ChildCheckIn;
use App\Model\Groups;
use App\Model\Krankmeldungen;
use App\Services\Attendance\AttendanceCalculator;
use App\Services\Attendance\AttendanceDay;
use App\Services\Attendance\AttendanceLog;
use App\Services\Security\SecurityLog;
use App\Settings\CareSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Verlauf der An-/Abmeldungen und Auswertung des tatsächlichen Betreuungsumfangs.
 * Zugriff: Recht „edit schickzeiten“ (siehe routes/web.php).
 */
class AttendanceHistoryController extends Controller
{
    public function __construct(
        private readonly AttendanceLog $log,
        private readonly AttendanceCalculator $calculator,
    ) {}

    /**
     * Tagesverlauf eines Kindes (Tab „Verlauf“ in der Anwesenheitsliste).
     */
    public function day(Request $request, Child $child): JsonResponse
    {
        $validated = $request->validate(['date' => 'nullable|date|before_or_equal:today']);
        $date = Carbon::parse($validated['date'] ?? today())->startOfDay();

        $events = $child->attendanceEvents()
            ->whereDate('date', $date->toDateString())
            ->with(['user:id,name', 'correctedBy:id,name', 'cancelledBy:id,name'])
            ->get();

        $sick = Krankmeldungen::query()
            ->where('child_id', $child->id)
            ->whereDate('start', '<=', $date->toDateString())
            ->whereDate('ende', '>=', $date->toDateString())
            ->exists();

        $shouldBe = ChildCheckIn::query()
            ->where('child_id', $child->id)
            ->whereDate('date', $date->toDateString())
            ->value('should_be');

        return response()->json([
            'success' => true,
            'data' => $this->dayPayload($this->calculator->day($date, $events, $sick, $shouldBe === null ? null : (bool) $shouldBe)),
        ]);
    }

    /**
     * Nachtrag einer An- oder Abmeldung.
     */
    public function store(Request $request, Child $child): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:'.ChildAttendanceEvent::TYPE_CHECK_IN.','.ChildAttendanceEvent::TYPE_CHECK_OUT,
            'date' => 'required|date|before_or_equal:today',
            'time' => 'required|date_format:H:i',
            'note' => 'required|string|min:3|max:500',
        ]);

        $event = $this->log->addManual(
            $child,
            $validated['type'],
            Carbon::parse($validated['date'].' '.$validated['time'], config('app.timezone')),
            auth()->id(),
            $validated['note'],
        );

        return response()->json(['success' => true, 'data' => $this->eventPayload($event->fresh(['user']))]);
    }

    /**
     * Uhrzeit eines Ereignisses korrigieren.
     */
    public function correct(Request $request, ChildAttendanceEvent $attendanceEvent): JsonResponse
    {
        $validated = $request->validate([
            'time' => 'required|date_format:H:i',
            'note' => 'required|string|min:3|max:500',
        ]);

        $this->log->correct($attendanceEvent, $validated['time'], auth()->id(), $validated['note']);

        return response()->json(['success' => true, 'data' => $this->eventPayload($attendanceEvent->fresh(['user', 'correctedBy']))]);
    }

    /**
     * Ereignis stornieren (z. B. Fehlklick).
     */
    public function cancel(Request $request, ChildAttendanceEvent $attendanceEvent): JsonResponse
    {
        $validated = $request->validate(['note' => 'required|string|min:3|max:500']);

        $this->log->cancel($attendanceEvent, auth()->id(), $validated['note']);

        return response()->json(['success' => true, 'data' => $this->eventPayload($attendanceEvent->fresh(['user', 'cancelledBy']))]);
    }

    /**
     * Auswertung des Betreuungsumfangs für einen Zeitraum.
     */
    public function report(Request $request)
    {
        [$filters, $children, $groups, $classes] = $this->reportInput($request);

        $days = $this->calculator->period($children, $filters['from'], $filters['to']);

        $rows = $children->map(fn (Child $child) => [
            'child' => $child,
            'days' => $days->get($child->id, collect()),
            'summary' => AttendanceCalculator::summarize($days->get($child->id, collect())),
        ]);

        return view('anwesenheit.auswertung', [
            'filters' => $filters,
            'rows' => $rows,
            'totals' => AttendanceCalculator::summarize($days->flatten(1)),
            'groups' => $groups,
            'classes' => $classes,
            'allChildren' => $this->careChildren()->orderBy('last_name')->orderBy('first_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }

    public function export(Request $request)
    {
        [$filters, $children] = $this->reportInput($request);

        $days = $this->calculator->period($children, $filters['from'], $filters['to']);

        SecurityLog::record('export.download', null, ['export' => 'betreuungsumfang', 'format' => 'xlsx', 'rows' => $children->count()]);

        $name = 'Betreuungsumfang_'.$filters['from']->format('Y-m-d').'_'.$filters['to']->format('Y-m-d').'.xlsx';

        return Excel::download(new AttendanceReportExport($children, $days), $name);
    }

    /**
     * @return array{0: array, 1: Collection<int, Child>, 2: Collection, 3: Collection}
     */
    private function reportInput(Request $request): array
    {
        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'group_id' => 'nullable|integer',
            'class_id' => 'nullable|integer',
            'child_id' => 'nullable|integer',
        ]);

        $from = Carbon::parse($validated['from'] ?? today()->startOfMonth())->startOfDay();
        $to = Carbon::parse($validated['to'] ?? today())->startOfDay();

        // Zeiträume über ein Jahr würden die Seite sehr langsam machen
        if ($from->diffInDays($to) > 366) {
            $to = $from->copy()->addDays(366);
        }

        $settings = new CareSetting;
        $groups = Groups::query()->whereIn('id', (array) ($settings->groups_list ?? []))->orderBy('name')->get(['id', 'name']);
        $classes = Groups::query()->whereIn('id', (array) ($settings->class_list ?? []))->orderBy('name')->get(['id', 'name']);

        $children = $this->careChildren()
            ->when($validated['group_id'] ?? null, fn ($q, $id) => $q->where('group_id', $id))
            ->when($validated['class_id'] ?? null, fn ($q, $id) => $q->where('class_id', $id))
            ->when($validated['child_id'] ?? null, fn ($q, $id) => $q->where('id', $id))
            ->with(['group:id,name', 'class:id,name'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $filters = [
            'from' => $from,
            'to' => $to,
            'group_id' => $validated['group_id'] ?? null,
            'class_id' => $validated['class_id'] ?? null,
            'child_id' => $validated['child_id'] ?? null,
        ];

        return [$filters, $children, $groups, $classes];
    }

    /** Kinder der in den Betreuungseinstellungen konfigurierten Gruppen/Klassen (wie Anwesenheitsliste). */
    private function careChildren()
    {
        $settings = new CareSetting;
        $groupIds = array_values((array) ($settings->groups_list ?? []));
        $classIds = array_values((array) ($settings->class_list ?? []));

        return Child::query()
            ->when(! empty($groupIds), fn ($q) => $q->whereIn('group_id', $groupIds))
            ->when(! empty($classIds), fn ($q) => $q->whereIn('class_id', $classIds));
    }

    private function dayPayload(AttendanceDay $day): array
    {
        return [
            'date' => $day->date->toDateString(),
            'minutes' => $day->minutes,
            'duration' => $day->formattedDuration(),
            'incomplete' => $day->incomplete,
            'running' => $day->running,
            'automatic' => $day->automatic,
            'holiday' => $day->holiday,
            'status' => $day->status,
            'status_label' => $day->statusLabel(),
            'intervals' => array_map(fn ($i) => [
                'from' => $i['from']->format('H:i'),
                'to' => $i['to']->format('H:i'),
                'capped' => $i['capped'],
                'running' => $i['running'],
            ], $day->intervals),
            'events' => $day->events->map(fn ($e) => $this->eventPayload($e))->values(),
        ];
    }

    private function eventPayload(ChildAttendanceEvent $event): array
    {
        return [
            'id' => $event->id,
            'type' => $event->type,
            'type_label' => $event->typeLabel(),
            'time' => $event->occurred_at->format('H:i'),
            'source' => $event->source,
            'source_label' => $event->sourceLabel(),
            'user' => $event->user?->name,
            'note' => $event->note,
            'corrected' => $event->isCorrected() ? [
                'original_time' => $event->original_occurred_at->format('H:i'),
                'by' => $event->correctedBy?->name,
                'at' => $event->corrected_at?->format('d.m.Y H:i'),
                'note' => $event->correction_note,
            ] : null,
            'cancelled' => $event->isCancelled() ? [
                'by' => $event->cancelledBy?->name,
                'at' => $event->cancelled_at->format('d.m.Y H:i'),
                'note' => $event->cancel_note,
            ] : null,
        ];
    }
}
