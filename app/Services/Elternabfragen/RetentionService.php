<?php

namespace App\Services\Elternabfragen;

use App\Enums\InquiryStatus;
use App\Model\Inquiry;
use App\Model\InquiryDelivery;
use App\Model\InquiryDraft;
use App\Model\InquiryEvent;
use App\Model\InquiryExport;
use App\Model\InquiryRecipient;
use App\Model\InquiryResponse;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Kap. 7.4: Nach Ablauf der Aufbewahrungsfrist (ab Abschluss) werden Antworten,
 * Uploads, Exportdateien und personenbezogene Protokolldaten gelöscht. Erhalten
 * bleiben die Abfrage selbst und eine anonyme Statistik (Anzahl je Status).
 * Abfragen ohne Frist (retention_days = null) werden nie automatisch gelöscht.
 */
class RetentionService
{
    public function __construct(private readonly ExportService $exports) {}

    /** @return list<Inquiry> */
    public function due(?CarbonInterface $now = null): array
    {
        $now ??= now();

        return Inquiry::query()
            ->where('status', InquiryStatus::Closed->value)
            ->whereNull('purged_at')
            ->whereNotNull('retention_days')
            ->whereNotNull('closed_at')
            ->get()
            ->filter(fn (Inquiry $inquiry) => $inquiry->closed_at->copy()->addDays($inquiry->retention_days)->lessThanOrEqualTo($now))
            ->values()
            ->all();
    }

    public function purge(Inquiry $inquiry): void
    {
        $statistics = $inquiry->assignments()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($n) => (int) $n)
            ->all();

        DB::transaction(function () use ($inquiry) {
            $assignmentIds = $inquiry->assignments()->pluck('id');

            InquiryExport::where('inquiry_id', $inquiry->id)->whereNull('file_deleted_at')->get()
                ->each(fn (InquiryExport $export) => $this->exports->deleteFile($export));

            InquiryResponse::whereIn('assignment_id', $assignmentIds)->delete();
            InquiryDraft::whereIn('assignment_id', $assignmentIds)->delete();
            InquiryRecipient::whereIn('assignment_id', $assignmentIds)->delete();
            InquiryDelivery::where('inquiry_id', $inquiry->id)->delete();
            InquiryEvent::where('inquiry_id', $inquiry->id)->whereNotNull('assignment_id')->delete();
            $inquiry->assignments()->delete();
        });

        Storage::disk(ResponseService::DISK)->deleteDirectory('elternabfragen/'.$inquiry->id);

        $inquiry->update(['purged_at' => now(), 'staff_visible' => false]);
        InquiryEvent::log('inquiry.purged', ['inquiry_id' => $inquiry->id], null, ['statistics' => $statistics]);
    }

    public function run(?CarbonInterface $now = null): int
    {
        $due = $this->due($now);
        foreach ($due as $inquiry) {
            $this->purge($inquiry);
        }

        return count($due);
    }
}
