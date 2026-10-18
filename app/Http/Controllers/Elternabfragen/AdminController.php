<?php

namespace App\Http\Controllers\Elternabfragen;

use App\Enums\InquiryAssignmentStatus;
use App\Enums\InquiryReviewStatus;
use App\Enums\InquiryStatus;
use App\Enums\InquiryTemplateStatus;
use App\Http\Controllers\Controller;
use App\Model\Inquiry;
use App\Model\InquiryResponse;
use App\Model\InquiryTemplateVersion;
use App\Settings\ElternabfragenSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Sekretariats-Dashboard, Freigaben, Mitarbeiteransicht und Standardregeln.
 */
class AdminController extends Controller
{
    public const ADMIN_PERMISSIONS = [
        'manage elternabfragen', 'approve elternabfragen', 'view elternabfragen answers', 'export elternabfragen', 'read elternabfragen',
    ];

    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->canAny(self::ADMIN_PERMISSIONS), 403);

        if (! $user->canAny(['manage elternabfragen', 'approve elternabfragen', 'view elternabfragen answers', 'export elternabfragen'])) {
            return redirect()->route('elternabfragen.admin.staff');
        }

        $status = $request->query('status');
        $inquiries = Inquiry::query()
            ->with(['templateVersion', 'responsible:id,name'])
            ->when($status, fn ($q) => $q->where('status', $status), fn ($q) => $q->where('status', '!=', InquiryStatus::Closed->value))
            ->when($request->query('q'), fn ($q, $term) => $q->where('title', 'like', '%'.$term.'%'))
            ->orderByRaw('CASE WHEN deadline_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('deadline_at')
            ->paginate(20)
            ->withQueryString();

        $counts = DB::table('inquiry_assignments')
            ->whereIn('inquiry_id', $inquiries->pluck('id'))
            ->select('inquiry_id', 'status', DB::raw('count(*) as total'))
            ->groupBy('inquiry_id', 'status')
            ->get()
            ->groupBy('inquiry_id')
            ->map(fn ($rows) => $rows->pluck('total', 'status')->map(fn ($n) => (int) $n));

        return view('elternabfragen.admin.index', [
            'inquiries' => $inquiries,
            'counts' => $counts,
            'status' => $status,
            'pendingApprovals' => $this->pendingApprovalCount(),
        ]);
    }

    public function approvals(): View
    {
        return view('elternabfragen.admin.approvals', [
            'versions' => InquiryTemplateVersion::with(['template', 'creator:id,name'])
                ->where('status', InquiryTemplateStatus::InReview->value)->orderBy('submitted_at')->get(),
            'inquiries' => Inquiry::with(['templateVersion', 'creator:id,name'])
                ->where('status', InquiryStatus::PendingApproval->value)->orderBy('submitted_at')->get(),
            'pendingApprovals' => $this->pendingApprovalCount(),
        ]);
    }

    /**
     * Mitarbeiteransicht: lesender Zugriff auf geprüfte Antworten freigegebener Abfragen,
     * filterbar nach Inhalt, Kind und Zustand.
     */
    public function staff(Request $request): View
    {
        $inquiries = Inquiry::query()->staffVisible()->whereIn('status', [InquiryStatus::Running->value, InquiryStatus::Closed->value])
            ->orderByDesc('released_at')->get(['id', 'title']);

        $responses = InquiryResponse::query()
            ->with(['inquiry:id,title', 'child:id,first_name,last_name,class_id', 'child.class:id,name', 'assignment:id,status,group_id'])
            ->where('is_current', true)
            ->where('review_status', InquiryReviewStatus::Approved->value)
            ->whereIn('inquiry_id', $inquiries->pluck('id'))
            ->when($request->integer('abfrage'), fn ($q, $id) => $q->where('inquiry_id', $id))
            ->when($request->query('kind'), fn ($q, $term) => $q->whereHas('child', fn ($c) => $c
                ->where('first_name', 'like', '%'.$term.'%')->orWhere('last_name', 'like', '%'.$term.'%')))
            ->when($request->query('inhalt'), fn ($q, $term) => $q->where('answers', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%'))
            ->when($request->query('zustand'), fn ($q, $status) => $q->whereHas('assignment', fn ($a) => $a->where('status', $status)))
            ->orderByDesc('submitted_at')
            ->paginate(30)
            ->withQueryString();

        return view('elternabfragen.admin.staff', [
            'inquiries' => $inquiries,
            'responses' => $responses,
            'statusOptions' => [
                InquiryAssignmentStatus::Submitted->value => InquiryAssignmentStatus::Submitted->label(),
                InquiryAssignmentStatus::Corrected->value => InquiryAssignmentStatus::Corrected->label(),
            ],
            'pendingApprovals' => $this->pendingApprovalCount(),
        ]);
    }

    public function settings(ElternabfragenSetting $settings): View
    {
        return view('elternabfragen.admin.settings', ['rules' => $settings, 'pendingApprovals' => $this->pendingApprovalCount()]);
    }

    public function updateSettings(Request $request, ElternabfragenSetting $settings): RedirectResponse
    {
        $data = $request->validate([
            'first_reminder_after_hours' => 'required|integer|min:0|max:720',
            'reminder_interval_hours' => 'required|integer|min:1|max:720',
            'reminder_days_before_deadline' => 'required|integer|min:0|max:60',
            'max_reminders' => 'required|integer|min:0|max:20',
            'correction_window_days' => 'required|integer|min:0|max:365',
            'export_expiry_days' => 'required|integer|min:1|max:90',
            'default_retention_days' => 'required|integer|min:1|max:3650',
            'upload_max_kb' => 'required|integer|min:100|max:51200',
        ]);

        $settings->fill($data + ['four_eyes' => $request->boolean('four_eyes')])->save();

        return back()->with(['type' => 'success', 'Meldung' => 'Einstellungen gespeichert.']);
    }

    public static function pendingApprovalCount(): int
    {
        if (! auth()->user()?->can('approve elternabfragen')) {
            return 0;
        }

        return InquiryTemplateVersion::where('status', InquiryTemplateStatus::InReview->value)->count()
            + Inquiry::where('status', InquiryStatus::PendingApproval->value)->count();
    }
}
