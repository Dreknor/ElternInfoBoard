<?php

namespace App\Services;

use App\Model\UserAppSettings;
use App\Settings\GeneralSetting;
use App\Themes\Contracts\ThemeInterface;
use App\Themes\ThemeRegistry;
use Illuminate\Support\Facades\Auth;

class ThemeService
{
    private ?ThemeInterface $resolvedCache = null;

    public function __construct(
        private ThemeRegistry $registry,
        private GeneralSetting $generalSetting,
    ) {}

    /**
     * Löst den aktiven Theme auf:
     * 1. User-eigene Einstellung (wenn erlaubt)
     * 2. Globaler Admin-Standard
     * 3. Fallback: 'default'
     */
    public function resolveActive(): ThemeInterface
    {
        if ($this->resolvedCache !== null) {
            return $this->resolvedCache;
        }

        // Ist Nutzer eingeloggt UND darf er überschreiben?
        if (Auth::check() && ($this->allowUserTheme())) {
            $userSettings = UserAppSettings::where('user_id', Auth::id())->first();
            $userTheme = data_get($userSettings?->settings, 'theme');

            if ($userTheme && $this->registry->exists($userTheme)) {
                return $this->resolvedCache = $this->registry->get($userTheme);
            }
        }

        // Globaler Admin-Standard
        $defaultTheme = $this->defaultTheme();

        return $this->resolvedCache = $this->registry->get($defaultTheme);
    }

    /**
     * Rendert die CSS Custom Properties als <style>-Block.
     */
    public function renderCssVariables(): string
    {
        $theme = $this->resolveActive();
        $vars = collect($theme->variables())
            ->map(fn ($value, $key) => "  {$key}: {$value};")
            ->implode("\n");

        return "<style id=\"theme-vars\">\n:root {\n{$vars}\n}\n</style>";
    }

    public function registry(): ThemeRegistry
    {
        return $this->registry;
    }

    /**
     * Liefert den global konfigurierten Standard-Theme-Identifier.
     */
    public function defaultTheme(): string
    {
        try {
            return $this->generalSetting->default_theme ?? 'default';
        } catch (\Throwable $e) {
            return 'default';
        }
    }

    /**
     * Prüft, ob Nutzer einen eigenen Theme wählen dürfen.
     */
    public function allowUserTheme(): bool
    {
        try {
            return (bool) ($this->generalSetting->allow_user_theme ?? true);
        } catch (\Throwable $e) {
            return true;
        }
    }
}

