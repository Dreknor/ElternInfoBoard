<?php

namespace App\Services\Elternabfragen;

use App\Enums\InquiryReviewStatus;
use App\Enums\InquiryStatus;
use App\Enums\InquiryAssignmentStatus;
use App\Model\InquiryAssignment;
use App\Model\InquiryDraft;
use App\Model\InquiryEvent;
use App\Model\InquiryRecipient;
use App\Model\InquiryResponse;
use App\Model\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Prozess D/E: Einzelfälle bearbeiten – Nachfrist, technisches Problem, Bearbeitungs-
 * hinweis, Korrekturfreigabe, Neustart und Prüfung von Antworten. Alles wird dokumentiert.
 */
class AssignmentAdminService
{
    public function __construct(
        private readonly TargetResolver $targets,
        private readonly DeliveryService $delivery,
    ) {}

    public function extend(InquiryAssignment $assignment, CarbonInterface $until, string $reason): void
    {
        $assignment->inquiry->assertStatus(InquiryStatus::Running);
        if ($until->lessThanOrEqualTo(now())) {
            throw ValidationException::withMessages(['extended_until' => 'Die Nachfrist muss in der Zukunft liegen.']);
        }

        $assignment->update(['extended_until' => $until]);
        $this->log($assignment, 'assignment.extended', $reason, ['until' => $until->toIso8601String()]);
    }

    public function markProblem(InquiryAssignment $assignment, string $note): void
    {
        $assignment->has_problem = true;
        $assignment->problem_note = $note;
        $assignment->status = $assignment->recomputeStatus();
        $assignment->save();
        $this->log($assignment, 'assignment.problem', $note);
    }

    public function resolveProblem(InquiryAssignment $assignment, ?string $note): void
    {
        $assignment->has_problem = false;
        $assignment->status = $assignment->recomputeStatus();
        $assignment->save();
        $this->log($assignment, 'assignment.problem_resolved', $note);
    }

    public function addNote(InquiryAssignment $assignment, string $note): void
    {
        $assignment->update(['note' => $note]);
        $this->log($assignment, 'assignment.note', $note);
    }

    /**
     * Korrektur nach Ablauf des Eltern-Zeitfensters anstoßen.
     */
    public function unlockCorrection(InquiryAssignment $assignment, CarbonInterface $until, string $reason): void
    {
        $assignment->inquiry->assertStatus(InquiryStatus::Running);
        if (! $assignment->isAnswered()) {
            throw ValidationException::withMessages(['correction_until' => 'Es liegt noch keine Antwort vor, die korrigiert werden könnte.']);
        }
        if ($until->lessThanOrEqualTo(now())) {
            throw ValidationException::withMessages(['correction_until' => 'Das Datum muss in der Zukunft liegen.']);
        }

        $assignment->update(['correction_until' => $until]);
        $this->log($assignment, 'assignment.correction_unlocked', $reason, ['until' => $until->toIso8601String()]);
    }

    /**
     * Abfrage für eine Familie neu starten – auch nach Abschluss der Abfrage. Die bisherige
     * Antwort bleibt als Version erhalten, gilt aber nicht mehr. Die Sorgeberechtigten werden
     * nach aktuellem Stand neu ermittelt und benachrichtigt; Abgabe bis zur individuellen Frist.
     */
    public function restart(InquiryAssignment $assignment, CarbonInterface $until, string $reason): void
    {
        $inquiry = $assignment->inquiry;
        $inquiry->assertStatus(InquiryStatus::Running, InquiryStatus::Closed);
        if ($inquiry->purged_at !== null) {
            abort(409, 'Die Antworten dieser Abfrage wurden bereits gelöscht.');
        }
        if ($until->lessThanOrEqualTo(now())) {
            throw ValidationException::withMessages(['restart_until' => 'Die neue Frist muss in der Zukunft liegen.']);
        }

        $previousVersion = $assignment->currentResponse?->version;

        DB::transaction(function () use ($assignment, $until) {
            InquiryResponse::where('assignment_id', $assignment->id)->where('is_current', true)->update(['is_current' => false]);
            InquiryDraft::where('assignment_id', $assignment->id)->delete();

            $assignment->forceFill([
                'status' => InquiryAssignmentStatus::Open,
                'current_response_id' => null,
                'first_submitted_at' => null,
                'last_submitted_at' => null,
                'extended_until' => $until,
                'correction_until' => null,
                'reopened_at' => now(),
                'has_problem' => false,
                'problem_note' => null,
                'reminder_count' => 0,
                'last_reminded_at' => null,
            ])->save();

            // Sorgerecht kann sich seit dem Start geändert haben
            $guardians = $assignment->child ? $this->targets->recipients($assignment->child)->pluck('id') : collect();
            $assignment->recipients()->whereNotIn('user_id', $guardians)->delete();
            $existing = $assignment->recipients()->pluck('user_id');
            $guardians->diff($existing)->each(fn (int $userId) => InquiryRecipient::create(['assignment_id' => $assignment->id, 'user_id' => $userId]));
        });

        $this->log($assignment, 'assignment.restarted', $reason, array_filter([
            'until' => $until->toIso8601String(),
            'previous_version' => $previousVersion,
        ]));

        $assignment->refresh();
        $this->delivery->notifyRelease($assignment);
    }

    public function review(InquiryResponse $response, User $user, InquiryReviewStatus $status, ?string $note): void
    {
        if ($status === InquiryReviewStatus::Clarification && blank($note)) {
            throw ValidationException::withMessages(['review_note' => 'Bitte den Klärungsbedarf beschreiben.']);
        }

        $response->update([
            'review_status' => $status,
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'review_note' => $note,
        ]);
        $this->log($response->assignment, 'response.reviewed', $note, ['version' => $response->version, 'status' => $status->value]);
    }

    private function log(InquiryAssignment $assignment, string $action, ?string $note, array $data = []): void
    {
        InquiryEvent::log($action, ['inquiry_id' => $assignment->inquiry_id, 'assignment_id' => $assignment->id], $note, $data);
    }
}
