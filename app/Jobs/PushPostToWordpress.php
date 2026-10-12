<?php

namespace App\Jobs;

use App\Model\Post;
use App\Repositories\WordpressRepository;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Überträgt eine Nachricht zur WordPress-Homepage.
 *
 * Pro Nachricht wartet höchstens ein Job in der Warteschlange (Controller und Observer
 * können gleichzeitig auslösen) und es läuft nie mehr als einer gleichzeitig – sonst
 * würden Bilder doppelt in die WordPress-Mediathek geladen. Da der Job die Nachricht
 * beim Ausführen frisch lädt, überträgt er immer den aktuellen Stand.
 */
class PushPostToWordpress implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Anzahl der Versuche, bevor der Job als fehlgeschlagen gilt.
     */
    public int $tries = 5;

    /**
     * Wartezeit (Sekunden) zwischen den Versuchen.
     *
     * @var array<int, int>
     */
    public array $backoff = [30, 60, 300, 900];

    /**
     * Sperre für doppelte Jobs spätestens nach dieser Zeit (Sekunden) aufheben.
     */
    public int $uniqueFor = 900;

    /**
     * Wurde die Nachricht inzwischen gelöscht, entfällt der Job.
     */
    public bool $deleteWhenMissingModels = true;

    public function __construct(
        public Post $post
    ) {
        //
    }

    public function uniqueId(): string
    {
        return (string) $this->post->getKey();
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('wordpress-post-'.$this->post->getKey()))
                ->releaseAfter(30)
                ->expireAfter(600),
        ];
    }

    public function handle(WordpressRepository $repository): void
    {
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
