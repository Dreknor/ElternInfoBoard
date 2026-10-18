<?php

namespace App\Services\Elternabfragen;

use App\Enums\InquiryReviewStatus;
use App\Enums\InquiryStatus;
use App\Model\InquiryAssignment;
use App\Model\InquiryEvent;
use App\Model\InquiryResponse;
use App\Model\User;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

/**
 * Prozess D/E: Einzelfälle bearbeiten – Nachfrist, technisches Problem, Bearbeitungs-
 * hinweis, Korrekturfreigabe und Prüfung von Antworten. Alles wird dokumentiert.
 */
class AssignmentAdminService
{
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
