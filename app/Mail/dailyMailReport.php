<?php

namespace App\Mail;

use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class dailyMailReport extends Mailable
{
    use Queueable, SerializesModels;

    public Collection|array $mails;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Collection|array $mails)
    {
        $this->mails = $mails;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): static
    {
        return $this
            ->subject('Nachrichtenübersicht am '.Carbon::yesterday()->format('d.m.Y'))
            ->view('emails.dailyMailReport', [
                'mails' => $this->mails,
            ]);
    }
}
