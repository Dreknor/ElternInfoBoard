<?php

namespace App\Http\Controllers\Elternabfragen;

use App\Enums\InquiryChangeRequestStatus;
use App\Http\Controllers\Controller;
use App\Model\Inquiry;
use App\Model\InquiryChangeRequest;
use App\Services\Elternabfragen\ChangeRequestService;
use App\Services\Elternabfragen\FieldSchema;
use App\Services\Elternabfragen\ResponseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Änderungsmeldungen der Eltern prüfen, übernehmen oder ablehnen
 * (Recht „view elternabfragen answers“, da die Inhalte sichtbar sind).
 */
class ChangeRequestController extends Controller
{
    public function __construct(private readonly ChangeRequestService $changes) {}

    public function index(Request $request): View
    {
        $request->validate(['status' => ['nullable', new Enum(InquiryChangeRequestStatus::class)]]);
        // Standard: offene Meldungen; „alle“ = leerer Filter
        $status = $request->has('status') ? ($request->query('status') ?: null) : InquiryChangeRequestStatus::Pending->value;

        $requests = InquiryChangeRequest::query()
            ->with(['inquiry:id,title,status,closed_at', 'child:id,first_name,last_name', 'user:id,name', 'handler:id,name', 'assignment:id,group_id', 'assignment.group:id,name'])
            ->whereHas('inquiry', fn ($q) => $q->whereNull('purged_at'))
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->integer('abfrage'), fn ($q, $id) => $q->where('inquiry_id', $id))
            ->when($request->query('kind'), fn ($q, $term) => $q->whereHas('child', fn ($c) => $c
                ->where('first_name', 'like', '%'.$term.'%')->orWhere('last_name', 'like', '%'.$term.'%')))
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [InquiryChangeRequestStatus::Pending->value])
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        return view('elternabfragen.admin.changes.index', [
            'requests' => $requests,
            'status' => $status,
            'inquiries' => Inquiry::query()->whereIn('id', InquiryChangeRequest::query()->select('inquiry_id'))->whereNull('purged_at')->orderByDesc('id')->get(['id', 'title']),
            'pendingApprovals' => AdminController::pendingApprovalCount(),
        ]);
    }

    public function show(InquiryChangeRequest $changeRequest, FieldSchema $schema): View
    {
        $changeRequest->load(['inquiry.templateVersion', 'assignment.group', 'assignment.currentResponse', 'child', 'user:id,name,email', 'handler:id,name', 'baseResponse', 'response']);
        abort_if($changeRequest->inquiry === null || $changeRequest->inquiry->purged_at !== null || $changeRequest->assignment === null, 404);

        $current = $changeRequest->assignment->currentResponse;
        // Seit der Meldung wurde eine neue Version abgegeben: mit der heute gültigen vergleichen
        $outdated = $changeRequest->isPending() && $current?->id !== $changeRequest->base_response_id;

        return view('elternabfragen.admin.changes.show', [
            'change' => $changeRequest,
            'inquiry' => $changeRequest->inquiry,
            'assignment' => $changeRequest->assignment,
            'rows' => $this->changes->comparison($changeRequest, $changeRequest->isPending() ? $current : $changeRequest->baseResponse),
            'outdated' => $outdated,
            'schema' => $schema,
            'others' => InquiryChangeRequest::where('assignment_id', $changeRequest->assignment_id)->whereKeyNot($changeRequest->id)->with('user:id,name')->orderByDesc('id')->get(),
            'pendingApprovals' => AdminController::pendingApprovalCount(),
        ]);
    }

    public function accept(Request $request, InquiryChangeRequest $changeRequest): RedirectResponse
    {
        $data = $request->validate(['handled_note' => 'nullable|string|max:2000']);
        $response = $this->changes->accept($changeRequest, $request->user(), $data['handled_note'] ?? null);

        return redirect()->route('elternabfragen.admin.change-requests.index')->with([
            'type' => 'success',
            'Meldung' => 'Änderung übernommen (neue Version '.$response->version.'). Die Eltern wurden benachrichtigt.'
                .($changeRequest->inquiry->templateVersion?->record_relevant ? ' Bitte die neue Version für die Schülerakte drucken.' : ''),
        ]);
    }

    public function reject(Request $request, InquiryChangeRequest $changeRequest): RedirectResponse
    {
        $data = $request->validate(['handled_note' => 'required|string|max:2000'], [
            'handled_note.required' => 'Bitte begründen Sie die Ablehnung – die Begründung erhalten die Eltern.',
        ]);
        $this->changes->reject($changeRequest, $request->user(), $data['handled_note']);

        return redirect()->route('elternabfragen.admin.change-requests.index')
            ->with(['type' => 'success', 'Meldung' => 'Änderungsmeldung abgelehnt. Die Eltern wurden mit Begründung benachrichtigt.']);
    }

    /** Mit der Meldung hochgeladene Datei. */
    public function file(InquiryChangeRequest $changeRequest, string $field): Response
    {
        abort_if($changeRequest->inquiry?->purged_at !== null, 404);
        $value = $changeRequest->answers[$field] ?? null;
        abort_unless(is_array($value) && isset($value['path']) && Storage::disk(ResponseService::DISK)->exists($value['path']), 404);

        return Storage::disk(ResponseService::DISK)->download($value['path'], $value['name'] ?? 'Datei');
    }
}
