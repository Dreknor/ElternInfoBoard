<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class PflichtstundenSetting extends Settings
{
    public string $pflichtstunden_start;

    public string $pflichtstunden_ende;

    public string $pflichtstunden_text;

    public int $pflichtstunden_anzahl;

    public float $pflichtstunden_betrag;

    public bool $listen_autocreate;

    public bool $gamification_show_progress = true;

    public bool $gamification_show_ranking = true;

    public bool $gamification_show_comparison = true;

    public array $pflichtstunden_bereiche = [];

    public static function group(): string
    {
        return 'pflichtstunden';
    }
}
