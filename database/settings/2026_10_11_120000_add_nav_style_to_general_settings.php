<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.nav_style', 'classic');
        $this->migrator->add('general.allow_user_nav_style', true);
    }

    public function down(): void
    {
        $this->migrator->delete('general.nav_style');
        $this->migrator->delete('general.allow_user_nav_style');
    }
};
