<?php

namespace App\Http\Controllers\Elternabfragen;

use App\Enums\InquiryReviewStatus;
use App\Http\Controllers\Controller;
use App\Model\Inquiry;
use App\Model\InquiryAssignment;
use App\Model\InquiryResponse;
use App\Services\Elternabfragen\AssignmentAdminService;
use App\Services\Elternabfragen\FieldSchema;
use App\Services\Elternabfragen\GuardianConfirmation;
use App\Services\Elternabfragen\ReminderService;
use App\Services\Elternabfragen\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Einzelfälle im Rücklauf: Erinnern, Nachfrist, Problem, Hinweis, Korrekturfreigabe,
 * Antwort einsehen und prüfen (Einsicht nur mit „view elternabfragen answers“).
 */
class AssignmentController extends Controller
{
    public function __construct(private readonly AssignmentAdminService $cases) {}

    public function remind(Request $request, Inquiry $inquiry, ReminderService $reminders): RedirectResponse
    {
        $request->validate(['assignment_ids' => 'nullable|array', 'assignment_ids.*' => 'integer']);
        $ids = $request->input('scope') === 'selected' ? array_map('intval', (array) $request->input('assignment_ids', [])) : null;
        if ($ids === []) {
            return back()->with(['type' => 'warning', 'Meldung' => 'Bitte mindestens einen Fall auswählen.']);
        }

        $result = $reminders->remindManually($inquiry, $ids, $request->user()->id);

        $message = $result['sent'].' Erinnerung(en) versendet.';
        foreach ($result['skipped'] as $reason => $count) {
            $message .= ' '.$count.'× übersprungen: '.$reason;
        }

        return back()->with(['type' => $result['sent'] > 0 ? 'success' : 'warning', 'Meldung' => $message]);
    }

    public function show(Request $request, InquiryAssignment $assignment): View
    {
        $user = $request->user();
        abort_unless($user->canAny(['manage elternabfragen', 'approve elternabfragen', 'view elternabfragen answers', 'export elternabfragen']), 403);

        $assignment->load([
            'inquiry.templateVersion', 'child', 'group', 'recipients.user:id,name,email',
            'responses.user:id,name', 'responses.reviewer:id,name', 'deliveries.user:id,name', 'events.user:id,name',
            'drafts.user:id,name', 'responses.confirmations.user:id,name', 'filer:id,name',
        ]);

        return view('elternabfragen.admin.assignment', [
            'assignment' => $assignment,
            'inquiry' => $assignment->inquiry,
            'canSeeAnswers' => $user->can('view elternabfragen answers'),
            'pendingConfirmations' => app(GuardianConfirmation::class)->pendingUsers($assignment),
            'pendingApprovals' => AdminController::pendingApprovalCount(),
        ]);
    }

    public function extend(Request $request, InquiryAssignment $assignment): RedirectResponse
    {
        $data = $request->validate(['extended_until' => 'required|date', 'reason' => 'required|string|max:1000']);
        $this->cases->extend($assignment, Carbon::parse($data['extended_until']), $data['reason']);

        return back()->with(['type' => 'success', 'Meldung' => 'Nachfrist gesetzt und dokumentiert.']);
    }

    public function problem(Request $request, InquiryAssignment $assignment): RedirectResponse
    {
        $data = $request->validate(['note' => 'required|string|max:1000']);
        $this->cases->markProblem($assignment, $data['note']);

        return back()->with(['type' => 'success', 'Meldung' => 'Als technisches Problem gekennzeichnet.']);
    }

    public function resolve(Request $request, InquiryAssignment $assignment): RedirectResponse
    {
        $this->cases->resolveProblem($assignment, $request->input('note'));

        return back()->with(['type' => 'success', 'Meldung' => 'Problem als gelöst markiert.']);
    }

    public function note(Request $request, InquiryAssignment $assignment): RedirectResponse
    {
        $data = $request->validate(['note' => 'required|string|max:2000']);
        $this->cases->addNote($assignment, $data['note']);

        return back()->with(['type' => 'success', 'Meldung' => 'Bearbeitungshinweis gespeichert.']);
    }

    public function unlockCorrection(Request $request, InquiryAssignment $assignment): RedirectResponse
    {
        $data = $request->validate(['correction_until' => 'required|date', 'reason' => 'required|string|max:1000']);
        $this->cases->unlockCorrection($assignment, Carbon::parse($data['correction_until']), $data['reason']);

        return back()->with(['type' => 'success', 'Meldung' => 'Korrektur für die Eltern freigegeben.']);
    }

    public function restart(Request $request, InquiryAssignment $assignment): RedirectResponse
    {
        $data = $request->validate(['restart_until' => 'required|date', 'reason' => 'required|string|max:1000']);
        $this->cases->restart($assignment, Carbon::parse($data['restart_until']), $data['reason']);

        return back()->with(['type' => 'success', 'Meldung' => 'Abfrage für die Familie neu gestartet. Die Sorgeberechtigten wurden benachrichtigt.']);
    }

    /**
     * Antwortdetails: Sekretariat (alle Versionen) bzw. Mitarbeitende (nur geprüfte, aktuelle
     * Antworten von Abfragen, die für Mitarbeitende freigegeben sind – Zweckbindung).
     */
    public function response(Request $request, InquiryResponse $response, FieldSchema $schema): View
    {
        $this->authorizeResponse($request, $response);
        $response->load(['inquiry', 'child', 'user:id,name', 'templateVersion', 'reviewer:id,name', 'assignment.group']);

        return view('elternabfragen.admin.response', [
            'response' => $response,
            'fields' => $response->templateVersion->fields,
            'schema' => $schema,
            'canReview' => $request->user()->can('view elternabfragen answers'),
            'pendingApprovals' => AdminController::pendingApprovalCount(),
        ]);
    }

    public function review(Request $request, InquiryResponse $response): RedirectResponse
    {
        $data = $request->validate([
            'review_status' => ['required', new Enum(InquiryReviewStatus::class)],
            'review_note' => 'nullable|string|max:2000',
        ]);
        $this->cases->review($response, $request->user(), InquiryReviewStatus::from($data['review_status']), $data['review_note'] ?? null);

        return back()->with(['type' => 'success', 'Meldung' => 'Prüfvermerk gespeichert.']);
    }

    public function file(Request $request, InquiryResponse $response, string $field, ResponseService $responses): Response
    {
        $this->authorizeResponse($request, $response);
        $file = $responses->filePath($response, $field);
        abort_unless($file, 404);

        return Storage::disk(ResponseService::DISK)->download($file['path'], $file['name']);
    }

    private function authorizeResponse(Request $request, InquiryResponse $response): void
    {
        if ($request->user()->can('view elternabfragen answers')) {
            return;
        }

        $inquiry = $response->inquiry;
        abort_unless(
            $request->user()->can('read elternabfragen')
                && $inquiry->staff_visible && $inquiry->purged_at === null
                && $response->is_current && $response->review_status === InquiryReviewStatus::Approved,
            404
        );
    }
}
