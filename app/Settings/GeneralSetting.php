<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSetting extends Settings
{
    public string $app_name;

    public string $logo;

    public string $favicon;

    /** Globaler Standard-Theme-Identifier (siehe app/Themes/*) */
    public string $default_theme = 'default';

    /** Dürfen Nutzer einen eigenen Theme wählen? */
    public bool $allow_user_theme = true;

    /**
     * Steuert das globale Last-Login-Tracking.
     * 'user'   – Nutzer entscheidet selbst (bisheriges Verhalten)
     * 'always' – immer aufzeichnen, unabhängig von der Nutzereinstellung
     * 'never'  – niemals aufzeichnen, Nutzereinstellung wird ignoriert
     */
    public string $login_tracking_mode = 'user';

    public static function group(): string
    {
        return 'general';
    }
}
