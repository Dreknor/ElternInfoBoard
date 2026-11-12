<?php

namespace App\Services\Elternabfragen;

use App\Enums\InquiryReceiptKind;
use App\Model\Inquiry;
use App\Model\InquiryChangeRequest;
use App\Model\InquiryConfirmation;
use App\Model\InquiryEvent;
use App\Model\InquiryReceipt;
use App\Model\InquiryResponse;
use App\Model\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * Nacherfassung von Erklärungsquittungen für Erklärungen, die vor Einführung der Quittungen
 * abgegeben wurden (Antwortversionen und Bestätigungen ohne Quittung).
 *
 * Reihenfolge: je Abfrage chronologisch nach dem ursprünglichen Zeitpunkt der Erklärung
 * (Antwortversion vor Bestätigung, dann ID). Gibt es für eine Abfrage bereits Quittungen
 * (nach dem Update entstanden), werden die fehlenden hinten an die Kette angehängt – die
 * Kette bleibt lückenlos und prüfbar, die Nummer (sequence) entspricht dann aber nicht mehr
 * der zeitlichen Reihenfolge. Maßgeblich ist declared_at; nacherfasste Quittungen sind mit
 * backfilled gekennzeichnet und lösen keine E-Mails aus.
 *
 * Idempotent: Es werden nur Erklärungen ohne Quittung erfasst; je Abfrage unter Sperre.
 */
class ReceiptBackfill
{
    /** Präfix des Korrekturgrunds bei übernommenen Änderungsmeldungen (ChangeRequestService::accept). */
    public const CHANGE_PREFIX = 'Änderungsmeldung: ';

    public function __construct(private readonly ReceiptService $receipts) {}

    /** Günstige Prüfung (zwei EXISTS-Abfragen), ob es Erklärungen ohne Quittung gibt. */
    public function hasPending(): bool
    {
        if (! Schema::hasTable('inquiry_receipts')) {
            return false;
        }

        return $this->pendingResponses()->exists() || $this->pendingConfirmations()->exists();
    }

    /**
     * IDs der Abfragen mit fehlenden Quittungen (aufsteigend).
     *
     * @return Collection<int, int>
     */
    public function pendingInquiryIds(): Collection
    {
        return $this->pendingResponses()->distinct()->pluck('inquiry_responses.inquiry_id')
            ->merge(
                InquiryResponse::query()
                    ->whereIn('id', $this->pendingConfirmations()->select('inquiry_confirmations.response_id'))
                    ->distinct()->pluck('inquiry_id')
            )
            ->map(fn ($id) => (int) $id)->unique()->sort()->values();
    }

    /**
     * Fehlende Erklärungen einer Abfrage in der Reihenfolge der Nacherfassung.
     *
     * @return Collection<int, array{response: InquiryResponse, confirmation: ?InquiryConfirmation, kind: InquiryReceiptKind, declared_at: CarbonInterface, channel: string, declarant: ?User}>
     */
    public function plan(Inquiry $inquiry): Collection
    {
        $responses = $this->pendingResponses()->where('inquiry_responses.inquiry_id', $inquiry->id)->get();
        $confirmations = $this->pendingConfirmations()
            ->whereIn('response_id', InquiryResponse::where('inquiry_id', $inquiry->id)->select('id'))
            ->with('response')
            ->get();

        $changed = InquiryChangeRequest::where('inquiry_id', $inquiry->id)->whereNotNull('response_id')->pluck('response_id')->map(fn ($id) => (int) $id)->all();
        $users = User::withTrashed()
            ->whereIn('id', $responses->pluck('user_id')->merge($confirmations->pluck('user_id'))->filter()->unique())
            ->get(['id', 'name'])->keyBy('id');

        $items = $responses->map(fn (InquiryResponse $response) => [
            'response' => $response,
            'confirmation' => null,
            'kind' => $this->kindFor($response, $changed),
            'declared_at' => $response->submitted_at ?? $response->created_at,
            'channel' => (string) $response->channel,
            'declarant' => $users->get($response->user_id),
            'order' => 0,
            'id' => $response->id,
        ])->merge($confirmations->map(fn (InquiryConfirmation $confirmation) => [
            'response' => $confirmation->response,
            'confirmation' => $confirmation,
            'kind' => InquiryReceiptKind::Confirmation,
            'declared_at' => $confirmation->confirmed_at ?? $confirmation->created_at,
            'channel' => (string) $confirmation->channel,
            'declarant' => $users->get($confirmation->user_id),
            'order' => 1,
            'id' => $confirmation->id,
        ]));

        return $items
            ->sortBy([
                fn ($a, $b) => $a['declared_at']->getTimestamp() <=> $b['declared_at']->getTimestamp(),
                fn ($a, $b) => $a['order'] <=> $b['order'],
                fn ($a, $b) => $a['id'] <=> $b['id'],
            ])
            ->map(fn (array $item) => array_diff_key($item, ['order' => true, 'id' => true]))
            ->values();
    }

