<?php

namespace App\Support;

use App\Settings\EmailSetting;
use Illuminate\Support\Facades\Mail;

/**
 * Überträgt die in der Datenbank gepflegten SMTP-Einstellungen in die Laravel-Mail-Konfiguration.
 *
 * Ist ein SMTP-Server hinterlegt, wird der SMTP-Mailer erzwungen. Andernfalls würde ein in der
 * .env gesetztes MAIL_MAILER=sendmail (z. B. auf Shared Hosting) bzw. MAIL_URL die Einstellungen
 * stillschweigend umgehen und Mails über den lokalen MTA des Webservers verschicken.
 */
class MailConfigurator
{
    public static function apply(EmailSetting $settings): void
    {
        $config = app('config');

        if (! empty($settings->mail_server)) {
            $config->set('mail.default', 'smtp');
            $config->set('mail.mailers.smtp.transport', 'smtp');
            // MAIL_URL hätte Vorrang vor host/port – daher entfernen
            $config->set('mail.mailers.smtp.url', null);
            $config->set('mail.mailers.smtp.host', $settings->mail_server);
        }

        if (! empty($settings->mail_port)) {
            $config->set('mail.mailers.smtp.port', (int) $settings->mail_port);
        }

        if (! empty($settings->mail_username)) {
            $config->set('mail.mailers.smtp.username', $settings->mail_username);
        }

        if (! empty($settings->mail_password)) {
            $config->set('mail.mailers.smtp.password', $settings->mail_password);
        }

        if (! empty($settings->mail_encryption)) {
            $config->set('mail.mailers.smtp.encryption', $settings->mail_encryption);
            $config->set('mail.mailers.smtp.scheme', self::schemeFor($settings->mail_encryption));
        }

        if (! empty($settings->mail_from_address)) {
            $config->set('mail.from.address', $settings->mail_from_address);
        }

        if (! empty($settings->mail_from_name)) {
            $config->set('mail.from.name', $settings->mail_from_name);
        }

        // Bereits aufgelöste Mailer verwerfen, damit die neue Konfiguration greift
        if (app()->resolved('mail.manager')) {
            Mail::purge();
            Mail::purge('smtp');
        }
    }

    /**
     * Symfony Mailer kennt nur die Schemes "smtp" (STARTTLS, z. B. Port 587)
     * und "smtps" (implizites TLS, z. B. Port 465).
     */
    public static function schemeFor(?string $encryption): ?string
    {
        return match (strtolower(trim((string) $encryption))) {
            'ssl', 'smtps' => 'smtps',
            'tls', 'starttls', 'smtp' => 'smtp',
            default => null,
        };
    }
}
