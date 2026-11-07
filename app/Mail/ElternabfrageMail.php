<?php

namespace App\Mail;

use App\Settings\GeneralSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Benachrichtigung, Erinnerung, Eingangsbestätigung, Bitte um Bestätigung und Rückmeldung
 * zu einer Änderungsmeldung einer Elternabfrage.
 * Enthält bewusst keine Antwortinhalte (Datenminimierung) – nur Link zur Abfrage.
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
            default => 'Neue Elternabfrage: '.$this->inquiryTitle,
        };

        return $this->subject($subject)->view('emails.elternabfrage');
    }
}
