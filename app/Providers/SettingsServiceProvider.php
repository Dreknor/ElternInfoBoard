<?php

namespace App\Providers;

use App\Settings\EmailSetting;
use App\Settings\KeyCloakSetting;
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
            $emailSetting = app(EmailSetting::class);
            app('config')->set([
                'mail.mailers.smtp.host' => $emailSetting->mail_server,
                'mail.mailers.smtp.port' => $emailSetting->mail_port,
                'mail.mailers.smtp.username' => $emailSetting->mail_username,
                'mail.mailers.smtp.password' => $emailSetting->mail_password,
                'mail.mailers.smtp.encryption' => $emailSetting->mail_encryption,
                'mail.from.address' => $emailSetting->mail_from_address,
                'mail.from.name' => $emailSetting->mail_from_name,
            ]);
        } catch (\Exception $e) {
            Log::error('Setting Email failed: '.$e->getMessage());
        }

        try {
            $keyCloakSetting = app(KeyCloakSetting::class);
            app('config')->set([
                'keycloak.client_id' => $keyCloakSetting->client_id,
                'keycloak.client_secret' => $keyCloakSetting->client_secret,
                'keycloak.realm' => $keyCloakSetting->realm,
                'keycloak.redirect_uri' => $keyCloakSetting->redirect_uri != null ? $keyCloakSetting->redirect_uri : config('app.url').'/login/keycloak/callback',
                'keycloak.base_url' => $keyCloakSetting->base_url != null ? $keyCloakSetting->base_url : 'https://keycloak.example.com',
                'keycloak.enabled' => $keyCloakSetting->enabled ?? false,
            ]);
        } catch (\Exception $e) {
            Log::error('Setting Keycloak failed: '.$e->getMessage());
        }

    }
}
