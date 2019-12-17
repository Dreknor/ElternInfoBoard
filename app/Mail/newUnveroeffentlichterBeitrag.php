<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class newUnveroeffentlichterBeitrag extends Mailable
{
    use Queueable, SerializesModels;

    public $von;
    public $Betreff;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($von, $Betreff)
    {
        $this->von = $von;
        $this->Betreff = $Betreff;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->subject("neuer unveröffentlichter Beitrag im ElternInfoBoard")
            ->view('emails.neuerUnveroeffentlichterBeitrag', [
            "von"   => $this->von,
            "betreff"   => $this->Betreff
        ]);
    }
}
