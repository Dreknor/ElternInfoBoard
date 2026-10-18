<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\InquiryFieldType;
use App\Model\InquiryAssignment;
use App\Model\InquiryDraft;
use App\Model\InquiryResponse;
use App\Services\Elternabfragen\FieldSchema;
use App\Services\Elternabfragen\InquiryAccess;
use App\Services\Elternabfragen\ResponseService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Elternabfragen in der Eltern-App: je Kind ein Auftrag. Abgeben dürfen nur
 * Sorgeberechtigte; nicht zugängliche Aufträge liefern 404.
 *
 * @group Elternabfragen
 */
class InquiryController extends ApiController
{
    public function __construct(
        private readonly InquiryAccess $access,
        private readonly ResponseService $responses,
        private readonly FieldSchema $schema,
    ) {}

    /**
     * Alle Abfragen zu den eigenen Kindern.
     *
     * `status`: open | in_progress | submitted | corrected | problem.
     * `can_submit`/`can_correct` steuern die Buttons; `reason` erklärt, warum nicht.
     *
     * @queryParam filter string `open` = nur offene, die der Nutzer abgeben darf. Example: open
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $items = $this->access->assignmentsQuery($user)
            ->with(['inquiry', 'child', 'currentResponse'])
            ->get()
            ->map(fn (InquiryAssignment $a) => $this->summary($request, $a))
            ->when($request->query('filter') === 'open', fn ($c) => $c->filter(fn ($i) => $i['can_submit']))
            ->sortBy('deadline')
            ->values();

        return response()->json(['data' => $items]);
    }

    /**
     * Abfrage mit Feldern, Entwurf bzw. gültiger Antwort und Versionsverlauf.
     *
     * Feldtypen: consent (Werte granted|denied, `consent_text` vollständig anzeigen), yes_no (yes|no),
     * checkbox (Array der Optionen), radio, select, text (`format`: email|phone|postal_code|number),
     * textarea, date (YYYY-MM-DD), file (Upload beim Absenden), info (nur Überschrift/Hinweis).
     */
    public function show(Request $request, InquiryAssignment $assignment): JsonResponse
    {
        $this->authorizeView($request, $assignment);
        $user = $request->user();
        $assignment->load(['inquiry.templateVersion', 'child', 'currentResponse.user', 'responses.user']);
        $state = $this->access->state($user, $assignment);
        $version = $assignment->inquiry->templateVersion;
        $draft = $state['can_view_answers']
            ? InquiryDraft::where('assignment_id', $assignment->id)->where('user_id', $user->id)->first()
            : null;
        $current = $assignment->currentResponse;

        return response()->json(['data' => $this->summary($request, $assignment) + [
            'intro' => $assignment->inquiry->intro,
            'purpose' => $version->purpose,
            'parent_info' => $version->parent_info,
            'responsible_office' => $version->responsible_office,
            'retention_note' => $version->retention_note,
            'retention_days' => $assignment->inquiry->retention_days,
            'fields' => array_map(fn (array $f) => $this->presentField($f), $version->fields),
            'draft' => $draft ? ['answers' => $draft->answers, 'updated_at' => $draft->updated_at->toIso8601String()] : null,
            'response' => $current && $state['can_view_answers'] ? $this->presentResponse($assignment, $current) : null,
            'versions' => $state['can_view_answers'] ? $assignment->responses->map(fn (InquiryResponse $r) => [
                'version' => $r->version,
                'submitted_at' => $r->submitted_at->toIso8601String(),
                'submitted_by' => $r->user?->name,
                'is_current' => $r->is_current,
                'correction_reason' => $r->correction_reason,
            ])->values() : [],
        ]]);
    }

    /**
     * Entwurf speichern (gilt nicht als Rückmeldung; Dateien werden erst beim Absenden übernommen).
     *
     * @bodyParam answers object required Antworten je Feldschlüssel.
     */
    public function saveDraft(Request $request, InquiryAssignment $assignment): JsonResponse
    {
        $this->authorizeView($request, $assignment);
        $request->validate(['answers' => 'present|array']);
        $draft = $this->responses->saveDraft($assignment, $request->user(), (array) $request->input('answers', []));

        return response()->json(['data' => ['answers' => $draft->answers, 'updated_at' => $draft->updated_at->toIso8601String()]]);
    }

    /**
     * Entwurf verwerfen.
     */
    public function discardDraft(Request $request, InquiryAssignment $assignment): JsonResponse
    {
        $this->authorizeView($request, $assignment);
        $this->responses->discardDraft($assignment, $request->user());

        return response()->json(['message' => 'Entwurf verworfen.']);
    }

    /**
     * Verbindlich absenden bzw. korrigieren.
     *
     * Als JSON oder – bei Datei-Feldern – als multipart/form-data (`answers[<key>]`).
     * 403 ohne Sorgerecht oder nach Fristende, 409 wenn bereits eine Antwort vorliegt und
     * keine Korrektur mehr möglich ist, 422 bei ungültigen Angaben (Meldungen je `answers.<key>`).
     *
     * @bodyParam answers object required Antworten je Feldschlüssel.
     * @bodyParam confirm boolean required Bestätigung der Richtigkeit. Example: true
     * @bodyParam correction_reason string Grund einer Korrektur.
     */
    public function submit(Request $request, InquiryAssignment $assignment): JsonResponse
    {
        $this->authorizeView($request, $assignment);
        $request->validate([
            'answers' => 'present|array',
            'confirm' => 'accepted',
            'correction_reason' => 'nullable|string|max:1000',
        ], ['confirm.accepted' => 'Bitte bestätigen Sie die Richtigkeit Ihrer Angaben.']);

        $files = collect($assignment->inquiry->templateVersion->fields)
            ->filter(fn ($f) => $f['type'] === InquiryFieldType::File->value && $request->hasFile('answers.'.$f['key']))
            ->mapWithKeys(fn ($f) => [$f['key'] => $request->file('answers.'.$f['key'])])
            ->all();

        $response = $this->responses->submit(
            $assignment,
            $request->user(),
            (array) $request->input('answers', []),
            $files,
            ['channel' => 'app', 'ip' => $request->ip(), 'user_agent' => $request->userAgent()],
            $request->input('correction_reason'),
        );

        $assignment->refresh()->load(['inquiry', 'child']);

        return response()->json([
            'message' => 'Vielen Dank! Ihre Angaben wurden verbindlich übermittelt.',
            'data' => $this->summary($request, $assignment) + ['response' => $this->presentResponse($assignment, $response)],
        ], 201);
    }

