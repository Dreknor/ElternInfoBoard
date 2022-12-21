<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class krankmeldung extends Mailable
{
    use Queueable, SerializesModels;

    public string $email;

    public string $name;

    public string $NameDesKindes;

    public string $krankVon;

    public string $krankBis;

    public string $bemerkung;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $Email, string $Name, string $NameDesKindes, string $krankVon, string $krankBis, string $bemerkung)
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
    public function build(): static
    {
        return $this
            ->subject('Krankmeldung '.$this->NameDesKindes.': '.$this->krankVon.' - '.$this->krankBis)
            ->view('emails.krankmeldung');
    }
}
