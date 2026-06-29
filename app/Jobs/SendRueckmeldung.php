<?php

namespace App\Jobs;

use App\Mail\UserRueckmeldung;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendRueckmeldung implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Anzahl der Versuche, bevor der Job als fehlgeschlagen gilt.
     */
    public int $tries = 3;

    /**
     * Wartezeit (Sekunden) zwischen den Versuchen.
     */
    public int $backoff = 60;

    protected array $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $email = new UserRueckmeldung($this->data);

        Mail::to($this->data['empfaenger'])->queue($email);
    }

    /**
     * Wird aufgerufen, wenn der Job endgültig fehlschlägt.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('SendRueckmeldung: E-Mail konnte nicht versendet werden', [
            'empfaenger' => $this->data['empfaenger'] ?? null,
            'message' => $exception->getMessage(),
        ]);
    }
}
