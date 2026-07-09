<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('email.contact_default_name', 'Sekretariat');
        $this->migrator->add('email.contact_default_email', null);
    }
};
