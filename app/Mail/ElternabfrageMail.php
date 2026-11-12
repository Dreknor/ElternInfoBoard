<?php

namespace App\Mail;

use App\Settings\GeneralSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

/**
 * Benachrichtigung, Erinnerung, Eingangsbestätigung, Bitte um Bestätigung und Rückmeldung
 * zu einer Änderungsmeldung einer Elternabfrage.
 * Der Mailtext enthält bewusst keine Antwortinhalte (Datenminimierung) – nur Link zur Abfrage.
 * Eingangsbestätigungen tragen die Erklärungsquittung: bei nicht sensiblen Abfragen als
 * PDF-Anhang, bei sensiblen nur als Hinweis auf den Download im Portal bzw. in der App.
 */
class ElternabfrageMail extends Mailable
{
    use Queueable, SerializesModels;

    public const KIND_RELEASE = 'release';

    public const KIND_REMINDER = 'reminder';

    public const KIND_CONFIRMATION = 'confirmation';

    /** Weitere sorgeberechtigte Person soll die eingereichte Antwort bestätigen. */
    public const KIND_CONFIRMATION_REQUEST = 'confirmation_request';

    /** Änderungsmeldung der Eltern wurde übernommen. */
    public const KIND_CHANGE_ACCEPTED = 'change_accepted';

    /** Änderungsmeldung der Eltern wurde abgelehnt (Begründung nur im Board). */
    public const KIND_CHANGE_REJECTED = 'change_rejected';

    /** Bestätigung einer weiteren sorgeberechtigten Person ist eingegangen (mit Quittung). */
    public const KIND_CONFIRMED = 'confirmed';

    /** Widerruf einer Einwilligung ist eingegangen (mit Quittung). */
    public const KIND_WITHDRAWAL = 'withdrawal';

    public string $boardName;

    public function __construct(
        public string $kind,
        public string $userName,
        public string $inquiryTitle,
        public string $childName,
        public string $deadline,
        public string $url,
        public ?string $purpose = null,
        public ?string $submittedAt = null,
        public ?int $version = null,
        public ?string $receiptNumber = null,
        public ?string $receiptPdf = null,
        public ?string $receiptFileName = null,
    ) {
        $this->boardName = rescue(fn () => (new GeneralSetting)->app_name, config('app.name'), false);
    }

    public function build(): static
    {
        $subject = match ($this->kind) {
            self::KIND_REMINDER => 'Erinnerung: '.$this->inquiryTitle,
            self::KIND_CONFIRMATION => 'Eingangsbestätigung: '.$this->inquiryTitle,
            self::KIND_CONFIRMATION_REQUEST => 'Bitte bestätigen: '.$this->inquiryTitle,
            self::KIND_CHANGE_ACCEPTED => 'Änderung übernommen: '.$this->inquiryTitle,
            self::KIND_CHANGE_REJECTED => 'Änderung nicht übernommen: '.$this->inquiryTitle,
            self::KIND_CONFIRMED => 'Bestätigung eingegangen: '.$this->inquiryTitle,
            self::KIND_WITHDRAWAL => 'Widerruf eingegangen: '.$this->inquiryTitle,
            default => 'Neue Elternabfrage: '.$this->inquiryTitle,
        };

        return $this->subject($subject)->view('emails.elternabfrage');
    }

    /**
     * @return Attachment[]
     */
    public function attachments(): array
    {
        if ($this->receiptPdf === null || $this->receiptFileName === null) {
            return [];
        }

        return [
            Attachment::fromData(fn () => $this->receiptPdf, $this->receiptFileName)->withMime('application/pdf'),
        ];
    }
}
