<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        try {
            $this->migrator->add('email.log_sent_emails', true);
        } catch (\Throwable $th) {
            return;
        }
    }

    public function down(): void
    {
        $this->migrator->delete('email.log_sent_emails');
    }
};
