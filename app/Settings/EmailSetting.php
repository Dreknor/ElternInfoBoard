<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class EmailSetting extends Settings
{
    public ?string $mail_server = null;

    public ?string $mail_port = null;

    public ?string $mail_username = null;

    /** Wird verschlüsselt in der Datenbank gespeichert (AES via Laravel encrypt()) */
    public ?string $mail_password = null;

    public ?string $mail_encryption = null;

    public ?string $mail_from_address = null;

    public ?string $mail_from_name = null;

    public ?string $new_user_welcome_text = null;

    /** Vereins-Import: Hinweis in der Zugangsdaten-Mail neuer Vereinskonten */
    public ?string $verein_access_notice = null;

    /** Vereins-Import: Benachrichtigung in der Anwendung bei Aufnahme bestehender Konten in den Verein */
    public ?string $verein_added_text = null;

    /** Vereins-Import: Benachrichtigung in der Anwendung bei Entfernung aus dem Verein */
    public ?string $verein_removed_text = null;

    public bool $log_sent_emails = false;

    public ?string $contact_default_name = 'Sekretariat';

    public ?string $contact_default_email = null;

    public static function group(): string
    {
        return 'email';
    }

    /**
     * Felder, die verschlüsselt in der Datenbank gespeichert werden.
     * Spatie Laravel Settings ver- und entschlüsselt automatisch.
     */
    public static function encrypted(): array
    {
        return [
            'mail_password',
        ];
    }
}
