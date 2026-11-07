<?php

namespace App\Http\Controllers\Elternabfragen;

use App\Http\Controllers\Controller;
use App\Model\Group;
use App\Model\Inquiry;
use App\Model\InquiryExport;
use App\Services\Elternabfragen\ExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kontrollierter Export (Prozess E, Kap. 9): Auswahl → Vorschau → protokollierte Datei mit Ablaufdatum.
 */
class ExportController extends Controller
{
    public function __construct(private readonly ExportService $exports) {}

    public function create(Inquiry $inquiry): View
    {
        abort_unless($inquiry->isReleased() && $inquiry->purged_at === null, 404);

        return view('elternabfragen.admin.export', [
            'inquiry' => $inquiry->load('templateVersion'),
            'columns' => $this->exports->availableColumns($inquiry),
            'groups' => $this->groups($inquiry),
            'exports' => $inquiry->exports()->with('creator:id,name')->limit(20)->get(),
            'preview' => null,
            'input' => ['columns' => array_keys($this->exports->availableColumns($inquiry)), 'group_id' => null, 'include_open' => false, 'format' => 'xlsx', 'target' => ''],
            'pendingApprovals' => AdminController::pendingApprovalCount(),
        ]);
    }

    public function preview(Request $request, Inquiry $inquiry): View
    {
        $input = $this->validated($request, $inquiry, false);

        return view('elternabfragen.admin.export', [
            'inquiry' => $inquiry->load('templateVersion'),
            'columns' => $this->exports->availableColumns($inquiry),
            'groups' => $this->groups($inquiry),
            'exports' => $inquiry->exports()->with('creator:id,name')->limit(20)->get(),
            'preview' => $this->exports->preview($inquiry, $input['group_id'], $input['columns'], $input['include_open']),
            'input' => $input,
            'pendingApprovals' => AdminController::pendingApprovalCount(),
        ]);
    }

    public function store(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $input = $this->validated($request, $inquiry, true);

        $export = $this->exports->create(
            $inquiry, $request->user(), $input['group_id'], $input['columns'], $input['include_open'], $input['format'], $input['target'],
        );

        return redirect()->route('elternabfragen.admin.exports.create', $inquiry)
            ->with(['type' => 'success', 'Meldung' => 'Export mit '.$export->record_count.' Datensätzen erstellt. Die Datei ist bis '.$export->expires_at->format('d.m.Y').' abrufbar.', 'download' => $export->id]);
    }

    public function download(InquiryExport $export): Response
    {
        abort_unless($export->isAvailable() && Storage::disk(ExportService::DISK)->exists($export->path), 410, 'Die Exportdatei ist abgelaufen.');
        $this->exports->registerDownload($export);

        $name = 'Elternabfrage_'.$export->inquiry_id.'_'.$export->created_at->format('Ymd_His').'.'.$export->format;

        return Storage::disk(ExportService::DISK)->download($export->path, $name);
    }

    private function validated(Request $request, Inquiry $inquiry, bool $final): array
    {
        abort_unless($inquiry->isReleased() && $inquiry->purged_at === null, 404);

        $data = $request->validate([
            'columns' => 'required|array|min:1',
            'columns.*' => 'string|in:'.implode(',', array_keys($this->exports->availableColumns($inquiry))),
            'group_id' => 'nullable|integer',
            'format' => 'required|in:xlsx,csv',
            'target' => ($final ? 'required' : 'nullable').'|string|max:255',
        ], [
            'columns.required' => 'Bitte mindestens ein Feld auswählen.',
            'target.required' => 'Bitte das Ziel des Exports angeben (z. B. „Übertragung in die Schulverwaltung“).',
        ]);

        return [
            'columns' => array_values($data['columns']),
            'group_id' => $data['group_id'] ?? null,
            'include_open' => $request->boolean('include_open'),
            'format' => $data['format'],
            'target' => (string) ($data['target'] ?? ''),
        ];
    }

    private function groups(Inquiry $inquiry)
    {
        return Group::withoutGlobalScopes()
            ->whereIn('id', $inquiry->assignments()->whereNotNull('group_id')->distinct()->pluck('group_id'))
            ->orderBy('name')->get(['id', 'name']);
    }
}
