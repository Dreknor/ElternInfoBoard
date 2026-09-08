<?php

namespace App\Services;

use App\Model\Child;
use App\Model\ChildCheckIn;
use App\Model\LatePickup;
use App\Model\Schickzeiten;
use App\Model\User;
use App\Settings\CareSetting;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Erkennt verspätete Abholungen anhand der hinterlegten Schickzeiten eines
 * Kindes und stellt Auswertungen (Wochenansicht / Einzelkind) sowie die
 * Bestätigung bzw. Verwerfung durch berechtigte Nutzer bereit.
 */
class LatePickupService
{
    /**
     * Prüft, ob eine Abholung (checked_out) verspätet erfolgte und legt in
     * diesem Fall einen (offenen) LatePickup-Datensatz an bzw. aktualisiert
     * einen bestehenden, noch nicht überprüften Datensatz für den Tag.
     */
    public function detectAndRecord(Child $child, ?ChildCheckIn $checkIn, Carbon $pickedUpAt): ?LatePickup
    {
        $date = $pickedUpAt->copy()->startOfDay();

        $deadline = $this->resolveDeadline($child, $date);

        if ($deadline === null || $pickedUpAt->lessThanOrEqualTo($deadline)) {
            return null;
        }

        // Nur erfassen, wenn die Abmeldung auch nach dem in den Settings
        // hinterlegten Ende der Betreuungszeit erfolgte.
        $careEndTime = $this->resolveCareEndTime($date);

        if ($careEndTime === null || $pickedUpAt->lessThanOrEqualTo($careEndTime)) {
            return null;
        }

        $delayMinutes = $deadline->diffInMinutes($pickedUpAt);

        $existing = LatePickup::query()
            ->where('child_id', $child->id)
            ->whereDate('date', $date)
            ->first();

        // Bereits geprüfte (bestätigte/verworfene) Einträge werden nicht überschrieben.
        if ($existing && ! $existing->isOffen()) {
            return $existing;
        }

        $attributes = [
            'child_check_in_id' => $checkIn?->id,
            'date' => $date->toDateString(),
            'weekday' => $date->dayOfWeek,
            'expected_time' => $deadline->format('H:i:s'),
            'picked_up_at' => $pickedUpAt,
            'delay_minutes' => $delayMinutes,
            'status' => LatePickup::STATUS_OFFEN,
        ];

        if ($existing) {
            $existing->update($attributes);

            return $existing->refresh();
        }

        return LatePickup::create(array_merge(['child_id' => $child->id], $attributes));
    }

    /**
     * Ermittelt die spätestmögliche Abholzeit (Deadline) für ein Kind an
     * einem bestimmten Tag anhand der Schickzeiten. Gibt null zurück, wenn
     * keine Deadline definiert ist (z. B. offene "ab"-Zeit ohne "spätestens").
     */
    public function resolveDeadline(Child $child, Carbon $date): ?Carbon
    {
        $schickzeiten = Schickzeiten::query()
            ->where('child_id', $child->id)
            ->where(function ($query) use ($date) {
                $query->whereDate('specific_date', $date->toDateString())
                    ->orWhere(function ($q) use ($date) {
                        $q->whereNull('specific_date')
                            ->where('weekday', $date->dayOfWeek);
                    });
            })
            ->get();

        $deadlines = $schickzeiten
            ->map(function (Schickzeiten $schickzeit) use ($date) {
                if ($schickzeit->type === 'genau' && $schickzeit->time) {
                    return $date->copy()->setTimeFrom($schickzeit->time);
                }

                if ($schickzeit->type === 'ab' && $schickzeit->time_spaet) {
                    return $date->copy()->setTimeFrom($schickzeit->time_spaet);
                }

                return null;
            })
            ->filter();

        if ($deadlines->isEmpty()) {
            return null;
        }

        return $deadlines->max();
    }

    /**
     * Ermittelt das in den Settings hinterlegte Ende der Betreuungszeit für
     * einen bestimmten Tag. Gibt null zurück, wenn keine Zeit hinterlegt ist.
     */
    private function resolveCareEndTime(Carbon $date): ?Carbon
    {
        $careSettings = new CareSetting;

        if (! $careSettings->end_time) {
            return null;
        }

        return $date->copy()->setTimeFrom(Carbon::parse($careSettings->end_time));
    }

    /**
     * Liefert die verspäteten Abholungen für eine Woche, optional gefiltert
     * nach Gruppe/Klasse, gruppiert nach Datum.
     */
    public function weeklyOverview(Carbon $weekStart, Carbon $weekEnd, ?int $groupId = null, ?int $classId = null): Collection
    {
        return LatePickup::query()
            ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->whereHas('child', function ($query) use ($groupId, $classId) {
                if ($groupId) {
                    $query->where('group_id', $groupId);
                }
                if ($classId) {
                    $query->where('class_id', $classId);
                }
            })
            ->with(['child.group:id,name', 'child.class:id,name', 'reviewer:id,name'])
            ->orderBy('date')
            ->orderBy('picked_up_at')
            ->get()
            ->groupBy(fn (LatePickup $latePickup) => $latePickup->date->toDateString());
    }

    /**
     * Liefert die verspäteten Abholungen eines einzelnen Kindes.
     */
    public function forChild(Child $child, int $days = 90): Collection
    {
        return LatePickup::query()
            ->where('child_id', $child->id)
            ->where('date', '>=', now()->subDays($days)->toDateString())
            ->with(['reviewer:id,name'])
            ->orderByDesc('date')
            ->get();
    }

    public function confirm(LatePickup $latePickup, User $user, ?string $comment = null): LatePickup
    {
        $latePickup->update([
            'status' => LatePickup::STATUS_BESTAETIGT,
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'review_comment' => $comment,
        ]);

        return $latePickup;
    }

    public function reject(LatePickup $latePickup, User $user, ?string $comment = null): LatePickup
    {
        $latePickup->update([
            'status' => LatePickup::STATUS_VERWORFEN,
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'review_comment' => $comment,
        ]);

        return $latePickup;
    }
}
