<?php

namespace App\Http\Controllers\Elternabfragen;

use App\Enums\InquiryAssignmentStatus;
use App\Enums\InquiryFieldType;
use App\Http\Controllers\Controller;
use App\Model\Child;
use App\Model\Inquiry;
use App\Model\InquiryAssignment;
use App\Model\InquiryEvent;
use App\Model\InquiryTemplate;
use App\Model\InquiryTemplateVersion;
use App\Services\Elternabfragen\FieldSchema;
use App\Services\Elternabfragen\TemplateImporter;
use App\Services\Elternabfragen\TemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Vorlagenverwaltung (Prozess A, Kap. 6.1).
 */
class TemplateController extends Controller
{
    /** Beispielkinder für Felder vom Typ „child“ in der Vorschau. */
    public const PREVIEW_CHILDREN = [1 => 'Max Mustermann', 2 => 'Erika Mustermann'];

    public function __construct(private readonly TemplateService $templates) {}

    public function index(Request $request): View
    {
        $templates = InquiryTemplate::query()
            ->with(['latestVersion', 'approvedVersion'])
            ->when(! $request->boolean('archiv'), fn ($q) => $q->whereNull('archived_at'), fn ($q) => $q->whereNotNull('archived_at'))
            ->orderBy('category')
            ->orderBy('title')
            ->get();

        return view('elternabfragen.admin.templates.index', [
            'templates' => $templates,
            'archive' => $request->boolean('archiv'),
            'pendingApprovals' => AdminController::pendingApprovalCount(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new InquiryTemplateVersion([
            'default_deadline_days' => 14,
            'fields' => [],
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $version = $this->templates->create($this->validated($request), $request->user());

        return $this->afterSave($request, $version, 'Vorlage angelegt.');
    }

    public function show(InquiryTemplateVersion $version): View
    {
        $version->load(['template.versions.creator', 'creator', 'reviewer', 'privacyChecker']);

        return view('elternabfragen.admin.templates.show', [
            'version' => $version,
            'events' => InquiryEvent::with('user:id,name')
                ->whereIn('template_version_id', $version->template->versions->pluck('id'))
                ->orderByDesc('id')->get(),
            'pendingApprovals' => AdminController::pendingApprovalCount(),
        ]);
    }

    public function edit(InquiryTemplateVersion $version): View|RedirectResponse
    {
        if (! $version->status->isEditable()) {
            return redirect()->route('elternabfragen.admin.templates.show', $version)
                ->with(['type' => 'warning', 'Meldung' => 'Diese Version ist nicht mehr bearbeitbar. Legen Sie eine neue Version an.']);
        }

        return $this->form($version);
    }

    public function update(Request $request, InquiryTemplateVersion $version): RedirectResponse
    {
        $this->templates->update($version, $this->validated($request));

        return $this->afterSave($request, $version, 'Entwurf gespeichert.');
    }

    public function submit(InquiryTemplateVersion $version): RedirectResponse
    {
        $this->templates->submit($version);

        return redirect()->route('elternabfragen.admin.templates.show', $version)
            ->with(['type' => 'success', 'Meldung' => 'Vorlage zur Prüfung eingereicht.']);
    }

    public function newVersion(Request $request, InquiryTemplate $template): RedirectResponse
    {
        $version = $this->templates->newVersion($template, $request->user());

        return redirect()->route('elternabfragen.admin.templates.edit', $version)
            ->with(['type' => 'info', 'Meldung' => 'Neue Version '.$version->version.' als Entwurf angelegt.']);
    }

    public function approve(Request $request, InquiryTemplateVersion $version): RedirectResponse
    {
        $data = $request->validate(['note' => 'nullable|string|max:2000']);
        $this->templates->approve($version, $request->user(), $request->boolean('privacy_checked'), $data['note'] ?? null);

        return redirect()->route('elternabfragen.admin.templates.show', $version)
            ->with(['type' => 'success', 'Meldung' => 'Vorlage freigegeben.']);
    }

    public function reject(Request $request, InquiryTemplateVersion $version): RedirectResponse
    {
        $data = $request->validate(['note' => 'required|string|max:2000'], ['note.required' => 'Bitte beschreiben Sie, was überarbeitet werden soll.']);
        $this->templates->reject($version, $request->user(), $data['note']);

        return redirect()->route('elternabfragen.admin.templates.show', $version)
            ->with(['type' => 'info', 'Meldung' => 'Vorlage zur Überarbeitung zurückgegeben.']);
    }

    public function archive(InquiryTemplate $template): RedirectResponse
    {
        $this->templates->archive($template);

        return redirect()->route('elternabfragen.admin.templates.index')
            ->with(['type' => 'success', 'Meldung' => 'Vorlage archiviert. Laufende Abfragen bleiben unverändert.']);
    }

    /**
     * Excel-/CSV-Liste einlesen: Spaltenköpfe werden zu Feldern für den Formular-Editor.
     */
    public function import(Request $request, TemplateImporter $importer): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt,ods|max:5120',
        ], [
            'file.required' => 'Bitte wählen Sie eine Excel- oder CSV-Datei aus.',
            'file.mimes' => 'Erlaubt sind Excel- (XLSX, XLS), ODS- und CSV-Dateien.',
            'file.max' => 'Die Datei darf höchstens 5 MB groß sein.',
        ]);

        return response()->json($importer->import($request->file('file')));
    }

    /**
     * Elternansicht einer gespeicherten Vorlagenversion.
     */
    public function preview(InquiryTemplateVersion $version, FieldSchema $schema): View
    {
        return $this->previewView($version, $schema, route('elternabfragen.admin.templates.show', $version), 'Zurück zur Vorlage');
    }

    /**
     * Elternansicht des ungespeicherten Formular-Editors (öffnet in neuem Tab, speichert nichts).
     */
    public function previewDraft(Request $request, FieldSchema $schema): View
    {
        // Die Vorschau leitet bei Fehlern nicht zurück (neuer Tab), sondern zeigt Hinweise an.
        $text = fn (string $key, int $max) => is_string($request->input($key)) ? Str::limit(trim($request->input($key)), $max, '') : null;
        $days = fn (string $key, int $max) => filter_var($request->input($key), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => $max]]) ?: null;
        $fields = json_decode((string) $request->input('fields_json'), true);
        [$normalized, $errors] = $schema->normalizeLenient(is_array($fields) ? $fields : []);

        $version = new InquiryTemplateVersion([
            'title' => $text('title', 255) ?: 'Neue Vorlage',
            'purpose' => $text('purpose', 5000) ?: '(Zweck noch nicht angegeben)',
            'parent_info' => $text('parent_info', 10000) ?: null,
            'responsible_office' => $text('responsible_office', 255) ?: null,
            'default_deadline_days' => $days('default_deadline_days', 365) ?? 14,
            'retention_days' => $request->boolean('retention_unlimited') ? null : $days('retention_days', 3650),
            'retention_unlimited' => $request->boolean('retention_unlimited'),
            'retention_note' => $text('retention_note', 2000) ?: null,
            'requires_all_guardians' => $request->boolean('requires_all_guardians'),
            'fields' => $normalized,
        ]);

        return $this->previewView($version, $schema, null, null, array_values($errors));
    }

