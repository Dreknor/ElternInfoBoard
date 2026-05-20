<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class CustomThemeSetting extends Settings
{
    /** Anzeigename des eigenen Designs */
    public string $name = 'Eigenes Design';

    /** Kurze Beschreibung */
    public string $description = 'Individuell angepasstes Design';

    /** Alle überschriebenen CSS Custom Properties */
    public array $variables = [];

    public static function group(): string
    {
        return 'custom_theme';
    }
}

