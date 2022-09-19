<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendFeedback extends Mailable
{
    use Queueable, SerializesModels;

    protected $text;
    protected $betreff;
    public $data;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($text, $betreff, $data)
    {
        $this->text = $text;
        $this->betreff = $betreff;
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $Mail = $this
            ->from(config('mail.from.address'),
               config('mail.from.name')
            )
            ->replyTo(auth()->user()->email, auth()->user()->name)
            ->subject($this->betreff)
            ->view('emails.feedback')->with([
                'text'  => $this->text,
                'from'  =>  auth()->user()->name,
            ]);

        if (count($this->data) > 0) {
            foreach ($this->data as $file) {
                $Mail->attach($file[0]);
            }
        }

        return $Mail;
    }
}
