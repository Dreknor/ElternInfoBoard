<?php

namespace App\Mail;

use App\Support\Collection;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DailyReportKrankmeldungen extends Mailable
{
    use Queueable, SerializesModels;

    public Collection|array $krankmeldungen;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Collection|array $krankmeldungen)
    {
        $this->krankmeldungen = $krankmeldungen;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): static
    {
        return $this
            ->subject('Krankmeldungen am '.Carbon::now()->format('d.m.Y'))
            ->view('emails.dailyReportKrankmeldungen', [
                'krankmeldungen' => $this->krankmeldungen,
            ]);
    }
}
