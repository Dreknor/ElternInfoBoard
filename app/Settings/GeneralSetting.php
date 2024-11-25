<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSetting extends Settings
{

    public string $app_name;

    public string $logo;

    public string $favicon;

    public static function group(): string
    {
        return 'general';
    }
}
