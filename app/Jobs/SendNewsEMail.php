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

/*
        //Nachrichten zusammenstellen
        if (!$user->can('view all')) {
            $Nachrichten = $user->posts()->where('released', 1)->where('updated_at', '>=',$user->lastEmail)->where('archiv_ab', '>', Carbon::now())->get();
        } else {
            $Nachrichten = $user->posts()->where('released', 1)->where('updated_at', '>=',$user->lastEmail)->where('archiv_ab', '>', Carbon::now())->get();

            //$Nachrichten = Post::where('released', 1)->where('updated_at', '>=',$user->lastEmail)->where('archiv_ab', '>', Carbon::now());
        }
*/
        Notification::send($user, new Push('Test', $user->name));
       // Notification::send($user, new Push('Test2', $Nachrichten->count()));
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
