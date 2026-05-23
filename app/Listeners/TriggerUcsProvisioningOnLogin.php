<?php

namespace App\Listeners;

use App\Jobs\SyncSingleUcsParentJob;
use App\Model\User;
use App\Settings\UcsSetting;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;

/**
 * Löst nach jedem direkten Login (Passwort / Magic Link / Passwordless)
 * einen asynchronen UCS-JIT-Sync für den eingeloggten Nutzer aus,
 * sofern er einen ucs_username besitzt und UCS aktiviert ist.
 *
 * Der Job wird nach dem Response dispatcht (non-blocking), damit der
 * Login-Vorgang selbst nicht verzögert wird.
 *
 * Intentfälle (§6.4):
 *   – Nutzer wurde per Nacht-Sync angelegt (ucs_username gesetzt) und
 *     loggt sich erstmals mit Passwort ein → frische Gruppen/Kinder-Daten.
 *   – Nutzer hat sein Passwort lokal gesetzt und nutzt UCS nicht für SSO,
 *     aber seine Klassenzugehörigkeit ändert sich → bleibt aktuell.
 *
 * Nicht ausgelöst via OIDC-Callback (dort greift UcsLoginController direkt).
 *
 * @see App\Jobs\SyncSingleUcsParentJob
 * @see docs/ucs-kelvin-integration-konzept.md §6.4
 */
class TriggerUcsProvisioningOnLogin
{
    /**
     * Event-Handler.
     *
     * Prüfungen vor dem Dispatch:
     *   1. UcsSetting::enabled + on_login_fallback
     *   2. User hat ucs_username (sonst kein JIT-Sync möglich)
     *   3. Kein Dispatch, wenn Login bereits via OIDC-Callback stattfand
     *      (Session-Marker 'ucs_id_token' ist dann gesetzt)
     */
    public function handle(Login $event): void
    {
        /** @var User $user */
        $user = $event->user;

        // Nur Users mit gesetztem ucs_username können JIT-gesyncted werden
        if (empty($user->ucs_username)) {
            return;
        }

        // Nicht nochmal dispatchen, wenn der Login via UCS-OIDC kam
        // (session 'ucs_id_token' ist in diesem Fall bereits gesetzt)
        if (session()->has('ucs_id_token')) {
            return;
        }

        try {
            /** @var UcsSetting $settings */
            $settings = app(UcsSetting::class);

            if (! $settings->enabled || ! $settings->on_login_fallback) {
                return;
            }
        } catch (\Throwable $e) {
            // UcsSetting noch nicht verfügbar (z. B. frische Migration)
            Log::channel('ucs')->debug('[TriggerUcsProvisioningOnLogin] UcsSetting nicht verfügbar', [
                'error' => $e->getMessage(),
            ]);

            return;
        }

        Log::channel('ucs')->info('[TriggerUcsProvisioningOnLogin] JIT-Sync nach Direkt-Login dispatcht', [
            'user_id'      => $user->id,
            'ucs_username' => $user->ucs_username,
        ]);

        // Non-blocking: nach dem Response dispatchen
        SyncSingleUcsParentJob::dispatchAfterResponse(
            $user->ucs_username,
            $user->id,
        );
    }
}



