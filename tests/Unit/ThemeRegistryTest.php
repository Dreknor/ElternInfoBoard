<?php

namespace Tests\Unit;

use App\Themes\Contracts\ThemeInterface;
use App\Themes\DarkTheme;
use App\Themes\DefaultTheme;
use App\Themes\NatureTheme;
use App\Themes\ThemeRegistry;
use App\Themes\WarmTheme;
use Tests\TestCase;

class ThemeRegistryTest extends TestCase
{
    public function test_registry_loads_default_themes(): void
    {
        $registry = new ThemeRegistry();

        $this->assertTrue($registry->exists('default'));
        $this->assertTrue($registry->exists('dark'));
        $this->assertTrue($registry->exists('nature'));
        $this->assertTrue($registry->exists('warm'));
    }

    public function test_get_returns_requested_theme(): void
    {
        $registry = new ThemeRegistry();

        $this->assertInstanceOf(DefaultTheme::class, $registry->get('default'));
        $this->assertInstanceOf(DarkTheme::class, $registry->get('dark'));
        $this->assertInstanceOf(NatureTheme::class, $registry->get('nature'));
        $this->assertInstanceOf(WarmTheme::class, $registry->get('warm'));
    }

    public function test_get_falls_back_to_default_for_unknown_id(): void
    {
        $registry = new ThemeRegistry();

        $theme = $registry->get('does-not-exist');

        $this->assertInstanceOf(DefaultTheme::class, $theme);
        $this->assertSame('default', $theme->id());
    }

    public function test_register_custom_theme(): void
    {
        $registry = new ThemeRegistry();

        $custom = new class extends \App\Themes\AbstractTheme {
            public function id(): string { return 'custom-test'; }
            public function name(): string { return 'Custom Test'; }
            public function description(): string { return 'desc'; }
            public function variables(): array { return ['--color-primary' => '#fff']; }
        };

        $registry->register($custom);

        $this->assertTrue($registry->exists('custom-test'));
        $this->assertSame($custom, $registry->get('custom-test'));
    }

    public function test_all_returns_collection_of_themes(): void
    {
        $registry = new ThemeRegistry();
        $all = $registry->all();

        $this->assertGreaterThanOrEqual(4, $all->count());
        foreach ($all as $theme) {
            $this->assertInstanceOf(ThemeInterface::class, $theme);
        }
    }

    public function test_each_theme_defines_required_css_variables(): void
    {
        $registry = new ThemeRegistry();
        $required = [
            '--color-primary',
            '--color-sidebar-bg',
            '--color-navbar-bg',
            '--color-body-bg',
            '--color-text-primary',
            '--app-bg',
            '--app-text',
        ];

        foreach ($registry->all() as $theme) {
            $vars = $theme->variables();
            foreach ($required as $key) {
                $this->assertArrayHasKey(
                    $key,
                    $vars,
                    "Theme [{$theme->id()}] fehlt CSS-Variable [{$key}]"
                );
            }
        }
    }
}