    /**
     * Fehlende Quittungen anlegen.
     *
     * @param  int|null  $limit  nach Erreichen dieser Anzahl keine weitere Abfrage beginnen (eine Abfrage wird immer vollständig erfasst)
     * @param  callable(Inquiry, int, bool): void|null  $progress  je Abfrage: Abfrage, Anzahl, Kette bereits vorhanden
     * @return array{inquiries: int, receipts: int, appended: int, failed: int}
     */
    public function run(bool $dryRun = false, ?int $limit = null, ?callable $progress = null): array
    {
        $stats = ['inquiries' => 0, 'receipts' => 0, 'appended' => 0, 'failed' => 0];

        foreach ($this->pendingInquiryIds() as $inquiryId) {
            if ($limit !== null && $stats['receipts'] >= $limit) {
                break;
            }

            $inquiry = Inquiry::whereKey($inquiryId)->whereNull('purged_at')->first();
            if ($inquiry === null) {
                continue;
            }

            try {
                [$count, $appended] = $dryRun
                    ? [$this->plan($inquiry)->count(), InquiryReceipt::where('inquiry_id', $inquiry->id)->exists()]
                    : $this->backfillInquiry($inquiry);
            } catch (Throwable $e) {
                // Abfrage bleibt unverändert (Transaktion) und wird beim nächsten Lauf erneut versucht
                report($e);
                $stats['failed']++;

                continue;
            }

            if ($count === 0) {
                continue;
            }
            $stats['inquiries']++;
            $stats['receipts'] += $count;
            $stats['appended'] += $appended ? 1 : 0;
            if ($progress) {
                $progress($inquiry, $count, $appended);
            }
        }

        return $stats;
    }

    /**
     * Eine Abfrage unter Sperre nacherfassen.
     *
     * @return array{0: int, 1: bool} Anzahl, an bestehende Kette angehängt
     */
    private function backfillInquiry(Inquiry $inquiry): array
    {
        return DB::transaction(function () use ($inquiry) {
            // Gleiche Sperre wie ReceiptService::issue – neue Erklärungen warten, bis der Block steht
            Inquiry::withTrashed()->whereKey($inquiry->id)->lockForUpdate()->first();
            $appended = InquiryReceipt::where('inquiry_id', $inquiry->id)->exists();
            $plan = $this->plan($inquiry);

            foreach ($plan as $item) {
                $this->receipts->issue($item['response'], $item['kind'], $item['confirmation'], [
                    'backfilled' => true,
                    'declared_at' => $item['declared_at'],
                    'declarant' => $item['declarant'],
                    'channel' => $item['channel'],
                ]);
            }

            if ($plan->isNotEmpty()) {
                InquiryEvent::create([
                    'inquiry_id' => $inquiry->id,
                    'action' => 'receipts.backfilled',
                    'note' => $plan->count().' '.($plan->count() === 1 ? 'Quittung' : 'Quittungen').' für frühere Erklärungen nacherfasst'
                        .($appended ? ' (an die bestehende Kette angehängt)' : '').'.',
                    'data' => ['count' => $plan->count(), 'appended' => $appended],
                ]);
            }

            return [$plan->count(), $appended];
        });
    }

    /**
     * Art der Erklärung einer Antwortversion – wie in ResponseService::createVersion.
     *
     * @param  list<int>  $changedResponseIds  Antwortversionen aus übernommenen Änderungsmeldungen
     */
    public function kindFor(InquiryResponse $response, array $changedResponseIds = []): InquiryReceiptKind
    {
        return match (true) {
            // Version 1 bzw. erste Antwort nach einem Neustart
            $response->previous_response_id === null => InquiryReceiptKind::Submission,
            in_array((int) $response->id, $changedResponseIds, true),
            Str::startsWith((string) $response->correction_reason, self::CHANGE_PREFIX) => InquiryReceiptKind::ChangeAccepted,
            default => InquiryReceiptKind::Correction,
        };
    }

    /** Antwortversionen nicht gelöschter Abfragen ohne Quittung der Version selbst. */
    private function pendingResponses(): Builder
    {
        return InquiryResponse::query()
            ->whereIn('inquiry_responses.inquiry_id', $this->activeInquiries())
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('inquiry_receipts')
                ->whereColumn('inquiry_receipts.response_id', 'inquiry_responses.id')
                ->whereNull('inquiry_receipts.confirmation_id')
                ->where('inquiry_receipts.kind', '!=', InquiryReceiptKind::Withdrawal->value));
    }

    /** Bestätigungen (nicht gelöschter Abfragen) ohne Quittung. */
    private function pendingConfirmations(): Builder
    {
        return InquiryConfirmation::query()
            ->whereIn('inquiry_confirmations.response_id', InquiryResponse::query()->whereIn('inquiry_id', $this->activeInquiries())->select('id'))
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('inquiry_receipts')
                ->whereColumn('inquiry_receipts.confirmation_id', 'inquiry_confirmations.id'));
    }

    private function activeInquiries(): Builder
    {
        return Inquiry::query()->whereNull('purged_at')->select('id');
    }
}
