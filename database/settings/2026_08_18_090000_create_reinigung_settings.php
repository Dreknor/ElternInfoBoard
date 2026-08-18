<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('reinigung.separate_bereiche', true);
        $this->migrator->add('reinigung.combined_exclude_bereiche', []);
        $this->migrator->add('reinigung.skip_holidays', false);
        $this->migrator->add('reinigung.reminder_enabled', false);
        $this->migrator->add('reinigung.reminder_days_before', 3);
        $this->migrator->add('reinigung.reminder_email', true);
        $this->migrator->add('reinigung.reminder_push', true);
        $this->migrator->add('reinigung.reminder_time', '08:00');
    }

    public function down(): void
    {
        $this->migrator->delete('reinigung.separate_bereiche');
        $this->migrator->delete('reinigung.combined_exclude_bereiche');
        $this->migrator->delete('reinigung.skip_holidays');
        $this->migrator->delete('reinigung.reminder_enabled');
        $this->migrator->delete('reinigung.reminder_days_before');
        $this->migrator->delete('reinigung.reminder_email');
        $this->migrator->delete('reinigung.reminder_push');
        $this->migrator->delete('reinigung.reminder_time');
    }
};
