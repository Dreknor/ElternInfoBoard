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

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($userid)
    {
        $this->user = $userid;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //@ToDo Neue
        // neue Listen
        //neue Dateien

        $user = User::find($this->user);


        //Nachrichten zusammenstellen
        if (!$user->can('view all')) {
            $Nachrichten = $user->posts;
        } else {
            $Nachrichten = Post::all();
        }

        $Nachrichten = $Nachrichten->filter(function ($post) use ($user)
        {
            if (!is_null($post->archiv_ab)) {
                if ($post->released == 1 and $post->updated_at->greaterThanOrEqualTo($user->lastEmail) and $post->archiv_ab->greaterThan(Carbon::now())) {
                    return $post;
                }
            }
        })->unique()->sortByDesc('updated_at')->all();

        Notification::send($user, new Push('Test', $Nachrichten->count()));
  /*
        //Elternratsdiskussionen versenden
        if ($user->hasRole('Elternrat')) {
            $diskussionen = Discussion::all();
            $diskussionen = collect($diskussionen);
            $diskussionen = $diskussionen->filter(function ($Discussion) use ($user) {
                if ($Discussion->updated_at->greaterThanOrEqualTo($user->lastEmail)) {
                    return $Discussion;
                }
            });
        } else {
            $diskussionen = [];
        }


        Mail::to($user->email)->queue(new AktuelleInformationen($Nachrichten, $user->name, $diskussionen));
      */

        $user->lastEmail = Carbon::now();
        $user->save();


    }
}
