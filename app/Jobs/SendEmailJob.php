<?php

namespace App\Jobs;

use App\Mail\AktuelleInformationen;
use App\Mail\SendFeedback;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $email;
    protected $news;
    protected $name;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($email, $news, $name)
    {
        $this->email = $email;
        $this->news = $news;
        $this->name = $name;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $email = new AktuelleInformationen($this->news, $this->name);

        Mail::to('daniel.roehrich@ev-grundschule.de')->send(new SendFeedback($this->news));
        //Mail::to($this->email)->subject('aktuelle Nachrichten vom Schulzentrum')->send($email);
    }
}
