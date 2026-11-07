<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class KeycloakSetting extends Settings
{
    public bool $enabled;

    public ?string $client_id;

    public ?string $client_secret;

    public ?string $realm;

    public ?string $redirect_uri;

    public ?string $base_url;

    public ?string $maildomain;

    // Rollen im SSO-Token, an denen Schülerinnen und Schüler erkannt werden
    // (kommagetrennt, z. B. "schueler,student"). Leer = keine Erkennung.
    // Konten mit diesen Rollen werden nie als normale Konten angemeldet oder angelegt (S-02).
    public ?string $student_roles;

    // Unbekannte SSO-Nutzer automatisch als Konto anlegen (bisheriges Verhalten).
    public bool $auto_create_users;

    public static function group(): string
    {
        return 'keycloak';
    }
}

