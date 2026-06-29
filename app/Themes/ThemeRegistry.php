<?php

namespace App\Themes;

use App\Themes\Contracts\ThemeInterface;
use Illuminate\Support\Collection;

class ThemeRegistry
{
    /** @var array<string, ThemeInterface> */
    private array $themes = [];

    public function __construct()
    {
        // Standard-Themes registrieren
        $this->register(new DefaultTheme());

        $this->register(new DarkTheme());
        $this->register(new NatureTheme());
        $this->register(new WarmTheme());
        $this->register(new ESZTheme());
        $this->register(new LightTheme());

    }

    public function register(ThemeInterface $theme): void
    {
        $this->themes[$theme->id()] = $theme;
    }

    public function get(string $id): ThemeInterface
    {
        return $this->themes[$id] ?? $this->themes['default'];
    }

    public function all(): Collection
    {
        return collect($this->themes);
    }

    public function exists(string $id): bool
    {
        return isset($this->themes[$id]);
    }
}

