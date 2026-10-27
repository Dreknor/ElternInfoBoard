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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Kap. 7.4: Nach Ablauf der Aufbewahrungsfrist (ab Abschluss) werden Antworten,
 * Uploads, Exportdateien und personenbezogene Protokolldaten gelöscht. Erhalten
 * bleiben die Abfrage selbst und eine anonyme Statistik (Anzahl je Status).
 * Abfragen ohne Frist (retention_days = null) werden nie automatisch gelöscht.
 *
 * Die Frist beginnt mit dem Abschluss, bei nach Abschluss neu gestarteten Fällen mit
 * dem Ende der letzten individuellen Frist. Aktenrelevante Abfragen werden zurück-
 * gehalten, solange vollständige Antworten noch nicht für die Schülerakte gedruckt sind.
 */
class RetentionService
{
    public function __construct(
        private readonly ExportService $exports,
        private readonly RecordFilingService $filing,
    ) {}

    /** @return list<Inquiry> */
    public function due(?CarbonInterface $now = null): array
    {
        return array_values(array_filter($this->expired($now), fn (Inquiry $inquiry) => $this->unfiledCount($inquiry) === 0));
    }

    /**
     * Abgelaufen, aber wegen noch nicht gedruckter Antworten zurückgehalten.
     *
     * @return list<array{inquiry: Inquiry, unfiled: int}>
     */
    public function held(?CarbonInterface $now = null): array
    {
        $held = [];
        foreach ($this->expired($now) as $inquiry) {
            if (($unfiled = $this->unfiledCount($inquiry)) > 0) {
                $held[] = ['inquiry' => $inquiry, 'unfiled' => $unfiled];
            }
        }

        return $held;
    }

    /** Beginn der Aufbewahrungsfrist. */
    public function retentionStart(Inquiry $inquiry): ?CarbonInterface
    {
        if ($inquiry->closed_at === null) {
            return null;
        }

        $reopenedUntil = $inquiry->assignments()
            ->whereNotNull('reopened_at')
            ->where('reopened_at', '>=', $inquiry->closed_at)
            ->max('extended_until');

        return $reopenedUntil && $inquiry->closed_at->lessThan($reopenedUntil)
            ? Carbon::parse($reopenedUntil)
            : $inquiry->closed_at;
    }

    /** Voraussichtlicher Löschzeitpunkt (ohne Rückhalt wegen der Aktenablage). */
    public function purgeDate(Inquiry $inquiry): ?CarbonInterface
    {
        $start = $this->retentionStart($inquiry);

        return $start && $inquiry->retention_days ? $start->copy()->addDays($inquiry->retention_days) : null;
    }

    /** @return list<Inquiry> */
    private function expired(?CarbonInterface $now = null): array
    {
        $now ??= now();

        return Inquiry::query()
            ->with('templateVersion')
            ->where('status', InquiryStatus::Closed->value)
            ->whereNull('purged_at')
            ->whereNotNull('retention_days')
            ->whereNotNull('closed_at')
            ->get()
            ->filter(fn (Inquiry $inquiry) => $this->purgeDate($inquiry)->lessThanOrEqualTo($now))
            ->values()
            ->all();
    }

    private function unfiledCount(Inquiry $inquiry): int
    {
        return $inquiry->templateVersion?->record_relevant ? $this->filing->pendingCount($inquiry) : 0;
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
