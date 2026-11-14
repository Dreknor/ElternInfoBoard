<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Standardregeln für Elternabfragen (Erinnerungen, Korrekturen, Export, Aufbewahrung).
 *
 * @see docs/elternabfragen-konzept.md §6
 */
class ElternabfragenSetting extends Settings
{
    /** Erste Erinnerung frühestens nach … Stunden seit der Benachrichtigung. */
    public int $first_reminder_after_hours;

    /** Weitere Erinnerungen höchstens einmal innerhalb von … Stunden. */
    public int $reminder_interval_hours;

    /** Automatische Erinnerung … Tage vor Fristende (0 = aus). */
    public int $reminder_days_before_deadline;

    /** Höchstzahl an Erinnerungen je Kind und Abfrage. */
    public int $max_reminders;

    /** Standard-Zeitfenster, in dem Eltern ihre Antwort selbst korrigieren dürfen. */
    public int $correction_window_days;

    /** Exportdateien werden nach … Tagen gelöscht. */
    public int $export_expiry_days;

    /** Standard-Aufbewahrungsfrist nach Abschluss einer Abfrage (Tage). */
    public int $default_retention_days;

    /** Vier-Augen-Prinzip: Freigabe nur durch eine andere Person als die erstellende. */
    public bool $four_eyes;

    /** Maximale Größe eines Datei-Uploads (KB). */
    public int $upload_max_kb;

    public static function group(): string
    {
        return 'elternabfragen';
    }
}
