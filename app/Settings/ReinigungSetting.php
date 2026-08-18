<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class ReinigungSetting extends Settings
{
    // Bereichs-Steuerung: sollen Reinigungspläne getrennt nach Gruppenbereichen
    // geführt werden, oder als ein gemeinsamer Plan für die gesamte Einrichtung?
    // Sind bei den Gruppen gar keine Bereiche gepflegt, wird unabhängig von dieser
    // Einstellung automatisch der gemeinsame Modus verwendet (siehe ReinigungController).
    public bool $separate_bereiche;

    // Bereiche (Werte aus Group.bereich), deren Nutzer im gemeinsamen Modus NICHT
    // in den gemeinsamen Reinigungsplan/Verteilungspool aufgenommen werden sollen.
    public array $combined_exclude_bereiche;

    // Ferien-Ausschluss beim automatischen Befüllen (nutzt HolidayService/CareSetting.bundesland)
    public bool $skip_holidays;

    // Erinnerung vor dem eigenen Reinigungseinsatz
    public bool $reminder_enabled;

    public int $reminder_days_before;

    public bool $reminder_email;

    public bool $reminder_push;

    public string $reminder_time;

    public static function group(): string
    {
        return 'reinigung';
    }
}
