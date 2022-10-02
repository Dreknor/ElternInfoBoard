<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class dringendeNachrichtStatus extends Mailable
{
    use Queueable, SerializesModels;

    public $empfaenger;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(array $empfaenger)
    {
        $this->empfaenger = $empfaenger;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->from(
                auth()->user()->email,
                auth()->user()->name
            )
            ->subject('Status dringender Nachricht')
            ->view('emails.statusDringend')->with([
                'empfaenger' => $this->empfaenger,
            ]);
    }
}
