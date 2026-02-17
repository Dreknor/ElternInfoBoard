<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Enabled flag - default false
        $this->migrator->add('keycloak.enabled', env('KEYCLOAK_ENABLED', false));

        // Use null as default to fallback to .env values
        $this->migrator->add('keycloak.client_id', env('KEYCLOAK_CLIENT_ID'));
        $this->migrator->add('keycloak.client_secret', env('KEYCLOAK_CLIENT_SECRET'));
        $this->migrator->add('keycloak.realm', env('KEYCLOAK_REALM', 'master'));
        $this->migrator->add('keycloak.redirect_uri', env('KEYCLOAK_REDIRECT_URI'));
        $this->migrator->add('keycloak.base_url', env('KEYCLOAK_BASE_URL'));
        $this->migrator->add('keycloak.maildomain', env('KEYCLOAK_MAILDOMAIN', '*'));
    }
};
