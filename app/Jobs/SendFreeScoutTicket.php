<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * SendFreeScoutTicket
 *
 * Sendet eine Support-Anfrage oder eine automatisch erfasste Server-Exception
 * an die FreeScout REST-API (/api/conversations).
 *
 * Dispatch-Verhalten (steuerbar über .env):
 *   FREESCOUT_QUEUE_SYNC=true  → synchron im selben Prozess (kein Worker nötig)
 *   FREESCOUT_QUEUE_SYNC=false → asynchron auf der Queue "freescout" (Standard)
 *
 * Payload-Schlüssel:
 *   message      (string)  – Nachrichtentext
 *   subject      (string)  – Betreffzeile
 *   user_name    (string)  – Vollständiger Nutzername
 *   user_email   (string)  – E-Mail-Adresse des Nutzers
 *   user_role    (string)  – Primäre Rolle des Nutzers (optional)
 *   page_url     (string)  – URL der Seite, von der die Anfrage stammt
 *   screenshot   (string|null) – Base64-kodierter PNG-Screenshot (optional)
 */
class SendFreeScoutTicket implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int Maximale Versuche bei Fehlern */
    public int $tries = 3;

    /** @var int Wartezeit in Sekunden zwischen den Versuchen */
    public int $backoff = 30;

    protected array $payload;

    /**
     * @param  array{
     *     message: string,
     *     subject: string,
     *     user_name: string,
     *     user_email: string,
     *     user_role?: string,
     *     page_url?: string,
     *     screenshot?: string|null,
     * } $payload
     */
    public function __construct(array $payload)
    {
        $this->payload = $payload;

        // Dedizierte Queue setzen (wird bei sync-Betrieb ignoriert)
        $this->onQueue(config('services.freescout.queue_name', 'freescout'));
    }

    /**
     * Zentrale Dispatch-Methode.
     *
     * Entscheidet anhand von FREESCOUT_QUEUE_SYNC, ob der Job synchron
     * (dispatchSync) oder asynchron (dispatch) ausgeführt wird.
     * Alle Stellen im Code rufen ausschließlich diese Methode auf.
     *
     * @param  array $payload  Siehe Konstruktor-Dokumentation
     */
    public static function dispatchTicket(array $payload): void
    {
        if (config('services.freescout.queue_sync', false)) {
            // Synchron: läuft sofort im selben Prozess – ideal für Cron-Umgebungen
            static::dispatchSync($payload);
        } else {
            // Asynchron: in die dedizierte Queue "freescout" einstellen
            static::dispatch($payload);
        }
    }

    /**
     * Verarbeitet den Job und erstellt ein neues Gespräch in FreeScout.
     */
    public function handle(): void
    {
        $config = config('services.freescout');

        if (empty($config['url']) || empty($config['api_key']) || empty($config['mailbox_id'])) {
            Log::warning('SendFreeScoutTicket: FreeScout ist nicht konfiguriert – Job wird übersprungen.', [
                'payload_subject' => $this->payload['subject'] ?? null,
            ]);
            return;
        }

        $thread = $this->buildThread();
        $body   = $this->buildRequestBody($thread);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ])->post(
            rtrim($config['url'], '/') . '/api/conversations?api_key=' . urlencode($config['api_key']),
            $body
        );

        if ($response->failed()) {
            Log::error('SendFreeScoutTicket: API-Anfrage fehlgeschlagen.', [
                'status'  => $response->status(),
                'body'    => $response->body(),
                'subject' => $this->payload['subject'] ?? null,
            ]);

            // Fehler weitergeben, damit der Queue-Retry greift
            throw new \RuntimeException(
                'FreeScout API error ' . $response->status() . ': ' . $response->body()
            );
        }

        Log::info('SendFreeScoutTicket: Ticket erfolgreich erstellt.', [
            'conversation_id' => data_get($response->json(), 'id'),
            'subject'         => $this->payload['subject'] ?? null,
        ]);
    }

    /**
     * Baut den Thread-Eintrag (erster Kommentar des Gesprächs).
     */
    protected function buildThread(): array
    {
        $contextLines = [];

        if (!empty($this->payload['page_url'])) {
            $contextLines[] = '**Seiten-URL:** ' . $this->payload['page_url'];
        }

        if (!empty($this->payload['user_role'])) {
            $contextLines[] = '**Rolle:** ' . $this->payload['user_role'];
        }

        $contextBlock = !empty($contextLines)
            ? "\n\n---\n" . implode("\n", $contextLines)
            : '';

        $thread = [
            'type' => 'customer',
            'text' => $this->payload['message'] . $contextBlock,
        ];

        // Screenshot als Anhang integrieren
        if (!empty($this->payload['screenshot'])) {
            // Base64-String bereinigen (Data-URL-Präfix entfernen, falls vorhanden)
            $base64Data = preg_replace('/^data:image\/\w+;base64,/', '', $this->payload['screenshot']);

            $thread['attachments'] = [
                [
                    'fileName' => 'screenshot-' . now()->format('Y-m-d_H-i-s') . '.png',
                    'mimeType' => 'image/png',
                    'data'     => $base64Data,
                ],
            ];
        }

        return $thread;
    }

    /**
     * Erstellt den vollständigen Request-Body für die FreeScout-API.
     */
    protected function buildRequestBody(array $thread): array
    {
        $nameParts = explode(' ', $this->payload['user_name'] ?? '', 2);

        return [
            'mailboxId' => (int) config('services.freescout.mailbox_id'),
            'type'      => 'email',
            'status'    => 'active',
            'subject'   => $this->payload['subject'] ?? 'Support-Anfrage',
            'customer'  => [
                'email'     => $this->payload['user_email'] ?? '',
                'firstName' => $nameParts[0] ?? '',
                'lastName'  => $nameParts[1] ?? '',
            ],
            'threads'   => [$thread],
        ];
    }

    /**
     * Wird aufgerufen, wenn alle Versuche fehlschlagen.
     */
    public function failed(Throwable $exception): void
    {
        Log::critical('SendFreeScoutTicket: Job endgültig fehlgeschlagen.', [
            'subject'   => $this->payload['subject'] ?? null,
            'exception' => $exception->getMessage(),
        ]);
    }
}


