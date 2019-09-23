<?php

namespace App\Jobs;

use App\Mail\UserRueckmeldung;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;

class SendRueckmeldung implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $text;
    protected $header;
    protected $empfaenger;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($empfaenger, $header, $text)
    {
        $this->text = $text;
        $this->empfaenger =$empfaenger;
        $this->header = $header;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $email = new UserRueckmeldung($this->text, $this->header);

        Mail::to($this->empfaenger)->send($email);

    }
}
