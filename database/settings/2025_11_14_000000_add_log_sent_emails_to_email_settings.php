<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('email.log_sent_emails', false);
    }

    public function down(): void
    {
        $this->migrator->delete('email.log_sent_emails');
    }
};

