<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('email.mail_server', env('MAIL_HOST', "example.com")) ?? "example.com";
        $this->migrator->add('email.mail_port', env('MAIL_PORT', "587")) ?? 587;
        $this->migrator->add('email.mail_username', env('MAIL_USERNAME', "info@example.com") ?? "info@example.com");
        $this->migrator->add('email.mail_password', env('MAIL_PASSWORD', "null") ?? now()->timestamp);
        $this->migrator->add('email.mail_encryption', config('mail.mailers.smtp.encryption') ?? "tls");
        $this->migrator->add('email.mail_from_address', env('MAIL_FROM_ADDRESS') ?? "info@example.com");
        $this->migrator->add('email.mail_from_name', env('MAIL_FROM_NAME') ?? "ElternInfoBoard");
    }
};
