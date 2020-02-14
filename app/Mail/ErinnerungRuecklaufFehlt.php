<?php

namespace App\Mail;

use App\Model\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ErinnerungRuecklaufFehlt extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $thema;
    public $ende;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, $thema, $ende)
    {
        $this->user = $user;
        $this->thema = $thema;
        $this->ende = $ende;
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
                env('MAIL_FROM_ADDRESS'),
                env('MAIL_FROM_NAME'),
            )
            ->subject('Feedback')
            ->view('emails.rueckmeldungFehlt')->with([
                "name"  => $this->user->name,
                "thema"  => $this->thema,
                "ende"  => $this->ende
            ]);
    }
}
