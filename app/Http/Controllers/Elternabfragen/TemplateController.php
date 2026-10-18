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
        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'purpose' => 'nullable|string|max:5000',
            'parent_info' => 'nullable|string|max:10000',
            'responsible_office' => 'nullable|string|max:255',
            'default_deadline_days' => 'nullable|integer|min:1|max:365',
            'retention_days' => 'nullable|integer|min:1|max:3650',
            'retention_note' => 'nullable|string|max:2000',
            'fields_json' => 'required|json',
        ]);

        $version = new InquiryTemplateVersion([
            'title' => ($data['title'] ?? null) ?: 'Neue Vorlage',
            'purpose' => ($data['purpose'] ?? null) ?: '(Zweck noch nicht angegeben)',
            'parent_info' => $data['parent_info'] ?? null,
            'responsible_office' => $data['responsible_office'] ?? null,
            'default_deadline_days' => $data['default_deadline_days'] ?? 14,
            'retention_days' => $data['retention_days'] ?? null,
            'retention_note' => $data['retention_note'] ?? null,
            'fields' => $schema->normalizeDefinition(json_decode($data['fields_json'], true) ?: []),
        ]);

        return $this->previewView($version, $schema, null, null);
    }

    private function previewView(InquiryTemplateVersion $version, FieldSchema $schema, ?string $back, ?string $backLabel): View
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
                'can_view_answers' => true, 'can_submit' => true, 'can_correct' => false, 'reason' => null,
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
            'retention_note' => 'nullable|string|max:2000',
            'is_sensitive' => 'nullable|boolean',
            'fields_json' => 'required|json',
        ], [
            'purpose.required' => 'Bitte beschreiben Sie den Zweck: Wofür werden die Angaben benötigt und wie werden sie verwendet?',
        ]);

        $data['fields'] = json_decode($data['fields_json'], true) ?: [];
        $data['is_sensitive'] = $request->boolean('is_sensitive');

        return $data;
    }

    private function afterSave(Request $request, InquiryTemplateVersion $version, string $message): RedirectResponse
    {
        if ($request->boolean('submit_review')) {
            $this->templates->submit($version);
            $message = 'Vorlage gespeichert und zur Prüfung eingereicht.';
        }

        return redirect()->route('elternabfragen.admin.templates.show', $version)->with(['type' => 'success', 'Meldung' => $message]);
    }
}
