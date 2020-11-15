<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DailyReportKrankmeldungen extends Mailable
{
    use Queueable, SerializesModels;

    public $krankmeldungen;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($krankmeldungen)
    {
        $this->krankmeldungen = $krankmeldungen;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->subject('Krankmeldungen am '.Carbon::now()->format('d.m.Y'))
            ->view('emails.dailyReportKrankmeldungen',[
                'krankmeldungen'    =>$this->krankmeldungen
            ]);
    }
}
