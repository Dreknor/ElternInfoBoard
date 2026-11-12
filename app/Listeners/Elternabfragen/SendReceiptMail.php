<?php

namespace App\Listeners\Elternabfragen;

use App\Events\Elternabfragen\ReceiptIssued;
use App\Services\Elternabfragen\DeliveryService;
use Throwable;

/**
 * Erklärungsquittung an die erklärende Person schicken (synchron wie die übrigen Mails der
 * Elternabfragen; protokolliert in InquiryDelivery). Fehler beim Versand protokolliert der
 * DeliveryService, sie brechen die bereits gespeicherte Erklärung nicht ab.
 */
class SendReceiptMail
{
    public function __construct(private readonly DeliveryService $delivery) {}

    public function handle(ReceiptIssued $event): void
    {
        try {
            $this->delivery->sendReceipt($event->receipt);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
