<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class PflichtstundenSetting extends Settings
{

    public string $pflichtstunden_start;
    public string $pflichtstunden_ende;

    public string $pflichtstunden_text;

    public int $pflichtstunden_anzahl;

    public bool $listen_autocreate;

    public static function group(): string
    {
        return 'pflichtstunden';
    }
}
