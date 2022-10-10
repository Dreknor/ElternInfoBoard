<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TerminAbsage extends Mailable
{
    use Queueable, SerializesModels;

    public $liste;

    public $termin;

    public $empfaenger;

    public $user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($empfaenger, $liste, $termin, $user)
    {
        $this->liste = $liste;
        $this->termin = $termin;
        $this->empfaenger = $empfaenger;
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from(
            $this->user->email,
            $this->user->name
             )
            ->subject('Absage Termin: '.$this->termin->format('d.m.Y H:i'))
            ->view('emails.terminAbsage')->with([
                'empfaenger' => $this->empfaenger,
                'termin' => $this->termin,
                'liste' => $this->liste,
                'user' => $this->user->name,
            ]);
    }
}
