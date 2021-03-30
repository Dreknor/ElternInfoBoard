<?php

namespace App\Jobs;

use App\Mail\AktuelleInformationen;
use App\Model\Discussion;
use App\Model\Post;
use App\Model\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendNewsEMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
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

        //Nachrichten zusammenstellen
        if (!$this->user->can('view all')) {
            $Nachrichten = $this->user->posts;
        } else {
            $Nachrichten = Post::all();
        }

        $Nachrichten = $Nachrichten->filter(function ($post) {
            if (!is_null($post->archiv_ab)) {
                if ($post->released == 1 and $post->updated_at->greaterThanOrEqualTo($this->user->lastEmail) and $post->archiv_ab->greaterThan(Carbon::now())) {
                    return $post;
                }
            }
        })->unique()->sortByDesc('updated_at')->all();

        //Elternratsdiskussionen versenden
        if ($this->user->hasRole('Elternrat')) {
            $diskussionen = Discussion::all();
            $diskussionen = collect($diskussionen);
            $diskussionen = $diskussionen->filter(function ($Discussion) {
                if ($Discussion->updated_at->greaterThanOrEqualTo($this->user->lastEmail)) {
                    return $Discussion;
                }
            });
        } else {
            $diskussionen = [];
        }

        if (count($Nachrichten)>0 or count($diskussionen)>0){
            Mail::to($this->user->email)->queue(new AktuelleInformationen($Nachrichten, $this->user->name, $diskussionen));
        }

        $this->user->lastEmail = Carbon::now();
        $this->user->save();


    }
}
