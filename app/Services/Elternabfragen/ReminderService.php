<?php

namespace App\Services\Elternabfragen;

use App\Enums\InquiryAssignmentStatus;
use App\Enums\InquiryStatus;
use App\Jobs\DeliverInquiryNotifications;
use App\Model\Inquiry;
use App\Model\InquiryAssignment;
use App\Model\InquiryDelivery;
use App\Settings\ElternabfragenSetting;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Prozess D / Kap. 8: Erinnerungen mit begrenzter Häufigkeit.
 *
 *  - erste Erinnerung frühestens nach 48 Stunden (Einstellung)
 *  - weitere höchstens einmal innerhalb von 24 Stunden (Einstellung)
 *  - keine Erinnerung nach erfolgreicher Einreichung
 *  - Ausnahme von der Wartezeit bei technischem Problem oder gesetzter Nachfrist
 */
class ReminderService
{
    public function __construct(private readonly ElternabfragenSetting $settings) {}

    /**
     * Grund, warum (noch) nicht erinnert werden darf – null, wenn erlaubt.
     */
    public function blockedReason(InquiryAssignment $assignment, ?CarbonInterface $now = null): ?string
    {
        $now ??= now();
        $inquiry = $assignment->inquiry;

        if ($inquiry->status !== InquiryStatus::Running) {
            return 'Abfrage läuft nicht.';
        }
        if ($assignment->isAnswered()) {
            return 'Bereits eingereicht.';
        }
        if ($now->greaterThan($assignment->effectiveDeadline())) {
            return 'Frist abgelaufen – bitte zuerst eine Nachfrist setzen.';
        }
        if ($assignment->reminder_count >= $this->settings->max_reminders) {
            return 'Höchstzahl an Erinnerungen erreicht.';
        }

        $exception = $assignment->has_problem || $assignment->extended_until !== null;
        $since = $assignment->notified_at ?? $inquiry->released_at;
        if (! $exception && $since && $now->lessThan($since->copy()->addHours($this->settings->first_reminder_after_hours))) {
            return 'Erste Erinnerung frühestens '.$this->settings->first_reminder_after_hours.' Stunden nach der Benachrichtigung.';
        }
        if ($assignment->last_reminded_at && $now->lessThan($assignment->last_reminded_at->copy()->addHours($this->settings->reminder_interval_hours))) {
            return 'Letzte Erinnerung liegt weniger als '.$this->settings->reminder_interval_hours.' Stunden zurück.';
        }

        return null;
    }

    /**
     * Manuelle Erinnerung an alle offenen oder gezielt ausgewählte Fälle.
     *
     * @param  list<int>|null  $assignmentIds  null = alle offenen Fälle
     * @return array{sent: int, skipped: array<string, int>}
     */
    public function remindManually(Inquiry $inquiry, ?array $assignmentIds, int $actorId): array
    {
        $query = $inquiry->assignments()->with('inquiry')->whereNull('current_response_id');
        if ($assignmentIds !== null) {
            $query->whereIn('id', $assignmentIds);
        }

        return $this->dispatch($inquiry, $query->get(), InquiryDelivery::KIND_REMINDER_MANUAL, 'manual', $actorId);
    }

    /**
     * Automatische Erinnerungen: vor Fristende und nach Fristende (nur bei gesetzter Nachfrist).
     */
    public function runAutomatic(?CarbonInterface $now = null): int
    {
        $now ??= now();
        $sent = 0;
        $daysBefore = $this->settings->reminder_days_before_deadline;

        Inquiry::query()
            ->where('status', InquiryStatus::Running->value)
            ->where('auto_reminders', true)
            ->get()
            ->each(function (Inquiry $inquiry) use ($now, $daysBefore, &$sent) {
                $open = $inquiry->assignments()->with('inquiry')
                    ->whereNull('current_response_id')
                    ->whereIn('status', [InquiryAssignmentStatus::Open->value, InquiryAssignmentStatus::InProgress->value, InquiryAssignmentStatus::Problem->value])
                    ->get();

                // vor Fristende: einmalig im Zeitfenster X Tage vor der (ursprünglichen) Frist
                if ($daysBefore > 0 && $now->between($inquiry->deadline_at->copy()->subDays($daysBefore), $inquiry->deadline_at)) {
                    $due = $open->filter(fn (InquiryAssignment $a) => $a->extended_until === null
                        && ! $a->deliveries()->where('kind', InquiryDelivery::KIND_REMINDER_BEFORE)->exists());
                    $sent += $this->dispatch($inquiry, $due, InquiryDelivery::KIND_REMINDER_BEFORE, 'auto', null, $now)['sent'];
                }

                // nach Fristende: einmalig, wenn eine individuelle Nachfrist läuft
                $due = $open->filter(fn (InquiryAssignment $a) => $a->extended_until !== null
                    && $now->greaterThan($inquiry->deadline_at)
                    && $now->lessThanOrEqualTo($a->extended_until)
                    && ! $a->deliveries()->where('kind', InquiryDelivery::KIND_REMINDER_AFTER)
                        ->where('sent_at', '>=', $a->events()->where('action', 'assignment.extended')->max('created_at') ?? $inquiry->deadline_at)
                        ->exists());
                $sent += $this->dispatch($inquiry, $due, InquiryDelivery::KIND_REMINDER_AFTER, 'auto', null, $now)['sent'];
            });

        return $sent;
    }

    /**
     * @param  iterable<InquiryAssignment>  $assignments
     * @return array{sent: int, skipped: array<string, int>}
     */
    private function dispatch(Inquiry $inquiry, iterable $assignments, string $kind, string $trigger, ?int $actorId, ?CarbonInterface $now = null): array
    {
        $ids = [];
        $skipped = [];

        foreach ($assignments as $assignment) {
            $reason = $this->blockedReason($assignment, $now);
            if ($reason !== null) {
                $skipped[$reason] = ($skipped[$reason] ?? 0) + 1;

                continue;
            }
            $ids[] = $assignment->id;
        }

        foreach (array_chunk($ids, 200) as $chunk) {
            InquiryAssignment::whereIn('id', $chunk)->update([
                'reminder_count' => DB::raw('reminder_count + 1'),
                'last_reminded_at' => $now ?? now(),
            ]);
            DeliverInquiryNotifications::dispatch($inquiry->id, $chunk, $kind, $trigger, $actorId);
        }

        return ['sent' => count($ids), 'skipped' => $skipped];
    }
}
