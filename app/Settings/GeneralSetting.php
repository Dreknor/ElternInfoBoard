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
     * Darstellung des Seitenmenüs (siehe config/navigation.php).
     * 'classic' – flache Liste aller Einträge (bisheriges Verhalten)
     * 'compact' – gruppiertes, einklappbares Menü mit Filterfeld
     */
    public string $nav_style = 'classic';

    /** Dürfen Nutzer die Menü-Darstellung selbst wählen? */
    public bool $allow_user_nav_style = true;

    /**
     * Steuert das globale Last-Login-Tracking.
     * 'user'   – Nutzer entscheidet selbst (bisheriges Verhalten)
     * 'always' – immer aufzeichnen, unabhängig von der Nutzereinstellung
     * 'never'  – niemals aufzeichnen, Nutzereinstellung wird ignoriert
     */
    public string $login_tracking_mode = 'user';

    /**
     * Rollen, die vor automatischem/massenhaftem Löschen geschützt sind
     * (Massenlöschung, Schuljahreswechsel-Bereinigung, Inaktivitäts-Cleanup).
     *
     * @var array<int, string>
     */
    public array $protected_roles = [];

    /**
     * Dürfen Termine als „öffentlich“ markiert und ohne Anmeldung veröffentlicht werden
     * (ICS-Feed und HTML-Seite)? Bei false wird die Option beim Anlegen ausgeblendet.
     */
    public bool $public_termine_enabled = true;

    public static function group(): string
    {
        return 'general';
    }
}
