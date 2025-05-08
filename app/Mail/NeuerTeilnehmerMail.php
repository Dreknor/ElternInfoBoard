<?php

namespace App\Mail;

use App\Model\Arbeitsgemeinschaft;
use App\Model\Child;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NeuerTeilnehmerMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $arbeitsgemeinschaft;
    protected $child;

    public function __construct(Arbeitsgemeinschaft $arbeitsgemeinschaft, Child $child)
    {
        $this->arbeitsgemeinschaft = $arbeitsgemeinschaft;
        $this->child = $child;
    }

    public function build()
    {
        return $this->subject('Neuer Teilnehmer in Ihrer AG')
            ->markdown('emails.NewParticipantNotification', [
                'arbeitsgemeinschaft' => $this->arbeitsgemeinschaft,
                'child' => $this->child
            ]);
    }
}