    private function previewView(InquiryTemplateVersion $version, FieldSchema $schema, ?string $back, ?string $backLabel, array $warnings = []): View
    {
        $inquiry = new Inquiry([
            'title' => $version->title,
            'deadline_at' => now()->addDays((int) ($version->default_deadline_days ?: 14))->endOfDay(),
            'retention_days' => $version->retention_days,
        ]);
        $inquiry->setRelation('templateVersion', $version);

        $assignment = new InquiryAssignment(['status' => InquiryAssignmentStatus::Open]);
        $assignment->setRelation('inquiry', $inquiry);
        $assignment->setRelation('child', new Child(['first_name' => 'Max', 'last_name' => 'Mustermann']));

        return view('elternabfragen.parent.show', [
            'assignment' => $assignment,
            'inquiry' => $inquiry,
            'version' => $version,
            'fields' => $version->fields ?? [],
            'state' => [
                'can_view_answers' => true, 'can_submit' => true, 'can_correct' => false, 'can_confirm' => false, 'awaiting_confirmation' => false, 'reason' => null,
                'deadline' => $inquiry->deadline_at, 'correction_until' => null,
            ],
            'answers' => [],
            'hasDraft' => false,
            'siblings' => collect(),
            'childOptions' => self::PREVIEW_CHILDREN,
            'schema' => $schema,
            'editing' => true,
            'preview' => true,
            'previewBack' => $back,
            'previewBackLabel' => $backLabel,
            'previewWarnings' => $warnings,
        ]);
    }

    private function form(InquiryTemplateVersion $version): View
    {
        return view('elternabfragen.admin.templates.form', [
            'version' => $version,
            'fieldTypes' => InquiryFieldType::options(),
            'formats' => FieldSchema::FORMATS,
            'categories' => InquiryTemplate::query()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
            'pendingApprovals' => AdminController::pendingApprovalCount(),
        ]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'purpose' => 'required|string|max:5000',
            'parent_info' => 'nullable|string|max:10000',
            'responsible_office' => 'nullable|string|max:255',
            'target_hint' => 'nullable|string|max:255',
            'default_deadline_days' => 'required|integer|min:1|max:365',
            'retention_days' => 'nullable|integer|min:1|max:3650',
            'retention_unlimited' => 'nullable|boolean',
            'retention_note' => 'nullable|string|max:2000',
            'is_sensitive' => 'nullable|boolean',
            'requires_all_guardians' => 'nullable|boolean',
            'record_relevant' => 'nullable|boolean',
            'fields_json' => 'required|json',
        ], [
            'purpose.required' => 'Bitte beschreiben Sie den Zweck: Wofür werden die Angaben benötigt und wie werden sie verwendet?',
        ]);

        $data['fields'] = json_decode($data['fields_json'], true) ?: [];
        $data['is_sensitive'] = $request->boolean('is_sensitive');
        $data['requires_all_guardians'] = $request->boolean('requires_all_guardians');
        $data['record_relevant'] = $request->boolean('record_relevant');
        $data['retention_unlimited'] = $request->boolean('retention_unlimited');

        return $data;
    }

    private function afterSave(Request $request, InquiryTemplateVersion $version, string $message): RedirectResponse
    {
        if ($request->boolean('submit_review')) {
            $this->templates->submit($version);
            $message = 'Vorlage gespeichert und zur Prüfung eingereicht.';
        }

        return redirect()->route('elternabfragen.admin.templates.show', $version)->with([
            'type' => 'success',
            'Meldung' => $message,
            // Lokal zwischengespeicherten Editor-Stand verwerfen (siehe templates/form)
            'templateDraftSaved' => $request->input('draft_key'),
        ]);
    }
}
