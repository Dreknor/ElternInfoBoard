<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class dringendeNachrichtStatus extends Mailable
{
    use Queueable, SerializesModels;

    public array $empfaenger;

    public string $senderEmail;

    public string $senderName;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(array $empfaenger, string $senderEmail, string $senderName)
    {
        $this->empfaenger = $empfaenger;
        $this->senderEmail = $senderEmail;
        $this->senderName = $senderName;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): static
    {
        Log::info('dringendeNachrichtStatus', [
            'empfaenger' => $this->empfaenger,
            'senderEmail' => $this->senderEmail,
            'senderName' => $this->senderName,
            'empfaenger_email' => $this->empfaenger,
        ]);

        return $this
            ->from(
                $this->senderEmail,
                $this->senderName
            )
            ->subject('Status dringender Nachricht')
            ->view('emails.statusDringend')->with([
                'empfaenger' => $this->empfaenger,
            ]);
    }
}
