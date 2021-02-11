<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserRueckmeldung extends Mailable
{
    use Queueable, SerializesModels;

    public $email;
    public $name;
    public $subject;
    public $text;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($Rueckmeldung)
    {
        $this->email = $Rueckmeldung['email'];
        $this->name = $Rueckmeldung['name'];
        $this->text = $Rueckmeldung['text'];
        $this->subject = $Rueckmeldung['subject'];

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
                config('mail.from.address'),
                config('mail.from.name')
            )
            ->replyTo($this->email,$this->name)
           ->subject($this->subject)
            ->view('emails.userRueckmeldung')->with(["text"  => $this->text]);
    }
}
