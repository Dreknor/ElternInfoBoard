<?php

namespace App\Mail;

use App\Model\Child;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Hinweis an weitere Bezugspersonen: das Kind wurde bereits krankgemeldet.
 */
class KrankmeldungInfoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $recipientName,
        public string $reporterName,
        public Child $child,
        public string $zeitraum,
    ) {}

    public function build(): static
    {
        return $this
            ->subject('Krankmeldung für '.$this->child->first_name.' eingegangen')
            ->view('emails.krankmeldungInfo');
    }
}
