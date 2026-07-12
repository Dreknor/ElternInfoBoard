<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Versendet die beim Import erzeugte Zugangsdaten-PDF an den einloggten Benutzer,
 * der den Import durchgeführt hat. Wird verwendet, wenn beim Import "PDF" statt
 * "E-Mail an neue Benutzer" gewählt wurde, damit die Zugangsdaten trotzdem zusätzlich
 * per Mail verfügbar sind.
 */
class ImportCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $importTypeLabel;

    public int $userCount;

    protected string $pdfContent;

    protected string $filename;

    public function __construct(string $pdfContent, string $filename, string $importTypeLabel, int $userCount)
    {
        $this->pdfContent = $pdfContent;
        $this->filename = $filename;
        $this->importTypeLabel = $importTypeLabel;
        $this->userCount = $userCount;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Zugangsdaten – ' . $this->importTypeLabel,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.import-credentials',
        );
    }

    /**
     * @return Attachment[]
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfContent, $this->filename)
                ->withMime('application/pdf'),
        ];
    }
}
