<?php

namespace App\Jobs;

use App\Services\Ucs\UcsSyncService;
use App\Settings\UcsSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Optionaler asynchroner Bulk-Sync-Job für UCS@school-Daten.
 *
 * HINWEIS: Der reguläre Scheduler nutzt diesen Job NICHT mehr direkt.
 * Stattdessen ruft der Scheduler `php artisan sync:ucs-parents` auf,
 * das UcsSyncService::run() synchron ausführt – kein Queue-Worker/Supervisor nötig,
 * funktioniert auch auf Shared-Hosting.
 *
 * Dieser Job bleibt erhalten für:
 *   – Manuelles asynchrones Dispatching:  SyncUcsSchoolJob::dispatch()
 *   – Notfall-Trigger aus einem Controller heraus
 *
 * – Queueable, idempotent
 * – Parameterfrei: Schule kommt aus UcsSetting
 * – Kein Retry (tries = 1), langer Timeout (900 s)
 * – failed() schreibt Status nach UcsSetting + captured Sentry
 *
 * @see docs/ucs-kelvin-integration-konzept.md §5.1, §5.4, §7.3
 */
class SyncUcsSchoolJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Maximale Laufzeit in Sekunden. */
    public int $timeout = 900;

    /** Kein automatischer Retry. */
    public int $tries = 1;

    /**
     * Konstruktor ohne Parameter – Schule wird aus UcsSetting gelesen.
     *
     * Queue: 'default' (Standard-Queue des Projekts).
     * Für asynchrones Dispatching: SyncUcsSchoolJob::dispatch()
     * Für synchrones Ausführen (Scheduler/CLI): php artisan sync:ucs-parents
     */
    public function __construct()
    {
        $this->onQueue('default');
    }

    /**
     * Job-Logik ausführen.
     *
     * Bricht ab, wenn die Integration oder der Sync deaktiviert ist.
     * Delegiert die eigentliche Arbeit an UcsSyncService::run().
     */
    public function handle(UcsSyncService $svc): void
    {
        /** @var UcsSetting $settings */
        $settings = app(UcsSetting::class);

        if (! $settings->enabled) {
            Log::channel('ucs')->info('[SyncUcsSchoolJob] Integration deaktiviert – Job abgebrochen.');

            return;
        }

        if (! $settings->sync_enabled) {
            Log::channel('ucs')->info('[SyncUcsSchoolJob] Sync deaktiviert (sync_enabled=false) – Job abgebrochen.');

            return;
        }

        Log::channel('ucs')->info('[SyncUcsSchoolJob] Starte Bulk-Sync.');

        $counts = $svc->run();

        Log::channel('ucs')->info('[SyncUcsSchoolJob] Bulk-Sync abgeschlossen.', $counts);
    }

    /**
     * Wird aufgerufen, wenn der Job endgültig fehlgeschlagen ist.
     *
     * Schreibt last_sync_status='failed' und erfasst die Exception in Sentry.
     */
    public function failed(Throwable $e): void
    {
        Log::channel('ucs')->error('[SyncUcsSchoolJob] Job failed.', [
            'error' => $e->getMessage(),
        ]);

        try {
            /** @var UcsSetting $settings */
            $settings = app(UcsSetting::class);
            $settings->last_sync_status  = 'failed';
            $settings->last_sync_message = mb_substr($e->getMessage(), 0, 200);
            $settings->save();
        } catch (Throwable) {
            // Settings-Speicherung darf den Fehlerfluss nicht blockieren
        }

        if (app()->bound('sentry')) {
            app('sentry')->captureException($e);
        }
    }
}

