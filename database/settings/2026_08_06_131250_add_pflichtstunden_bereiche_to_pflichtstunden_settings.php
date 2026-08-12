<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        try {
            $this->migrator->add('pflichtstunden.pflichtstunden_bereiche', []);
        } catch (\Spatie\LaravelSettings\Exceptions\SettingAlreadyExists $e) {
            // Setting already exists, do nothing
            \Illuminate\Support\Facades\Log::info('Setting already exists: ' . $e->getMessage());
        }
    }

    public function down(): void
    {
        $this->migrator->delete('pflichtstunden.pflichtstunden_bereiche');
    }
};
