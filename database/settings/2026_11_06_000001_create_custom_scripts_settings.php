<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('custom_scripts.active', false);
        $this->migrator->add('custom_scripts.snippet', '');
        $this->migrator->add('custom_scripts.position', 'body');
        $this->migrator->add('custom_scripts.show_for_guests', false);
        $this->migrator->add('custom_scripts.app_enabled', false);
        $this->migrator->add('custom_scripts.app_label', 'Chatbot');
        $this->migrator->add('custom_scripts.updated_by', null);
        $this->migrator->add('custom_scripts.updated_at', null);
    }

    public function down(): void
    {
        $this->migrator->delete('custom_scripts.active');
        $this->migrator->delete('custom_scripts.snippet');
        $this->migrator->delete('custom_scripts.position');
        $this->migrator->delete('custom_scripts.show_for_guests');
        $this->migrator->delete('custom_scripts.app_enabled');
        $this->migrator->delete('custom_scripts.app_label');
        $this->migrator->delete('custom_scripts.updated_by');
        $this->migrator->delete('custom_scripts.updated_at');
    }
};
