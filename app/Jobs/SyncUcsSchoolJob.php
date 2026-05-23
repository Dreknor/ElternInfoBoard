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
 * Asynchroner Bulk-Sync-Job für UCS@school-Daten.
 *
 * – Queueable, idempotent, nur ein gleichzeitig aktives Exemplar (onOneServer/withoutOverlapping
 *   wird über den Scheduler gesetzt, kann aber auch Job-seitig per ShouldBeUnique umgesetzt
 *   werden – hier über ShouldQueue ohne ShouldBeUnique, da withoutOverlapping im Scheduler steht)
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
     * Für einen eigenen Worker kann beim Dispatch onQueue('ucs') gesetzt werden:
     *   SyncUcsSchoolJob::dispatch()->onQueue('ucs')
     * Dann ist ein eigener Supervisor-Worker für die 'ucs'-Queue
     * erforderlich (vgl. docs/todos/10-rollout-und-deployment.md).
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

