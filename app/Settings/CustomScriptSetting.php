<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Instanzspezifisches HTML/JavaScript-Snippet (z. B. Chatbot-Widget).
 * Wird nur ausgegeben, wenn config('custom_scripts.enabled') gesetzt ist.
 */
class CustomScriptSetting extends Settings
{
    /** Snippet wird ausgegeben (zusätzlich zur Instanz-Freischaltung). */
    public bool $active = false;

    /** Das Snippet, so wie es der Anbieter liefert (z. B. <script …></script>). */
    public string $snippet = '';

    /** Ausgabeort: 'head' (Ende von <head>) oder 'body' (Ende von <body>). */
    public string $position = 'body';

    /** Auch für nicht angemeldete Besucher (Login, Passwort vergessen …) ausgeben. */
    public bool $show_for_guests = false;

    /** Chat in der Eltern-App anbieten (Button, öffnet /chat bzw. app_url im In-App-Browser). */
    public bool $app_enabled = false;

    /** Beschriftung des Buttons in der App. */
    public string $app_label = 'Chat';

    /** Optional: eigene Chat-Seite des Anbieters (https). Leer = /chat dieser Instanz. */
    public ?string $app_url = null;

    /** Wer das Snippet zuletzt geändert hat (User-ID) und wann (ISO-8601). */
    public ?int $updated_by = null;

    public ?string $updated_at = null;

    public static function group(): string
    {
        return 'custom_scripts';
    }
}
