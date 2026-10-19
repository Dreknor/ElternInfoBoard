<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    private const KEYS = [
        'general.public_termine_location_name',
        'general.public_termine_location_street',
        'general.public_termine_location_postal_code',
        'general.public_termine_location_city',
    ];

    public function up(): void
    {
        foreach (self::KEYS as $key) {
            $this->migrator->add($key, null);
        }
    }

    public function down(): void
    {
        foreach (self::KEYS as $key) {
            $this->migrator->delete($key);
        }
    }
};
