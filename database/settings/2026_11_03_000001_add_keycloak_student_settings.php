<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * SSO: Schülerrollen erkennen und automatische Kontoanlage abschaltbar machen
 * (docs/konzept-schuelerkonten.md, Paket S-02).
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->migrator->exists('keycloak.student_roles')) {
            $this->migrator->add('keycloak.student_roles', null);
        }
        if (! $this->migrator->exists('keycloak.auto_create_users')) {
            // Bisheriges Verhalten beibehalten
            $this->migrator->add('keycloak.auto_create_users', true);
        }
    }

    public function down(): void
    {
        foreach (['keycloak.student_roles', 'keycloak.auto_create_users'] as $name) {
            if ($this->migrator->exists($name)) {
                $this->migrator->delete($name);
            }
        }
    }
};
