<?php

namespace App\Http\Controllers\Elternabfragen;

use App\Http\Controllers\Controller;
use App\Model\Inquiry;
use App\Model\InquiryEvent;
use App\Model\InquiryReceipt;
use App\Services\Elternabfragen\ReceiptService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Erklärungsquittungen in der Verwaltung: vorgelegtes PDF prüfen und Zweitschriften.
 * Recht „view elternabfragen answers“ (wie Antwortansicht und Akten-PDF: Quittungen
 * enthalten die vollständigen Antworten).
 */
class ReceiptController extends Controller
{
    /** Höchstgröße des hochgeladenen PDFs in KB. */
    public const MAX_KB = 10240;

    public function __construct(private readonly ReceiptService $receipts) {}

    public function check(): View
    {
        return $this->checkView();
    }

    /**
     * Vorgelegtes PDF mit den gespeicherten Prüfsummen vergleichen. Die Datei wird nicht
     * gespeichert – nur ihre SHA-256 wird gebildet.
     */
    public function verify(Request $request): View
    {
        $request->validate(['pdf' => ['required', 'file', 'mimetypes:application/pdf', 'max:'.self::MAX_KB]], [
            'pdf.required' => 'Bitte wählen Sie die PDF-Datei der Quittung aus.',
            'pdf.mimetypes' => 'Bitte laden Sie eine PDF-Datei hoch.',
            'pdf.max' => 'Die Datei ist zu groß (höchstens 10 MB).',
        ]);

        $receipt = $this->receipts->matchesPdf((string) file_get_contents($request->file('pdf')->getRealPath()));

        return $this->checkView([
            'checked' => true,
            'receipt' => $receipt?->load(['inquiry:id,title,deleted_at', 'assignment.group:id,name']),
            'chain' => $receipt ? $this->receipts->verifyChain($receipt->inquiry) : null,
            'newer' => $receipt ? InquiryReceipt::where('assignment_id', $receipt->assignment_id)->where('sequence', '>', $receipt->sequence)->count() : 0,
        ]);
    }

    /** Zweitschrift (byteidentisch zum Original). */
    public function download(Request $request, InquiryReceipt $receipt): Response
    {
        $bytes = $this->receipts->pdf($receipt);

        InquiryEvent::create([
            'inquiry_id' => $receipt->inquiry_id,
            'assignment_id' => $receipt->assignment_id,
            'user_id' => $request->user()->id,
            'action' => 'receipt.downloaded',
            'data' => ['uuid' => $receipt->uuid],
        ]);

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$receipt->fileName().'"',
        ]);
    }

    /**
     * Übersicht für die Detailseite einer Abfrage: Kettenprüfung und Quittungen je Kind.
     *
     * @return array{count: int, backfilled: int, chain: array{valid: bool, count: int, errors: list<string>}, children: Collection<int, array{child: string, group: ?string, receipts: Collection<int, InquiryReceipt>}>}
     */
    public static function overview(Inquiry $inquiry): array
    {
        $receipts = InquiryReceipt::where('inquiry_id', $inquiry->id)
            ->with('assignment.group:id,name')
            ->orderBy('sequence')
            ->get();

        return [
            'count' => $receipts->count(),
            'backfilled' => $receipts->where('backfilled', true)->count(),
            'chain' => app(ReceiptService::class)->verifyChain($inquiry),
            'children' => $receipts->groupBy('assignment_id')
                ->map(fn (Collection $items) => [
                    'child' => $items->last()->child_name,
                    'group' => $items->first()->assignment?->group?->name,
                    'receipts' => $items->sortBy(fn (InquiryReceipt $r) => [$r->declared_at->getTimestamp(), $r->sequence])->values(),
                ])
                ->sortBy(fn (array $child) => [$child['group'] ?? 'zzz', $child['child']])
                ->values(),
        ];
    }

    private function checkView(array $data = []): View
    {
        return view('elternabfragen.admin.receipts.check', $data + [
            'checked' => false,
            'receipt' => null,
            'chain' => null,
            'newer' => 0,
            'pendingApprovals' => AdminController::pendingApprovalCount(),
        ]);
    }
}
