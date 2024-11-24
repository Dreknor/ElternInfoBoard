<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('email.mail_server', env('MAIL_HOST', "null"));
        $this->migrator->add('email.mail_port', env('MAIL_PORT', "587"));
        $this->migrator->add('email.mail_username', env('MAIL_USERNAME', "null"));
        $this->migrator->add('email.mail_password', env('MAIL_PASSWORD', "null"));
        $this->migrator->add('email.mail_encryption', env('MAIL_ENCRYPTION', "tls"));
        $this->migrator->add('email.mail_from_address', env('MAIL_FROM_ADDRESS', "null"));
        $this->migrator->add('email.mail_from_name', env('MAIL_FROM_NAME', "null"));


    }
};
