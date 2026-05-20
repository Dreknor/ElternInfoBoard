<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.default_theme', 'default');
        $this->migrator->add('general.allow_user_theme', true);
    }

    public function down(): void
    {
        $this->migrator->delete('general.default_theme');
        $this->migrator->delete('general.allow_user_theme');
    }
};

