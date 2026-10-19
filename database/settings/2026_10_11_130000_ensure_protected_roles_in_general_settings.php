<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Reparatur: Auf manchen Installationen ist die Migration
 * 2026_09_07_000000_add_protected_roles_to_general_settings als ausgeführt markiert,
 * der Eintrag general.protected_roles fehlt aber. Dann schlägt jedes Speichern von
 * GeneralSetting fehl ("properties were missing: protected_roles").
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->migrator->exists('general.protected_roles')) {
            $this->migrator->add('general.protected_roles', ['Administrator', 'Mitarbeiter', 'Schulbegleiter', 'Vereinsmitglied']);
        }
    }

    public function down(): void
    {
        // Bewusst leer: Der Eintrag gehört zur ursprünglichen Migration.
    }
};
