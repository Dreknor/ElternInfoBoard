<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class krankmeldung extends Mailable
{
    use Queueable, SerializesModels;

    public $email;

    public $name;

    public $NameDesKindes;

    public $krankVon;

    public $krankBis;

    public $bemerkung;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($Email, $Name, $NameDesKindes, $krankVon, $krankBis, $bemerkung)
    {
        $this->email = $Email;
        $this->name = $Name;
        $this->NameDesKindes = $NameDesKindes;
        $this->krankVon = $krankVon;
        $this->krankBis = $krankBis;
        $this->bemerkung = $bemerkung;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->subject('Krankmeldung '.$this->NameDesKindes.': '.$this->krankVon.' - '.$this->krankBis)
            ->view('emails.krankmeldung');
    }
}
