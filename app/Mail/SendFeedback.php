<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendFeedback extends Mailable
{
    use Queueable, SerializesModels;

    protected $text;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($text)
    {
        $this->text = $text;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->from(config('mail.from.address'),
               config('mail.from.name')
            )
            ->replyTo(auth()->user()->email, auth()->user()->name)
            ->subject('Kontaktformular vom '.config('app.name'))
            ->view('emails.feedback')->with([
                "text"  => $this->text,
                'from'  =>  auth()->user()->name
            ]);
    }
}
