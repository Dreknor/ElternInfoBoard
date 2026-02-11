<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class StundenplanSetting extends Settings
{
    public string $import_api_key;

    public string $import_api_url;

    public bool $allow_web_import;

    public bool $allow_api_import;

    public static function group(): string
    {
        return 'stundenplan';
    }
}