    /**
     * PDF-Bestätigung der gültigen Antwort.
     */
    public function pdf(Request $request, InquiryAssignment $assignment): Response
    {
        $this->authorizeView($request, $assignment);
        abort_unless($this->access->canAnswer($request->user(), $assignment) && $assignment->currentResponse, 404);
        $response = $assignment->currentResponse()->with(['user', 'templateVersion'])->first();

        return Pdf::loadView('elternabfragen.pdf.confirmation', [
            'assignment' => $assignment,
            'inquiry' => $assignment->inquiry,
            'response' => $response,
            'fields' => $response->templateVersion->fields,
            'schema' => $this->schema,
        ])->download('Bestaetigung_'.$assignment->inquiry_id.'_v'.$response->version.'.pdf');
    }

    /**
     * Hochgeladene Datei der gültigen Antwort.
     */
    public function file(Request $request, InquiryAssignment $assignment, string $field): Response
    {
        $this->authorizeView($request, $assignment);
        abort_unless($this->access->canAnswer($request->user(), $assignment), 404);
        $file = $assignment->currentResponse ? $this->responses->filePath($assignment->currentResponse, $field) : null;
        abort_unless($file, 404);

        return Storage::disk(ResponseService::DISK)->download($file['path'], $file['name']);
    }

    private function summary(Request $request, InquiryAssignment $assignment): array
    {
        $state = $this->access->state($request->user(), $assignment);
        $inquiry = $assignment->inquiry;

        return [
            'id' => $assignment->id,
            'inquiry_id' => $inquiry->id,
            'title' => $inquiry->title,
            'child' => [
                'id' => $assignment->child_id,
                'first_name' => $assignment->child?->first_name,
                'full_name' => $assignment->childName(),
            ],
            'status' => $assignment->status->value,
            'status_label' => $assignment->status->label(),
            'inquiry_status' => $inquiry->status->value,
            'deadline' => $state['deadline']->toIso8601String(),
            'extended' => $assignment->extended_until !== null,
            'submitted_at' => $assignment->last_submitted_at?->toIso8601String(),
            'can_view_answers' => $state['can_view_answers'],
            'can_submit' => $state['can_submit'],
            'can_correct' => $state['can_correct'],
            'correction_until' => $state['correction_until']?->toIso8601String(),
            'reason' => $state['reason'],
        ];
    }

    private function presentField(array $field): array
    {
        return array_filter([
            'key' => $field['key'],
            'type' => $field['type'],
            'label' => $field['label'],
            'help' => $field['help'] ?? null,
            'required' => (bool) ($field['required'] ?? false),
            'options' => match ($field['type']) {
                'yes_no' => [['value' => FieldSchema::YES, 'label' => 'Ja'], ['value' => FieldSchema::NO, 'label' => 'Nein']],
                'consent' => [['value' => FieldSchema::GRANTED, 'label' => 'Ich willige ein'], ['value' => FieldSchema::DENIED, 'label' => 'Ich willige nicht ein']],
                default => isset($field['options']) ? array_map(fn ($o) => ['value' => $o, 'label' => $o], $field['options']) : null,
            },
            'min_length' => $field['min_length'] ?? null,
            'max_length' => $field['max_length'] ?? null,
            'format' => $field['format'] ?? null,
            'error_message' => $field['error_message'] ?? null,
            'consent_text' => $field['consent_text'] ?? null,
            'accept' => $field['type'] === 'file' ? FieldSchema::UPLOAD_MIMES : null,
        ], fn ($v) => $v !== null);
    }

    private function presentResponse(InquiryAssignment $assignment, InquiryResponse $response): array
    {
        $fields = collect($response->templateVersion?->fields ?? $assignment->inquiry->templateVersion->fields)->keyBy('key');

        return [
            'version' => $response->version,
            'submitted_at' => $response->submitted_at->toIso8601String(),
            'submitted_by' => $response->user?->name,
            'answers' => collect($response->answers)->map(fn ($value, $key) => is_array($value) && isset($value['path'])
                ? ['name' => $value['name'], 'size' => $value['size'] ?? null, 'url' => route('api.v1.elternabfragen.file', [$assignment, $key])]
                : $value)->all(),
            'display' => $fields->filter(fn ($f) => $f['type'] !== 'info')
                ->map(fn ($f) => ['key' => $f['key'], 'label' => $f['label'], 'value' => $this->schema->display($f, $response->answers[$f['key']] ?? null)])
                ->values(),
            'answer_hash' => $response->answer_hash,
            'pdf_url' => route('api.v1.elternabfragen.pdf', $assignment),
        ];
    }

    private function authorizeView(Request $request, InquiryAssignment $assignment): void
    {
        abort_unless($this->access->canView($request->user(), $assignment), 404, 'Abfrage nicht gefunden.');
    }
}
