<?php

namespace App\Services\CustomScripts;

use App\Model\CustomScript;
use App\Model\User;
use App\Settings\CustomScriptSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Entscheidet, welche eigenen Skripte wo ausgegeben werden.
 *
 * Ohne Instanz-Freischaltung (config custom_scripts.enabled) wird nichts
 * gelesen und nichts ausgegeben. Ein Skript erscheint nur, wenn es aktiv ist
 * und Ausgabeort, Zielgruppe (Kontoart, Rollen) und Seitenregel passen.
 */
class CustomScriptRenderer
{
    public const POSITIONS = CustomScript::POSITIONS;

    /** Pfad der eigenständigen Chat-Seite (für die App per auth/web-link). */
    public const CHAT_PATH = '/chat';

    /** @var Collection<int, CustomScript>|null aktive Skripte, einmal pro Request geladen */
    private ?Collection $activeScripts = null;

    public static function enabled(): bool
    {
        return (bool) config('custom_scripts.enabled', false);
    }

    /**
     * Snippets für den Ausgabeort im Layout der aktuellen Seite oder ein leerer String.
     */
    public function render(string $position): string
    {
        $user = $this->currentUser();
        $path = request()->path();

        return $this->activeScripts()
            ->filter(fn (CustomScript $script) => $script->position === $position
                && $script->matchesUser($user)
                && $script->matchesPath($path))
            ->map(fn (CustomScript $script) => self::wrap($script))
            ->implode('');
    }

    /**
     * Snippets für die eigenständige Chat-Seite (Seitenregel und Ausgabeort spielen keine Rolle) oder null.
     */
    public function chatPageSnippet(?User $user = null): ?string
    {
        $scripts = $this->chatScriptsFor($user ?? $this->currentUser());

        return $scripts->isEmpty() ? null : $scripts->map(fn (CustomScript $script) => self::wrap($script))->implode('');
    }

    /**
     * Chat-Eintrag für die App (bootstrap) oder null, wenn nicht angeboten.
     *
     * Nur gesetzt, wenn für den Nutzer mindestens ein aktives Chat-Skript greift. Die App holt
     * über POST auth/web-link einen Anmeldelink für `web_path` und öffnet ihn.
     *
     * @return array{label: string, web_path: string}|null
     */
    public function appChat(?User $user = null): ?array
    {
        if (! self::enabled()) {
            return null;
        }

        $setting = $this->setting();

        if (! $setting || ! $setting->app_enabled || $this->chatScriptsFor($user ?? $this->currentUser())->isEmpty()) {
            return null;
        }

        return [
            'label' => $setting->app_label !== '' ? $setting->app_label : 'Chatbot',
            'web_path' => self::CHAT_PATH,
        ];
    }

    /**
     * @return Collection<int, CustomScript>
     */
    private function chatScriptsFor(?User $user): Collection
    {
        return $this->activeScripts()
            ->filter(fn (CustomScript $script) => $script->use_on_chat && $script->matchesUser($user))
            ->values();
    }

    /**
     * Aktive Skripte mit Inhalt, sortiert; leer ohne Freischaltung oder bei Fehlern.
     *
     * @return Collection<int, CustomScript>
     */
    private function activeScripts(): Collection
    {
        if ($this->activeScripts !== null) {
            return $this->activeScripts;
        }

        if (! self::enabled()) {
            return $this->activeScripts = collect();
        }

        try {
            $scripts = CustomScript::query()
                ->where('active', true)
                ->with('roles:id')
                ->ordered()
                ->get()
                ->filter(fn (CustomScript $script) => trim($script->snippet) !== '')
                ->values();
        } catch (\Throwable $e) {
            // z. B. Migration noch nicht gelaufen: Seite nicht kaputt machen
            Log::warning('Eigene Skripte konnten nicht geladen werden: '.$e->getMessage());
            $scripts = collect();
        }

        return $this->activeScripts = $scripts;
    }

    private function setting(): ?CustomScriptSetting
    {
        try {
            return app(CustomScriptSetting::class);
        } catch (\Throwable $e) {
            Log::warning('Einstellungen der eigenen Skripte konnten nicht geladen werden: '.$e->getMessage());

            return null;
        }
    }

    private function currentUser(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }

    private static function wrap(CustomScript $script): string
    {
        return "\n<!-- custom-script:start #{$script->id} -->\n".$script->snippet."\n<!-- custom-script:end #{$script->id} -->\n";
    }
}
