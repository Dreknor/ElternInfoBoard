<?php

namespace App\Http\Controllers\Elternabfragen;

use App\Enums\InquiryFieldType;
use App\Http\Controllers\Controller;
use App\Model\Inquiry;
use App\Model\InquiryAssignment;
use App\Model\InquiryResponse;
use App\Services\Elternabfragen\FieldSchema;
use App\Services\Elternabfragen\GuardianConfirmation;
use App\Services\Elternabfragen\InquiryAccess;
use App\Services\Elternabfragen\ResponseService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Elternansicht (Prozess C): Übersicht, Formular mit Entwurf, Zusammenfassung,
 * verbindliches Absenden, Empfangsbestätigung und PDF.
 */
class ParentInquiryController extends Controller
{
    public function __construct(
        private readonly InquiryAccess $access,
        private readonly ResponseService $responses,
        private readonly FieldSchema $schema,
        private readonly GuardianConfirmation $confirmation,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $assignments = $this->access->assignmentsQuery($user)
            ->with(['inquiry.templateVersion', 'child', 'currentResponse.user'])
            ->get()
            ->sortBy(fn (InquiryAssignment $a) => $a->inquiry->deadline_at)
            ->values();

        $states = $assignments->mapWithKeys(fn (InquiryAssignment $a) => [$a->id => $this->access->state($user, $a)]);

        // Offen: noch nicht beantwortet oder vom Nutzer noch zu bestätigen
        [$open, $done] = $assignments->partition(fn (InquiryAssignment $a) => (! $a->isAnswered() && $a->inquiry->isRunning()) || $states[$a->id]['can_confirm']);

        return view('elternabfragen.parent.index', compact('open', 'done', 'states'));
    }

    /**
     * Einstieg aus E-Mail/Push: bei genau einem Kind direkt zum Formular.
     */
    public function open(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $assignments = $this->access->assignmentsQuery($request->user())->where('inquiry_id', $inquiry->id)->get();
        abort_if($assignments->isEmpty(), 404);

        if ($assignments->count() === 1) {
            return redirect()->route('elternabfragen.show', $assignments->first());
        }

        return redirect()->route('elternabfragen.index');
    }

    public function show(Request $request, InquiryAssignment $assignment): View
    {
        $user = $request->user();
        $this->authorizeView($request, $assignment);
        $assignment->load(['inquiry.templateVersion', 'child', 'currentResponse.user', 'responses.user']);

        $state = $this->access->state($user, $assignment);
        $siblings = $this->access->assignmentsQuery($user)
            ->where('inquiry_id', $assignment->inquiry_id)
            ->with('child')
            ->get();

        return view('elternabfragen.parent.show', [
            'assignment' => $assignment,
            'inquiry' => $assignment->inquiry,
            'version' => $assignment->inquiry->templateVersion,
            'fields' => $assignment->inquiry->templateVersion->fields,
            'state' => $state,
            'answers' => $state['can_view_answers'] ? $this->responses->prefill($assignment, $user) : [],
            'hasDraft' => $assignment->drafts()->where('user_id', $user->id)->exists(),
            'siblings' => $siblings,
            'childOptions' => $this->responses->childOptions($assignment->inquiry->templateVersion->fields, $user),
            'schema' => $this->schema,
            'editing' => ($state['can_submit'] || ($state['can_correct'] && $request->boolean('korrigieren'))),
            'preview' => false,
            'confirmations' => $state['can_view_answers'] ? $this->confirmation->overview($assignment) : [],
        ]);
    }

    /**
     * Gültige Antwort als weitere sorgeberechtigte Person bestätigen.
     */
    public function confirm(Request $request, InquiryAssignment $assignment): RedirectResponse
    {
        $this->authorizeView($request, $assignment);
        $request->validate(['confirm' => 'accepted'], [
            'confirm.accepted' => 'Bitte bestätigen Sie, dass Sie die Angaben geprüft haben und mit ihnen einverstanden sind.',
        ]);

        $this->confirmation->confirm($assignment, $request->user(), ['channel' => 'web', 'ip' => $request->ip(), 'user_agent' => $request->userAgent()]);

        return redirect()->route('elternabfragen.show', $assignment)
            ->with(['type' => 'success', 'Meldung' => 'Vielen Dank! Ihre Bestätigung wurde gespeichert.']);
    }

