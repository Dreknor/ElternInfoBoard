<?php

namespace App\Services\CustomScripts;

use App\Settings\CustomScriptSetting;
use Illuminate\Support\Facades\Log;

/**
 * Entscheidet, ob und wo das instanzspezifische Snippet ausgegeben wird.
 *
 * Ohne Instanz-Freischaltung (config custom_scripts.enabled) wird nichts
 * gelesen und nichts ausgegeben.
 */
class CustomScriptRenderer
{
    public const POSITIONS = ['head', 'body'];

    public static function enabled(): bool
    {
        return (bool) config('custom_scripts.enabled', false);
    }

    /**
     * Liefert das Snippet für den angegebenen Ausgabeort oder einen leeren String.
     */
    public function render(string $position): string
    {
        if (! self::enabled()) {
            return '';
        }

        try {
            $setting = app(CustomScriptSetting::class);
        } catch (\Throwable $e) {
            // z. B. Settings-Migration noch nicht gelaufen: Seite nicht kaputt machen
            Log::warning('Eigene Skripte konnten nicht geladen werden: '.$e->getMessage());

            return '';
        }

        if (! $setting->active || trim($setting->snippet) === '' || $setting->position !== $position) {
            return '';
        }

        if (! auth()->check() && ! $setting->show_for_guests) {
            return '';
        }

        return "\n<!-- custom-script:start -->\n".$setting->snippet."\n<!-- custom-script:end -->\n";
    }
}
