<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TerminAbsageEltern extends Mailable
{
    use Queueable, SerializesModels;

    public $liste;
    public $termin;
    public $user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, $liste, $termin)
    {
        $this->liste = $liste;
        $this->termin = $termin;
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
            ->view('emails.terminAbsageEltern')
            ->with([
                'termin' => $this->termin,
                'liste' => $this->liste,
                'user' => $this->user,
            ]);
    }
}
