<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

class AddLoginTrackingModeToGeneralSettings extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.login_tracking_mode', 'user');
    }

    public function down(): void
    {
        $this->migrator->delete('general.login_tracking_mode');
    }
}
