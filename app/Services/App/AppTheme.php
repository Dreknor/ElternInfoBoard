<?php

namespace App\Services\App;

use App\Themes\Contracts\ThemeInterface;

/**
 * Überträgt die CSS-Variablen des Web-Themes auf die Farb-Tokens der App,
 * damit Web und App gleich aussehen (auch bei eigenem Schul-Theme).
 */
class AppTheme
{
    /** App-Token => [Web-Variable, Ersatzwert] */
    private const MAP = [
        'primary' => ['--color-primary', '#2563eb'],
        'primaryPressed' => ['--color-primary-dark', '#1d4ed8'],
        'primarySoft' => ['--color-primary-light', '#eff6ff'],
        'accent' => ['--color-secondary', '#6366f1'],
        'accentSoft' => ['--color-widget-accent-bg', '#faf5ff'],
        'background' => ['--color-body-bg', '#f3f4f6'],
        'surface' => ['--color-card-bg', '#ffffff'],
        'border' => ['--color-card-border', '#e5e7eb'],
        'divider' => ['--color-surface-subtle', '#f9fafb'],
        'text' => ['--color-text-primary', '#111827'],
        'textMuted' => ['--color-text-secondary', '#6b7280'],
        'textSubtle' => ['--color-input-placeholder', '#9ca3af'],
        'success' => ['--color-text-success', '#15803d'],
        'successSoft' => ['--color-widget-success-bg', '#f0fdf4'],
        'warning' => ['--color-warning-text-secondary', '#92400e'],
        'warningSoft' => ['--color-warning-bg', '#fef3c7'],
        'badge' => ['--color-badge-bg', '#ef4444'],
        'headerFrom' => ['--color-widget-primary-from', '#2563eb'],
        'headerTo' => ['--color-widget-primary-to', '#1d4ed8'],
        'losungFrom' => ['--color-losung-header-from', '#2563eb'],
        'losungTo' => ['--color-losung-header-to', '#4f46e5'],
    ];

    public static function from(ThemeInterface $theme): array
    {
        $vars = $theme->variables();
        $colors = [];
        foreach (self::MAP as $token => [$var, $fallback]) {
            $value = $vars[$var] ?? $fallback;
            // Nur einfache Hex-Farben übernehmen (keine Verläufe o. Ä.).
            $colors[$token] = preg_match('/^#([0-9a-f]{6}|[0-9a-f]{3})$/i', trim($value)) ? strtolower(trim($value)) : $fallback;
        }

        return [
            'id' => $theme->id(),
            'name' => $theme->name(),
            'dark' => str_contains($theme->bodyClasses(), 'dark') || $theme->id() === 'dark',
            'colors' => $colors,
        ];
    }
}
