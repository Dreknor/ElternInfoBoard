<?php

namespace App\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

/**
 * UcsServiceProvider
 *
 * Registriert alles, was für die UCS@school / Kelvin-Integration
 * beim Application-Boot benötigt wird:
 *
 * - Socialite-Driver „ucs" (Alias auf Keycloak) → Paket 06
 * - Spiegeln von UcsSetting in config() für Bibliotheken → §14.3
 *
 * @see \App\Settings\UcsSetting
 * @see docs/ucs-kelvin-integration-konzept.md §14.3
 */
class UcsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Kein spezifisches Binding nötig.
        // KelvinClient und UcsSyncService werden über den DI-Container aufgelöst.
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // In der Testing-Umgebung Settings-Zugriff überspringen,
        // um Datenbankabhängigkeiten in Unit-Tests zu vermeiden.
        if ($this->app->environment('testing')) {
            return;
        }

        try {
            /** @var \App\Settings\UcsSetting $s */
            $s = $this->app->make(\App\Settings\UcsSetting::class);

            // UcsSetting in config() spiegeln, damit Bibliotheken (z. B.
            // Queue-Worker ohne DI) config('ucs.*') lesen können.
            // Single Source of Truth bleibt UcsSetting (§14.3).
            config([
                'ucs.kelvin.base_url'  => $s->kelvin_base_url,
                'ucs.kelvin.username'  => $s->kelvin_username,
                'ucs.kelvin.password'  => $s->kelvin_password,
                'ucs.kelvin.page_size' => $s->kelvin_page_size,
                'ucs.kelvin.timeout'   => $s->kelvin_timeout,
                'ucs.kelvin.token_ttl' => $s->kelvin_token_ttl,
                'ucs.school'           => $s->school,
                'ucs.sync.enabled'     => $s->sync_enabled,
                'ucs.sync.cron'        => $s->sync_cron,
            ]);
        } catch (\Exception $e) {
            Log::channel('ucs')->warning(
                'UcsServiceProvider: UcsSetting konnte nicht geladen werden – '
                . $e->getMessage()
            );
        }

        // -----------------------------------------------------------------
        // Socialite-Driver "ucs" registrieren (Alias auf Keycloak)
        // Vollständige Implementierung → Paket 06 (OIDC-Login)
        // Hier wird der Driver vorab registriert, damit
        // Socialite::driver('ucs') keinen „Driver not supported"-Fehler
        // wirft, falls der Driver noch nicht vollständig gemappt wurde.
        // -----------------------------------------------------------------
        $this->registerUcsSocialiteDriver();
    }

    /**
     * Socialite-Driver „ucs" als Alias auf den Keycloak-Driver registrieren.
     *
     * Die eigentliche OIDC-Konfiguration (client_id, secret, redirect …)
     * wird in Paket 06 aus KeycloakSetting befüllt.
     *
     * @see docs/todos/06-oidc-login.md
     */
    protected function registerUcsSocialiteDriver(): void
    {
        // Benötigt SocialiteProviders/Keycloak.
        // Driver wird nur registriert, wenn die Keycloak-Provider-Klasse existiert.
        if (! class_exists(\SocialiteProviders\Keycloak\Provider::class)) {
            return;
        }

        $this->app['events']->listen(
            \SocialiteProviders\Manager\SocialiteWasCalled::class,
            function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
                // Paket 06 implementiert einen dedizierten UCS-Driver.
                // Bis dahin: Keycloak-Provider unter dem Alias „ucs" registrieren.
                $event->extendSocialite('ucs', \SocialiteProviders\Keycloak\Provider::class);
            }
        );
    }
}

