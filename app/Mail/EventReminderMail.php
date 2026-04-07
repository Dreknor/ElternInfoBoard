<?php

namespace App\Mail;

use App\Model\ElternratEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EventReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $event;

    public $hoursUntil;

    /**
     * Create a new message instance.
     */
    public function __construct(ElternratEvent $event, int $hoursUntil)
    {
        $this->event = $event;
        $this->hoursUntil = $hoursUntil;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Erinnerung: '.$this->event->title)
            ->view('emails.event-reminder')
            ->with([
                'event' => $this->event,
                'hoursUntil' => $this->hoursUntil,
            ]);
    }
}
