<?php

namespace App\Jobs;

use App\Model\Inquiry;
use App\Model\InquiryAssignment;
use App\Model\InquiryDelivery;
use App\Services\Elternabfragen\DeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Versendet Benachrichtigungen bzw. Erinnerungen einer Elternabfrage im Hintergrund
 * und meldet nicht zustellbare Fälle der verantwortlichen Person.
 */
class DeliverInquiryNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 1800;

    /**
     * @param  list<int>  $assignmentIds
     */
    public function __construct(
        public int $inquiryId,
        public array $assignmentIds,
        public string $kind = InquiryDelivery::KIND_RELEASE,
        public string $trigger = 'auto',
        public ?int $actorId = null,
    ) {}

    public function handle(DeliveryService $delivery): void
    {
        $inquiry = Inquiry::find($this->inquiryId);
        if (! $inquiry || ! $inquiry->isRunning()) {
            return;
        }

        $failed = 0;
        InquiryAssignment::query()
            ->with(['inquiry.templateVersion', 'child'])
            ->where('inquiry_id', $inquiry->id)
            ->whereIn('id', $this->assignmentIds)
            ->chunkById(100, function ($assignments) use ($delivery, &$failed) {
                foreach ($assignments as $assignment) {
                    $ok = $this->kind === InquiryDelivery::KIND_RELEASE
                        ? $delivery->notifyRelease($assignment)
                        : $delivery->remind($assignment, $this->kind, $this->trigger, $this->actorId);
                    $failed += $ok ? 0 : 1;
                }
            });

        if ($failed > 0) {
            $delivery->notifyProblems($inquiry, $failed);
        }
    }
}
