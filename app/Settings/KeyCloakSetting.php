<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Konfiguration für die Keycloak / UCS-OIDC-Integration.
 *
 * Diese Settings werden für den Socialite-Driver „ucs" (Alias auf Keycloak)
 * genutzt und über den Admin-Tab „OIDC / Keycloak" verwaltet.
 *
 * Spatie-Settings-Gruppe: keycloak
 *
 * @see database/settings/2024_11_23_140342_create_key_cloak_settings.php
 * @see docs/ucs-kelvin-integration-konzept.md §6.1, §14.9
 */
class KeyCloakSetting extends Settings
{
    /** Keycloak / OIDC-Integration aktiv/inaktiv */
    public bool $enabled;

    /** OAuth2 Client-ID */
    public ?string $client_id;

    /** OAuth2 Client-Secret */
    public ?string $client_secret;

    /** Keycloak-Realm, z. B. "ucs" oder "master" */
    public ?string $realm;

    /** Callback-URI nach erfolgreichem OIDC-Login */
    public ?string $redirect_uri;

    /** Basis-URL des Keycloak-/Konnect-Servers, z. B. "https://auth.example.de" */
    public ?string $base_url;

    /** Erlaubte E-Mail-Domains (Komma-getrennt, "*" = alle) */
    public ?string $maildomain;

    public static function group(): string
    {
        return 'keycloak';
    }
}

