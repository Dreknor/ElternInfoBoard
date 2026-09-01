<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('schicken.schicken_erlaube_zeitraum', true);
    }

    public function down(): void
    {
        $this->migrator->delete('schicken.schicken_erlaube_zeitraum');
    }
};
