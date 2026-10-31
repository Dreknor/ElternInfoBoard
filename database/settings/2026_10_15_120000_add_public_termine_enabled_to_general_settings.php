<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Standard true: bisheriges Verhalten (öffentliche Termine erlaubt) bleibt erhalten.
        $this->migrator->add('general.public_termine_enabled', true);
    }

    public function down(): void
    {
        $this->migrator->delete('general.public_termine_enabled');
    }
};
