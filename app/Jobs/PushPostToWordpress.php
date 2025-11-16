<?php

namespace App\Jobs;

use App\Model\Post;
use App\Repositories\WordpressRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PushPostToWordpress implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Post $post
    )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $repository = new WordpressRepository();
        $repository->pushPost($this->post);
    }
}
