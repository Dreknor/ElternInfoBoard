<?php

namespace App\Providers;

use App\Settings\EmailSetting;
use App\Settings\KeycloakSetting;
use App\Support\MailConfigurator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Skip settings loading in testing environment
        if (app()->environment('testing')) {
            return;
        }

        try {
            // Priorität: EmailSetting (DB) → ENV → Default (aus mail.php)
            MailConfigurator::apply(app(EmailSetting::class));
        } catch (\Exception $e) {
            Log::error('Setting Email failed: '.$e->getMessage());
            // Bei Fehler werden die Werte aus ENV bzw. Defaults verwendet
        }

        try {
            $keycloakSetting = app(KeycloakSetting::class);

            // Priorität: KeycloakSetting (DB) → ENV → Default (aus services.php)
            app('config')->set('services.keycloak.enabled', $keycloakSetting->enabled);

            if (!empty($keycloakSetting->client_id)) {
                app('config')->set('services.keycloak.client_id', $keycloakSetting->client_id);
            }

            if (!empty($keycloakSetting->client_secret)) {
                app('config')->set('services.keycloak.client_secret', $keycloakSetting->client_secret);
            }

            if (!empty($keycloakSetting->realm)) {
                app('config')->set('services.keycloak.realms', $keycloakSetting->realm);
            }

            if (!empty($keycloakSetting->redirect_uri)) {
                app('config')->set('services.keycloak.redirect', $keycloakSetting->redirect_uri);
            }

            if (!empty($keycloakSetting->base_url)) {
                app('config')->set('services.keycloak.base_url', $keycloakSetting->base_url);
            }

            if (!empty($keycloakSetting->maildomain)) {
                app('config')->set('services.keycloak.mail_domain', $keycloakSetting->maildomain);
            }
        } catch (\Exception $e) {
            Log::error('Setting Keycloak failed: '.$e->getMessage());
            // Bei Fehler werden die Werte aus ENV bzw. Defaults verwendet
        }

    }
}
