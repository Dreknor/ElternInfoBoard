<?php

namespace App\Jobs;

use App\Mail\AktuelleInformationen;
use App\Model\Discussion;
use App\Model\Post;
use App\Model\User;
use App\Notifications\Push;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class SendNewsEMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $nachrichten;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user, $nachrichten)
    {
        $this->user = $user;
        $this->nachrichten = $nachrichten;

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $Nachrichten = $this->nachrichten->filter(function ($post){
            if ($post->released ==1 and $post->archived_ab->greaterThan(Carbon::now()) and $post->updated_at->greaterThan($this->user->lastEmail)){
                return $post;
            }
        });


        Mail::to($this->user->email)->queue(new AktuelleInformationen($Nachrichten, $this->user->name, []));

        $this->user->lastEmail = Carbon::now();
        $this->user->save();


    }
}
