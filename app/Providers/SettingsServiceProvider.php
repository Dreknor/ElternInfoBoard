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

            // Nur Werte überschreiben, die in den EmailSettings gesetzt sind
            // Priorität: EmailSetting (DB) → ENV → Default (aus mail.php)

            if (!empty($emailSetting->mail_server)) {
                app('config')->set('mail.mailers.smtp.host', $emailSetting->mail_server);
            }

            if (!empty($emailSetting->mail_port)) {
                app('config')->set('mail.mailers.smtp.port', $emailSetting->mail_port);
            }

            if (!empty($emailSetting->mail_username)) {
                app('config')->set('mail.mailers.smtp.username', $emailSetting->mail_username);
            }

            if (!empty($emailSetting->mail_password)) {
                app('config')->set('mail.mailers.smtp.password', $emailSetting->mail_password);
            }

            if (!empty($emailSetting->mail_encryption)) {
                app('config')->set('mail.mailers.smtp.encryption', $emailSetting->mail_encryption);
            }

            if (!empty($emailSetting->mail_from_address)) {
                app('config')->set('mail.from.address', $emailSetting->mail_from_address);
            }

            if (!empty($emailSetting->mail_from_name)) {
                app('config')->set('mail.from.name', $emailSetting->mail_from_name);
            }
        } catch (\Exception $e) {
            Log::error('Setting Email failed: '.$e->getMessage());
            // Bei Fehler werden die Werte aus ENV bzw. Defaults verwendet
        }

    }
}
