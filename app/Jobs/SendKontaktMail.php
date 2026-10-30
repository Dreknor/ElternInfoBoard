<?php

namespace App\Jobs;

use App\Mail\SendFeedback;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Verschickt eine Nachricht aus dem Kontaktformular (App) im Hintergrund.
 *
 * Anhänge liegen bis zum Versand im privaten Verzeichnis `storage/app/kontakt/{id}`
 * und werden danach – auch wenn der Versand endgültig scheitert – gelöscht.
 * Der Job wird verschlüsselt in der Queue abgelegt (Nachrichtentext, z. B. Krankmeldungen).
 */
class SendKontaktMail implements ShouldBeEncrypted, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const DIRECTORY = 'kontakt';

    public int $tries = 3;

    public int $backoff = 60;

    /**
     * @param  array<int, array{path: string, name: string, mime: ?string}>  $attachments  Pfade relativ zur Disk `local`
     */
    public function __construct(
        protected string $to,
        protected string $text,
        protected string $betreff,
        protected array $attachments,
        protected ?string $directory,
        protected ?string $userName,
        protected ?string $userEmail,
    ) {}

    public function handle(): void
    {
        $disk = Storage::disk('local');
        $attachments = array_map(fn (array $file) => [
            'path' => $disk->path($file['path']),
            'name' => $file['name'],
            'mime' => $file['mime'],
        ], $this->attachments);

        Mail::to($this->to)->send(new SendFeedback(
            $this->text,
            $this->betreff,
            ['attachments' => $attachments],
            $this->userName,
            $this->userEmail,
            $this->userName
        ));

        $this->cleanup();
    }

    /** Endgültig gescheitert: protokollieren (ohne Inhalte, B-61) und Anhänge löschen. */
    public function failed(Throwable $exception): void
    {
        Log::error('Kontaktformular (API): Versand fehlgeschlagen', [
            'attachments' => count($this->attachments),
            'error' => $exception->getMessage(),
        ]);
        $this->cleanup();
    }

    private function cleanup(): void
    {
        if ($this->directory) {
            Storage::disk('local')->deleteDirectory($this->directory);
        }
    }
}
