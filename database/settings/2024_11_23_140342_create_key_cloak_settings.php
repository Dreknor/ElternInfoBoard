<?php

use App\Settings\GeneralSetting;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {


        $this->migrator->add('keycloak.enabled', false);
        $this->migrator->add('keycloak.client_id', config('app.keycloak.client_id', config('app.name')));
        $this->migrator->add('keycloak.client_secret', config('app.keycloak.client_secret'), 'SuperSecret');
        $this->migrator->add('keycloak.realm', config('app.keycloak.realm', 'master'));
        $this->migrator->add('keycloak.redirect_uri', config('app.keycloak.url', config('app.url', 'http://localhost')));
        $this->migrator->add('keycloak.base_url', config('app.keycloak.base_url', 'KeyCloack-Url'));
        $this->migrator->add('keycloak.maildomain', config('app.keycloak.maildomain', 'maildomain.com'));

    }
};
