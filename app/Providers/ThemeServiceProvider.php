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
    }
}

