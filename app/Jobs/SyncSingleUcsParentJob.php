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
 * JIT-Sync für einen einzelnen UCS-Elternaccount im Hintergrund.
 *
 * Wird nach einem direkten Login (Passwort / Magic Link) dispatcht,
 * wenn der Nutzer einen ucs_username besitzt und UCS aktiviert ist.
 * Läuft non-blocking nach dem Response (dispatchAfterResponse).
 *
 * Unterschied zu SyncUcsSchoolJob:
 *   – Betrifft nur EINEN Nutzer (nicht die gesamte Schule)
 *   – Kein withoutOverlapping / onOneServer
 *   – Kurzer Timeout (on_login_timeout aus UcsSetting, max. 30 s)
 *
 * @see App\Listeners\TriggerUcsProvisioningOnLogin
 * @see docs/ucs-kelvin-integration-konzept.md §6.4
 */
class SyncSingleUcsParentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Maximale Laufzeit: leicht über dem on_login_timeout. */
    public int $timeout = 60;

    /** Kein automatischer Retry. */
    public int $tries = 1;

    public function __construct(
        private readonly string $ucsUsername,
        private readonly int    $userId,
    ) {}

    public function handle(UcsSyncService $svc): void
    {
        /** @var UcsSetting $settings */
        $settings = app(UcsSetting::class);

        if (! $settings->enabled || ! $settings->on_login_fallback) {
            Log::channel('ucs')->debug('[SyncSingleUcsParentJob] Übersprungen (disabled)', [
                'user_id'      => $this->userId,
                'ucs_username' => $this->ucsUsername,
            ]);

            return;
        }

        Log::channel('ucs')->info('[SyncSingleUcsParentJob] JIT-Sync nach Direkt-Login', [
            'user_id'      => $this->userId,
            'ucs_username' => $this->ucsUsername,
        ]);

        try {
            $user = $svc->syncSingleParent($this->ucsUsername);

            Log::channel('ucs')->info('[SyncSingleUcsParentJob] Abgeschlossen', [
                'user_id'      => $this->userId,
                'ucs_username' => $this->ucsUsername,
                'synced'       => $user !== null,
            ]);
        } catch (Throwable $e) {
            Log::channel('ucs')->warning('[SyncSingleUcsParentJob] Fehler', [
                'user_id'      => $this->userId,
                'ucs_username' => $this->ucsUsername,
                'error'        => $e->getMessage(),
            ]);
            // Kein Re-Throw: Login des Nutzers darf nicht beeinträchtigt werden
        }
    }
}

