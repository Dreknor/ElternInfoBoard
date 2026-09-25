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

    /** Berechnungsgrundlage: 'family' (Soll pro Familie) oder 'child' (Soll pro Kind). */
    public string $pflichtstunden_basis = 'family';

    /** Nur bei Basis 'child': höchstens so viele Kinder zählen (null = unbegrenzt). */
    public ?int $pflichtstunden_max_kinder = null;

    /** Umgang mit Kindern mehrerer Familien: 'combined', 'split' oder 'separate'. */
    public string $pflichtstunden_geteilte_kinder = 'separate';

    /** Nur Kinder dieser Gruppen/Klassen zählen (leer = alle aktiven Kinder). */
    public array $pflichtstunden_kinder_gruppen = [];

    /** Protokoll der letzten Umstellung der Berechnungsgrundlage (E8). */
    public ?string $pflichtstunden_basis_changed_at = null;

    public ?int $pflichtstunden_basis_changed_by = null;

    public static function group(): string
    {
        return 'pflichtstunden';
    }
}
