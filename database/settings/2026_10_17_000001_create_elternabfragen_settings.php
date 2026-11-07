<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Standardregeln des Moduls „Elternabfragen“ (Konzept Kap. 7.3, 8, 9).
 */
return new class extends SettingsMigration
{
    private const DEFAULTS = [
        'elternabfragen.first_reminder_after_hours' => 48,
        'elternabfragen.reminder_interval_hours' => 24,
        'elternabfragen.reminder_days_before_deadline' => 2,
        'elternabfragen.max_reminders' => 3,
        'elternabfragen.correction_window_days' => 7,
        'elternabfragen.export_expiry_days' => 7,
        'elternabfragen.default_retention_days' => 365,
        'elternabfragen.four_eyes' => true,
        'elternabfragen.upload_max_kb' => 10240,
    ];

    public function up(): void
    {
        foreach (self::DEFAULTS as $key => $value) {
            if (! $this->migrator->exists($key)) {
                $this->migrator->add($key, $value);
            }
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::DEFAULTS) as $key) {
            $this->migrator->deleteIfExists($key);
        }
    }
};
