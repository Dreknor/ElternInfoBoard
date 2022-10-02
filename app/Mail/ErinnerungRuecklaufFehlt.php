<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ErinnerungRuecklaufFehlt extends Mailable
{
    use Queueable, SerializesModels;

    public $email;

    public $name;

    public $thema;

    public $ende;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($Email, $Name, $thema, $ende)
    {
        $this->email = $Email;
        $this->name = $Name;
        $this->thema = $thema;
        $this->ende = $ende;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->subject('Rückmeldung fehlt: '.$this->thema)
            ->view('emails.rueckmeldungFehlt')->with([
                'name' => $this->name,
                'thema' => $this->thema,
                'ende' => $this->ende,
            ]);
    }
}