    public function saveDraft(Request $request, InquiryAssignment $assignment): RedirectResponse
    {
        $this->authorizeView($request, $assignment);
        $this->responses->saveDraft($assignment, $request->user(), (array) $request->input('answers', []));

        return redirect()->route('elternabfragen.show', [$assignment, 'korrigieren' => $assignment->isAnswered() ? 1 : null])
            ->with(['type' => 'success', 'Meldung' => 'Entwurf gespeichert. Ein Entwurf gilt noch nicht als Rückmeldung.']);
    }

    public function discardDraft(Request $request, InquiryAssignment $assignment): RedirectResponse
    {
        $this->authorizeView($request, $assignment);
        $this->responses->discardDraft($assignment, $request->user());

        return redirect()->route('elternabfragen.show', $assignment)->with(['type' => 'info', 'Meldung' => 'Entwurf verworfen.']);
    }

    public function submit(Request $request, InquiryAssignment $assignment): RedirectResponse
    {
        $this->authorizeView($request, $assignment);
        $request->validate(['correction_reason' => 'nullable|string|max:1000', 'confirm' => 'accepted'], [
            'confirm.accepted' => 'Bitte bestätigen Sie die Richtigkeit Ihrer Angaben.',
        ]);

        $files = collect($assignment->inquiry->templateVersion->fields)
            ->filter(fn ($f) => $f['type'] === InquiryFieldType::File->value && $request->hasFile('answers.'.$f['key']))
            ->mapWithKeys(fn ($f) => [$f['key'] => $request->file('answers.'.$f['key'])])
            ->all();

        $this->responses->submit(
            $assignment,
            $request->user(),
            (array) $request->input('answers', []),
            $files,
            ['channel' => 'web', 'ip' => $request->ip(), 'user_agent' => $request->userAgent()],
            $request->input('correction_reason'),
        );

        return redirect()->route('elternabfragen.show', $assignment)
            ->with(['type' => 'success', 'Meldung' => 'Vielen Dank! Ihre Angaben wurden verbindlich übermittelt.', 'submitted' => true]);
    }

    public function pdf(Request $request, InquiryAssignment $assignment): Response
    {
        $this->authorizeView($request, $assignment);
        abort_unless($this->access->canAnswer($request->user(), $assignment) && $assignment->currentResponse, 404);

        $response = $assignment->currentResponse()->with('user')->first();

        return Pdf::loadView('elternabfragen.pdf.confirmation', [
            'assignment' => $assignment,
            'inquiry' => $assignment->inquiry,
            'response' => $response,
            'fields' => $response->templateVersion->fields,
            'schema' => $this->schema,
        ])->download('Bestaetigung_'.$assignment->inquiry_id.'_'.$assignment->child_id.'_v'.$response->version.'.pdf');
    }

    /**
     * Eigene hochgeladene Datei der aktuellen Antwort (nur Sorgeberechtigte).
     */
    public function file(Request $request, InquiryAssignment $assignment, string $field): Response
    {
        $this->authorizeView($request, $assignment);
        abort_unless($this->access->canAnswer($request->user(), $assignment), 404);

        /** @var InquiryResponse|null $response */
        $response = $assignment->currentResponse;
        $file = $response ? $this->responses->filePath($response, $field) : null;
        abort_unless($file, 404);

        return Storage::disk(ResponseService::DISK)->download($file['path'], $file['name']);
    }

    /**
     * Manipulierte IDs dürfen keinen Zugriff ermöglichen – auch nicht ihre Existenz verraten.
     */
    private function authorizeView(Request $request, InquiryAssignment $assignment): void
    {
        abort_unless($this->access->canView($request->user(), $assignment), 404);
    }
}
