<?php

namespace App\Providers;

use App\Services\ThemeService;
use App\Themes\ThemeRegistry;
use Illuminate\Support\ServiceProvider;

class ThemeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ThemeRegistry::class, fn () => new ThemeRegistry());
        $this->app->singleton(ThemeService::class);
    }

    public function boot(): void
    {
        // Erweiterungspunkt: Themes aus Config laden (für nachträgliche Themes)
        $customThemes = config('themes.custom', []);
        if (! empty($customThemes)) {
            /** @var ThemeRegistry $registry */
            $registry = $this->app->make(ThemeRegistry::class);
            foreach ($customThemes as $themeClass) {
                if (class_exists($themeClass)) {
                    $registry->register(new $themeClass());
                }
            }
        }

        // Datenbankbasiertes eigenes Theme registrieren
        try {
            /** @var ThemeRegistry $registry */
            $registry = $this->app->make(ThemeRegistry::class);
            $setting  = $this->app->make(\App\Settings\CustomThemeSetting::class);
            $registry->register(new \App\Themes\CustomTheme($setting));
        } catch (\Throwable $e) {
            // DB noch nicht verfügbar (z.B. während Migrationen) – ignorieren
        }
    }
}

