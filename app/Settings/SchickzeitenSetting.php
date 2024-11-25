<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SchickzeitenSetting extends Settings
{

    public string $schicken_ab;

    public string $schicken_bis;

    public string $schicken_text;

    public int $schicken_intervall;


    public static function group(): string
    {
        return 'schicken';
    }
}
