<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class UserRueckmeldung extends Mailable
{
    use Queueable, SerializesModels;

    protected $user;
    public $subject;
    protected $text;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($text, $subject)
    {
        $this->user = auth()->user();
        $this->text = $text;
        $this->subject = $subject;

    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from([
            $this->user->email,
            $this->user->name
        ])->subject($this->subject)
            ->view('emails.userRueckmeldung')->with(["text"  => $this->text]);
    }
}
