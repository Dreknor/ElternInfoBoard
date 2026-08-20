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

    public int $pflichtstunden_anzahl_ermaessigt = 10;

    public float $pflichtstunden_betrag_ermaessigt = 12.5;

    public bool $konto_uebertrag_aktiv = false;

    public ?float $konto_uebertrag_max_stunden = null;

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
