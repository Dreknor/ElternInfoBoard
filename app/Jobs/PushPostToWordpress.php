<?php

namespace App\Jobs;

use App\Model\Post;
use App\Repositories\WordpressRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class PushPostToWordpress implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Anzahl der Versuche, bevor der Job als fehlgeschlagen gilt.
     */
    public int $tries = 3;

    /**
     * Wartezeit (Sekunden) zwischen den Versuchen.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Post $post
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $repository = new WordpressRepository;
        $repository->pushPost($this->post);
    }

    /**
     * Wird aufgerufen, wenn der Job endgültig fehlschlägt.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('PushPostToWordpress endgültig fehlgeschlagen', [
            'post_id' => $this->post->id ?? null,
            'message' => $exception->getMessage(),
        ]);
    }
}
