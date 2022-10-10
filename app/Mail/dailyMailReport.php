<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class dailyMailReport extends Mailable
{
    use Queueable, SerializesModels;

    public $mails;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($mails)
    {
        $this->mails = $mails;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->subject('Nachrichtenübersicht am '.Carbon::yesterday()->format('d.m.Y'))
            ->view('emails.dailyMailReport', [
                'mails' => $this->mails,
            ]);
    }
}
