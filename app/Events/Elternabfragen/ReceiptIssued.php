<?php

namespace App\Events\Elternabfragen;

use App\Model\InquiryReceipt;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Eine Erklärungsquittung wurde ausgestellt (erst nach dem Commit der Erklärung).
 * Abonnenten (z. B. Mailversand an die erklärende Person) erzeugen das PDF bei Bedarf
 * über ReceiptService::renderPdf() – es ist byteidentisch zum quittierten Original.
 */
class ReceiptIssued implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly InquiryReceipt $receipt) {}
}
