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

    public static function group(): string
    {
        return 'general';
    }
}
