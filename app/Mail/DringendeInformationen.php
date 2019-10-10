<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DringendeInformationen extends Mailable
{
    use Queueable, SerializesModels;

    public $header;
    public $text;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($header, $text )
    {
        $this->header = $header;
        $this->text = $text;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->header)
            ->view('emails.dringendeNachricht',[
            "nachricht" => $this->text,
            "header" => $this->header,
        ]);
    }
}
