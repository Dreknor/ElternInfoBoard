<?php

namespace App\Services\Elternabfragen;

use App\Enums\GuardianRight;
use App\Enums\InquiryStatus;
use App\Model\InquiryAssignment;
use App\Model\User;
use App\Services\Family\FamilyResolver;
use Illuminate\Database\Eloquent\Builder;

/**
 * Elternseitige Berechtigungen – serverseitig bei jedem Zugriff geprüft (Konzept Kap. 7.1).
 *
 *  - sehen: jede gültige Beziehung zum Kind (Leseberechtigung), Antwortinhalte nur mit Sorgerecht
 *  - abgeben/korrigieren: nur Sorgeberechtigte (Berechtigung zur Abgabe von Erklärungen)
 *  - Beziehungen mit abgelaufenem `valid_until` zählen nicht (beendetes/befristetes Mandat)
 */
class InquiryAccess
{
    public function __construct(
        private readonly FamilyResolver $family,
        private readonly GuardianConfirmation $confirmation,
    ) {}

    /**
     * Aufträge, die der Nutzer sehen darf (gestartete bzw. abgeschlossene, nicht gelöschte Abfragen).
     */
    public function assignmentsQuery(User $user): Builder
    {
        return InquiryAssignment::query()
            ->whereIn('child_id', $this->family->childrenQuery($user)->select('children.id'))
            ->whereHas('inquiry', fn ($q) => $q
                ->whereIn('status', [InquiryStatus::Running->value, InquiryStatus::Closed->value])
                ->whereNull('purged_at'));
    }

    public function canView(User $user, InquiryAssignment $assignment): bool
    {
        $inquiry = $assignment->inquiry;

        return $inquiry !== null
            && $inquiry->isReleased()
            && $inquiry->purged_at === null
            && $assignment->child !== null
            && $this->family->hasAccessToChild($user, $assignment->child);
    }

    public function canAnswer(User $user, InquiryAssignment $assignment): bool
    {
        return $assignment->child !== null
            && $this->family->hasAccessToChild($user, $assignment->child, GuardianRight::Custody);
    }

    /**
     * Abgabestatus aus Sicht des Nutzers.
     *
     * `awaiting_confirmation`: die Antwort liegt vor, aber noch nicht alle Sorgeberechtigten haben bestätigt;
     * `can_confirm`: der Nutzer selbst muss (und darf) die gültige Antwort noch bestätigen.
     *
     * @return array{can_view_answers: bool, can_submit: bool, can_correct: bool, can_confirm: bool, awaiting_confirmation: bool, reason: ?string, deadline: \Carbon\CarbonInterface, correction_until: ?\Carbon\CarbonInterface}
     */
    public function state(User $user, InquiryAssignment $assignment): array
    {
        $inquiry = $assignment->inquiry;
        $custody = $this->canAnswer($user, $assignment);
        $deadline = $assignment->effectiveDeadline();
        $correctionUntil = $this->correctionUntil($assignment);
        $now = now();

        $reason = null;
        $canSubmit = false;
        $canCorrect = false;
        $awaiting = $assignment->isAnswered() && $this->confirmation->isPending($assignment);
        $canConfirm = $custody && $awaiting && $this->confirmation->canConfirm($user, $assignment);

        if (! $custody) {
            $reason = 'Antworten können nur sorgeberechtigte Personen abgeben.';
        } elseif (! $assignment->acceptsAnswers()) {
            $reason = $inquiry->closedEarly() ? 'Die Abfrage wurde vorzeitig beendet.' : 'Die Abfrage ist abgeschlossen.';
        } elseif (! $assignment->isAnswered()) {
            $canSubmit = $now->lessThanOrEqualTo($deadline);
            $reason = $canSubmit ? null : 'Die Rückgabefrist ist abgelaufen. Bitte wenden Sie sich an das Sekretariat.';
        } else {
            // Solange noch Bestätigungen fehlen, dürfen Sorgeberechtigte bis zur Frist korrigieren
            $canCorrect = ($correctionUntil !== null && $now->lessThanOrEqualTo($correctionUntil))
                || ($awaiting && $now->lessThanOrEqualTo($deadline));
            $reason = $canCorrect ? null : 'Eine Korrektur ist nur noch über das Sekretariat möglich.';
        }

        return [
            'can_view_answers' => $custody,
            'can_submit' => $canSubmit,
            'can_correct' => $canCorrect,
            'can_confirm' => $canConfirm,
            'awaiting_confirmation' => $awaiting,
            'reason' => $reason,
            'deadline' => $deadline,
            'correction_until' => $assignment->isAnswered() ? $correctionUntil : null,
        ];
    }

    /**
     * Eltern dürfen innerhalb des Zeitfensters ab Ersteinreichung selbst korrigieren,
     * danach nur, wenn das Sekretariat eine Korrektur freigegeben hat.
     */
    public function correctionUntil(InquiryAssignment $assignment): ?\Carbon\CarbonInterface
    {
        $window = $assignment->first_submitted_at && $assignment->inquiry->correction_window_days > 0
            ? $assignment->first_submitted_at->copy()->addDays($assignment->inquiry->correction_window_days)
            : null;

        $unlocked = $assignment->correction_until;

        if ($window && $unlocked) {
            return $window->greaterThan($unlocked) ? $window : $unlocked;
        }

        return $window ?? $unlocked;
    }
}
