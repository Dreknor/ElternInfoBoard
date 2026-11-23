<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Globale Einstellungen zu den eigenen Skripten.
 *
 * Die Skripte selbst liegen in der Tabelle `custom_scripts` (Model CustomScript).
 * Hier bleibt nur der Chat-Button der Eltern-App.
 */
class CustomScriptSetting extends Settings
{
    /** Chat in der Eltern-App anbieten (Button öffnet /chat im In-App-Browser). */
    public bool $app_enabled = false;

    /** Beschriftung des Buttons in der App. */
    public string $app_label = 'Chatbot';

    public static function group(): string
    {
        return 'custom_scripts';
    }
}
