<?php

namespace App\Services\Attendance;

use App\Model\Child;
use App\Model\ChildAttendanceEvent;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Schreibt das Ereignisprotokoll der An- und Abmeldungen.
 *
 * Erfasst wird nur, wenn sich der Status tatsächlich ändert – das prüfen die Aufrufer
 * (CareController). Korrekturen und Stornos verändern bestehende Einträge nur additiv:
 * der ursprüngliche Zeitpunkt bleibt erhalten, jede Änderung braucht einen Kommentar.
 */
class AttendanceLog
{
    public function record(
        Child|int $child,
        string $type,
        string $source,
        ?CarbonInterface $occurredAt = null,
        ?int $userId = null,
        ?string $note = null,
    ): ChildAttendanceEvent {
        $occurredAt = Carbon::instance($occurredAt ?? now())->timezone(config('app.timezone'));

        return ChildAttendanceEvent::create([
            'child_id' => $child instanceof Child ? $child->id : $child,
            'date' => $occurredAt->toDateString(),
            'type' => $type,
            'occurred_at' => $occurredAt,
            'source' => $source,
            'user_id' => $userId,
            'note' => $note,
        ]);
    }

    /**
     * Nachtrag durch Personal (z. B. vergessene Abmeldung).
     */
    public function addManual(Child $child, string $type, CarbonInterface $occurredAt, int $userId, string $note): ChildAttendanceEvent
    {
        $this->assertNotInFuture($occurredAt);

        return $this->record($child, $type, ChildAttendanceEvent::SOURCE_MANUAL, $occurredAt, $userId, $note);
    }

    /**
     * Uhrzeit eines Ereignisses korrigieren. Der Tag bleibt unverändert, der erste
     * ursprüngliche Zeitpunkt wird festgehalten (weitere Änderungen stehen im Audit-Log).
     */
    public function correct(ChildAttendanceEvent $event, string $time, int $userId, string $note): ChildAttendanceEvent
    {
        if ($event->isCancelled()) {
            throw ValidationException::withMessages(['event' => 'Stornierte Einträge können nicht korrigiert werden.']);
        }

        $newTime = Carbon::parse($event->date->toDateString().' '.$time, config('app.timezone'));
        $this->assertNotInFuture($newTime);

        $event->update([
            'original_occurred_at' => $event->original_occurred_at ?? $event->occurred_at,
            'occurred_at' => $newTime,
            'corrected_by' => $userId,
            'corrected_at' => now(),
            'correction_note' => $note,
        ]);

        return $event;
    }

    /**
     * Ereignis stornieren (z. B. Fehlklick). Es bleibt sichtbar, zählt aber nicht mehr.
     */
    public function cancel(ChildAttendanceEvent $event, int $userId, string $note): ChildAttendanceEvent
    {
        if ($event->isCancelled()) {
            return $event;
        }

        $event->update([
            'cancelled_by' => $userId,
            'cancelled_at' => now(),
            'cancel_note' => $note,
        ]);

        return $event;
    }

    private function assertNotInFuture(CarbonInterface $time): void
    {
        if ($time->greaterThan(now())) {
            throw ValidationException::withMessages(['time' => 'Der Zeitpunkt darf nicht in der Zukunft liegen.']);
        }
    }
}
