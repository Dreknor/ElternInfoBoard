<?php

namespace App\Http\Controllers\Elternabfragen;

use App\Enums\InquiryAssignmentStatus;
use App\Enums\InquiryStatus;
use App\Enums\InquiryTemplateStatus;
use App\Http\Controllers\Controller;
use App\Model\Group;
use App\Model\Inquiry;
use App\Model\InquiryAssignment;
use App\Model\InquiryDelivery;
use App\Model\InquiryResponse;
use App\Model\InquiryTemplateVersion;
use App\Model\User;
use App\Services\Elternabfragen\FieldSchema;
use App\Services\Elternabfragen\InquiryService;
use App\Services\Elternabfragen\ReminderService;
use App\Services\Elternabfragen\TargetResolver;
use App\Settings\ElternabfragenSetting;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Abfragen vorbereiten und freigeben (Prozess B) sowie Rücklaufübersicht (Prozess D, Kap. 6.4).
 */
class InquiryController extends Controller
{
    public function __construct(
        private readonly InquiryService $inquiries,
        private readonly TargetResolver $targets,
    ) {}

    public function create(Request $request, ElternabfragenSetting $settings): View
    {
        $version = $request->integer('vorlage')
            ? InquiryTemplateVersion::where('status', InquiryTemplateStatus::Approved->value)->find($request->integer('vorlage'))
            : null;

        $days = $version?->default_deadline_days ?? 14;

        return $this->form(new Inquiry([
            'template_version_id' => $version?->id,
            'title' => $version?->title,
            'starts_at' => now()->addHour()->startOfHour(),
            'deadline_at' => now()->addDays($days)->setTime(23, 59),
            'target_type' => Inquiry::TARGET_GROUPS,
            'target_group_ids' => [],
            'responsible_user_id' => $request->user()->id,
            'notify_mail' => true,
            'notify_push' => true,
            'auto_reminders' => true,
            'staff_visible' => false,
            'correction_window_days' => $settings->correction_window_days,
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $inquiry = $this->inquiries->create($this->validated($request), $request->user());

        return $this->afterSave($request, $inquiry, 'Abfrage als Entwurf angelegt.');
    }

    public function edit(Inquiry $inquiry): View|RedirectResponse
    {
        if (! $inquiry->isEditable()) {
            return redirect()->route('elternabfragen.admin.inquiries.show', $inquiry)
                ->with(['type' => 'warning', 'Meldung' => 'Nur Entwürfe können bearbeitet werden.']);
        }

        return $this->form($inquiry);
    }

    public function update(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $this->inquiries->update($inquiry, $this->validated($request));

        return $this->afterSave($request, $inquiry, 'Entwurf gespeichert.');
    }

    public function submit(Inquiry $inquiry): RedirectResponse
    {
        $this->inquiries->submit($inquiry);

        return redirect()->route('elternabfragen.admin.inquiries.show', $inquiry)
            ->with(['type' => 'success', 'Meldung' => 'Abfrage zur Freigabe eingereicht.']);
    }

    public function approve(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $request->validate(['confirm' => 'accepted', 'note' => 'nullable|string|max:2000'], [
            'confirm.accepted' => 'Bitte bestätigen Sie die Prüfung von Zielgruppe, Frist, Absender, Pflichtfeldern und Kanälen.',
        ]);
        $this->inquiries->approve($inquiry, $request->user(), $request->input('note'));

        return redirect()->route('elternabfragen.admin.inquiries.show', $inquiry)
            ->with(['type' => 'success', 'Meldung' => $inquiry->fresh()->isRunning()
                ? 'Abfrage freigegeben und gestartet.'
                : 'Abfrage freigegeben. Sie startet am '.$inquiry->starts_at->format('d.m.Y H:i').'.']);
    }

    public function reject(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $data = $request->validate(['note' => 'required|string|max:2000'], ['note.required' => 'Bitte beschreiben Sie, was überarbeitet werden soll.']);
        $this->inquiries->reject($inquiry, $data['note']);

        return redirect()->route('elternabfragen.admin.inquiries.show', $inquiry)
            ->with(['type' => 'info', 'Meldung' => 'Abfrage zur Überarbeitung zurückgegeben.']);
    }

    /**
     * Rücklauf nach Klasse, Kind und Status – ohne Antwortinhalte.
     */
    public function show(Request $request, Inquiry $inquiry, ReminderService $reminders): View
    {
        $user = $request->user();
        abort_unless($user->canAny(AdminController::ADMIN_PERMISSIONS) && ! $this->onlyStaff($user), 403);

        $inquiry->load(['templateVersion.template', 'responsible:id,name', 'creator:id,name', 'approver:id,name', 'previousInquiry:id,title']);

        $assignments = $inquiry->assignments()
            ->with(['child:id,first_name,last_name', 'group:id,name', 'recipients.user:id,name,email', 'currentResponse:id,version,review_status,submitted_at,user_id,baseline_changes', 'currentResponse.user:id,name'])
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->integer('klasse'), fn ($q, $groupId) => $q->where('group_id', $groupId))
            ->when($request->query('frist') === 'nachfrist', fn ($q) => $q->whereNotNull('extended_until'))
            ->when($request->query('q'), function ($q, $term) {
                $like = '%'.$term.'%';
                $q->where(fn ($w) => $w
                    ->whereHas('child', fn ($c) => $c->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like))
                    ->orWhereHas('recipients.user', fn ($u) => $u->where('name', 'like', $like)->orWhere('email', 'like', $like)));
            })
            ->get()
            ->sortBy(fn (InquiryAssignment $a) => [$a->group?->name ?? 'zzz', $a->child?->last_name ?? '', $a->child?->first_name ?? ''])
            ->values();

        $lastDeliveries = InquiryDelivery::query()
            ->whereIn('assignment_id', $assignments->pluck('id'))
            ->select('assignment_id', 'kind', DB::raw('max(sent_at) as sent_at'))
            ->groupBy('assignment_id', 'kind')
            ->get()
            ->groupBy('assignment_id');

        $statusCounts = $inquiry->assignments()->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');

        return view('elternabfragen.admin.inquiries.show', [
            'inquiry' => $inquiry,
            'assignments' => $assignments,
            'lastDeliveries' => $lastDeliveries,
            'statusCounts' => $statusCounts,
            'groups' => Group::withoutGlobalScopes()->whereIn('id', $inquiry->assignments()->whereNotNull('group_id')->distinct()->pluck('group_id'))->orderBy('name')->get(['id', 'name']),
            'preview' => in_array($inquiry->status, [InquiryStatus::Draft, InquiryStatus::PendingApproval, InquiryStatus::Scheduled], true)
                ? $this->targets->preview($inquiry) : null,
            'reminderBlocks' => $assignments->mapWithKeys(fn (InquiryAssignment $a) => [$a->id => $reminders->blockedReason($a->setRelation('inquiry', $inquiry))]),
            'events' => $inquiry->events()->with('user:id,name')->whereNull('assignment_id')->limit(50)->get(),
            'users' => User::permission(['manage elternabfragen', 'approve elternabfragen'])->orderBy('name')->get(['id', 'name']),
            'statusOptions' => InquiryAssignmentStatus::options(),
            'changeCounts' => $inquiry->previous_inquiry_id ? $this->changeCounts($inquiry) : null,
            'pendingApprovals' => AdminController::pendingApprovalCount(),
        ]);
    }

    /**
     * Änderungen gegenüber der Vorabfrage je Kind (Sekretariat): nur gültige Antworten,
     * standardmäßig nur Kinder mit geänderten Angaben.
     */
    public function changes(Request $request, Inquiry $inquiry, FieldSchema $schema): View
    {
        abort_unless($inquiry->previous_inquiry_id || $inquiry->responses()->whereNotNull('baseline_changes')->exists(), 404);
        $inquiry->load(['templateVersion', 'previousInquiry:id,title,deadline_at']);
        $all = $request->query('anzeigen') === 'alle';

        $responses = InquiryResponse::query()
            ->where('inquiry_id', $inquiry->id)
            ->where('is_current', true)
            ->with(['child:id,first_name,last_name', 'user:id,name', 'assignment.group:id,name'])
            ->get()
            ->filter(fn (InquiryResponse $r) => $all || $r->baselineChanges() !== [])
            ->sortBy(fn (InquiryResponse $r) => [$r->assignment?->group?->name ?? 'zzz', $r->child?->last_name ?? '', $r->child?->first_name ?? ''])
            ->values();

        return view('elternabfragen.admin.inquiries.changes', [
            'inquiry' => $inquiry,
            'responses' => $responses,
            'fields' => collect($inquiry->templateVersion->fields)->filter(fn ($f) => isset($f['key']))->keyBy('key'),
            'schema' => $schema,
            'counts' => $this->changeCounts($inquiry),
            'all' => $all,
            'pendingApprovals' => AdminController::pendingApprovalCount(),
        ]);
    }

    /**
     * @return array{changed: int, unchanged: int, without_baseline: int}
     */
    private function changeCounts(Inquiry $inquiry): array
    {
        $changes = InquiryResponse::where('inquiry_id', $inquiry->id)->where('is_current', true)->get(['id', 'baseline_changes']);

        return [
            'changed' => $changes->filter(fn (InquiryResponse $r) => $r->baselineChanges() !== [])->count(),
            'unchanged' => $changes->filter(fn (InquiryResponse $r) => $r->hasBaseline() && $r->baselineChanges() === [])->count(),
            'without_baseline' => $changes->reject(fn (InquiryResponse $r) => $r->hasBaseline())->count(),
        ];
    }

    /**
     * Elternvorschau vor dem Versand.
     */
    public function preview(Inquiry $inquiry, FieldSchema $schema): View
    {
        $inquiry->load('templateVersion');
        $assignment = new InquiryAssignment(['inquiry_id' => $inquiry->id, 'status' => InquiryAssignmentStatus::Open]);
        $assignment->setRelation('inquiry', $inquiry);
        $assignment->setRelation('child', new \App\Model\Child(['first_name' => 'Max', 'last_name' => 'Mustermann']));

        return view('elternabfragen.parent.show', [
            'assignment' => $assignment,
            'inquiry' => $inquiry,
            'version' => $inquiry->templateVersion,
            'fields' => $inquiry->templateVersion->fields,
            'state' => [
                'can_view_answers' => true, 'can_submit' => true, 'can_correct' => false, 'can_confirm' => false, 'awaiting_confirmation' => false, 'reason' => null,
                'deadline' => $inquiry->deadline_at, 'correction_until' => null,
            ],
            'answers' => [],
            'hasDraft' => false,
            'siblings' => collect(),
            'childOptions' => TemplateController::PREVIEW_CHILDREN,
            'schema' => $schema,
            'editing' => true,
            'preview' => true,
        ]);
    }

    public function sync(Inquiry $inquiry): RedirectResponse
    {
        $added = $this->inquiries->syncTargets($inquiry);

        return back()->with(['type' => 'success', 'Meldung' => $added > 0
            ? $added.' neu hinzugekommene Kinder ergänzt und benachrichtigt.'
            : 'Die Zielgruppe ist aktuell – keine neuen Kinder.']);
    }

    public function deadline(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $data = $request->validate([
            'deadline_at' => 'required|date',
            'reason' => 'required|string|max:1000',
        ], ['reason.required' => 'Änderungen an einer freigegebenen Abfrage müssen begründet werden.']);

        $this->inquiries->changeDeadline($inquiry, Carbon::parse($data['deadline_at']), $data['reason']);

        return back()->with(['type' => 'success', 'Meldung' => 'Frist geändert und protokolliert.']);
    }

    public function settings(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $data = $request->validate([
            'responsible_user_id' => 'nullable|exists:users,id',
            'correction_window_days' => 'required|integer|min:0|max:365',
        ]);
        $data += [
            'notify_mail' => $request->boolean('notify_mail'),
            'notify_push' => $request->boolean('notify_push'),
            'auto_reminders' => $request->boolean('auto_reminders'),
            'staff_visible' => $request->boolean('staff_visible'),
        ];

        $this->inquiries->updateSettings($inquiry, $data);

        return back()->with(['type' => 'success', 'Meldung' => 'Einstellungen gespeichert.']);
    }

    public function close(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $data = $request->validate(['note' => 'nullable|string|max:1000']);
        $wasScheduled = $inquiry->status === InquiryStatus::Scheduled;
        $this->inquiries->close($inquiry, $data['note'] ?? null);

        return back()->with(['type' => 'success', 'Meldung' => $wasScheduled
            ? 'Abfrage beendet. Sie wird nicht mehr gestartet.'
            : 'Abfrage beendet. Es sind keine Abgaben und Erinnerungen mehr möglich.']);
    }

    public function duplicate(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $copy = $this->inquiries->duplicate($inquiry, $request->user());

        return redirect()->route('elternabfragen.admin.inquiries.edit', $copy)
            ->with(['type' => 'info', 'Meldung' => 'Kopie als neuer Entwurf angelegt.']);
    }

    public function destroy(Inquiry $inquiry): RedirectResponse
    {
        $inquiry->assertStatus(InquiryStatus::Draft);
        $inquiry->delete();

        return redirect()->route('elternabfragen.admin.index')->with(['type' => 'success', 'Meldung' => 'Entwurf gelöscht.']);
    }

    private function form(Inquiry $inquiry): View
    {
        return view('elternabfragen.admin.inquiries.form', [
            'inquiry' => $inquiry,
            'versions' => InquiryTemplateVersion::with('template')
                ->where('status', InquiryTemplateStatus::Approved->value)
                ->whereHas('template', fn ($q) => $q->whereNull('archived_at'))
                ->orderBy('title')->get()
                ->when($inquiry->templateVersion && ! $inquiry->templateVersion->status->isEditable(), fn ($c) => $c->push($inquiry->templateVersion)->unique('id')),
            'groups' => Group::withoutGlobalScopes()->whereNull('owner_id')->where('active', true)->orderBy('name')->get(['id', 'name']),
            'users' => User::permission(['manage elternabfragen', 'approve elternabfragen'])->orderBy('name')->get(['id', 'name']),
            'previousInquiries' => $this->inquiries->previousCandidates($inquiry),
            'pendingApprovals' => AdminController::pendingApprovalCount(),
        ]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'template_version_id' => 'required|exists:inquiry_template_versions,id',
            'previous_inquiry_id' => 'nullable|integer',
            'title' => 'required|string|max:255',
            'intro' => 'nullable|string|max:10000',
            'starts_at' => 'required|date',
            'deadline_at' => 'required|date|after:starts_at',
            'target_type' => 'required|in:all,groups',
            'target_group_ids' => 'required_if:target_type,groups|array',
            'target_group_ids.*' => 'integer|exists:groups,id',
            'responsible_user_id' => 'nullable|exists:users,id',
            'correction_window_days' => 'required|integer|min:0|max:365',
        ], [
            'deadline_at.after' => 'Die Rückgabefrist muss nach dem Startzeitpunkt liegen.',
            'target_group_ids.required_if' => 'Bitte mindestens eine Klasse oder Gruppe wählen.',
        ]);

        return $data + [
            'notify_mail' => $request->boolean('notify_mail'),
            'notify_push' => $request->boolean('notify_push'),
            'auto_reminders' => $request->boolean('auto_reminders'),
            'staff_visible' => $request->boolean('staff_visible'),
        ];
    }

    private function afterSave(Request $request, Inquiry $inquiry, string $message): RedirectResponse
    {
        if ($request->boolean('submit_approval')) {
            $this->inquiries->submit($inquiry);
            $message = 'Abfrage gespeichert und zur Freigabe eingereicht.';
        }

        return redirect()->route('elternabfragen.admin.inquiries.show', $inquiry)->with(['type' => 'success', 'Meldung' => $message]);
    }

    private function onlyStaff(User $user): bool
    {
        return ! $user->canAny(['manage elternabfragen', 'approve elternabfragen', 'view elternabfragen answers', 'export elternabfragen']);
    }
}
