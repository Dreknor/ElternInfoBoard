<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserRueckmeldung extends Mailable
{
    use Queueable, SerializesModels;

    public mixed $email;

    public mixed $name;

    public $subject;

    public mixed $text;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(array $Rueckmeldung)
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
    public function build(): static
    {
        return $this
            ->from(
                config('mail.from.address'),
                config('mail.from.name')
            )
            ->replyTo($this->email, $this->name)
           ->subject($this->subject)
            ->view('emails.userRueckmeldung')->with(['text' => $this->text]);
    }
}
