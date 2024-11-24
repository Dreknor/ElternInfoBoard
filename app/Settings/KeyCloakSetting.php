<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class KeyCloakSetting extends Settings
{
    public bool $enabled;

    public string $client_id;

    public string $client_secret;

    public string $realm;

    public string $redirect_uri;

    public string $base_url;
    public string $maildomain;

    public static function group(): string
    {
        return 'keycloak';
    }
}
