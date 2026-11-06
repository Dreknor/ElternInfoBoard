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

    /** Pfad der eigenständigen Chat-Seite (für die App per auth/web-link). */
    public const CHAT_PATH = '/chat';

    public static function enabled(): bool
    {
        return (bool) config('custom_scripts.enabled', false);
    }

    /**
     * Liefert das Snippet für den angegebenen Ausgabeort im Layout oder einen leeren String.
     */
    public function render(string $position): string
    {
        $setting = $this->activeSetting();

        if (! $setting || $setting->position !== $position) {
            return '';
        }

        if (! auth()->check() && ! $setting->show_for_guests) {
            return '';
        }

        return self::wrap($setting->snippet);
    }

    /**
     * Snippet für die eigenständige Chat-Seite (unabhängig vom Ausgabeort) oder null.
     */
    public function chatPageSnippet(): ?string
    {
        $setting = $this->activeSetting();

        return $setting ? self::wrap($setting->snippet) : null;
    }

    /**
     * Chat-Eintrag für die App (bootstrap) oder null, wenn nicht angeboten.
     *
     * Nur gesetzt, wenn tatsächlich ein aktives Snippet existiert. Die App holt
     * über POST auth/web-link einen Anmeldelink für `web_path` und öffnet ihn.
     *
     * @return array{label: string, web_path: string}|null
     */
    public function appChat(): ?array
    {
        $setting = $this->activeSetting();

        if (! $setting || ! $setting->app_enabled) {
            return null;
        }

        return [
            'label' => $setting->app_label !== '' ? $setting->app_label : 'Chat',
            'web_path' => self::CHAT_PATH,
        ];
    }

    /**
     * Setting, sofern freigeschaltet, aktiv und mit Inhalt – sonst null.
     */
    private function activeSetting(): ?CustomScriptSetting
    {
        if (! self::enabled()) {
            return null;
        }

        try {
            $setting = app(CustomScriptSetting::class);
        } catch (\Throwable $e) {
            // z. B. Settings-Migration noch nicht gelaufen: Seite nicht kaputt machen
            Log::warning('Eigene Skripte konnten nicht geladen werden: '.$e->getMessage());

            return null;
        }

        if (! $setting->active || trim($setting->snippet) === '') {
            return null;
        }

        return $setting;
    }

    private static function wrap(string $snippet): string
    {
        return "\n<!-- custom-script:start -->\n".$snippet."\n<!-- custom-script:end -->\n";
    }
}
