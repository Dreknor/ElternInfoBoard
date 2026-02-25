<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class EmailSetting extends Settings
{
    public ?string $mail_server = null;

    public ?string $mail_port = null;

    public ?string $mail_username = null;

    public ?string $mail_password = null;

    public ?string $mail_encryption = null;

    public ?string $mail_from_address = null;

    public ?string $mail_from_name = null;

    public ?string $new_user_welcome_text = null;

    public bool $log_sent_emails = false;

    public static function group(): string
    {
        return 'email';
    }
}
