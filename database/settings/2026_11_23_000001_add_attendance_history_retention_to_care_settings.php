<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('care.attendance_history_months', 6);
    }

    public function down(): void
    {
        $this->migrator->delete('care.attendance_history_months');
    }
};
