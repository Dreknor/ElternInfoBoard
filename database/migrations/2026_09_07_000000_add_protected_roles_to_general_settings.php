<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

class AddProtectedRolesToGeneralSettings extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.protected_roles', ['Administrator', 'Mitarbeiter', 'Schulbegleiter', 'Vereinsmitglied']);
    }

    public function down(): void
    {
        $this->migrator->delete('general.protected_roles');
    }
}
