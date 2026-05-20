<?php

namespace Tests\Unit;

use App\Model\User;
use App\Model\UserAppSettings;
use App\Services\ThemeService;
use App\Settings\GeneralSetting;
use App\Themes\DarkTheme;
use App\Themes\DefaultTheme;
use App\Themes\NatureTheme;
use App\Themes\ThemeRegistry;
use Tests\TestCase;

class ThemeServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->forgetScopedInstances();
        $this->app->forgetInstance(ThemeService::class);
    }

    private function service(): ThemeService
    {
        return app(ThemeService::class);
    }

    public function test_returns_default_theme_for_guest(): void
    {
        $theme = $this->service()->resolveActive();

        $this->assertInstanceOf(DefaultTheme::class, $theme);
    }

    public function test_returns_admin_default_theme_when_user_has_no_preference(): void
    {
        $general = app(GeneralSetting::class);
        $general->default_theme = 'nature';
        $general->allow_user_theme = true;
        $general->save();

        $user = User::factory()->create();
        $this->actingAs($user);

        // Service neu auflösen, da resolvedCache pro Instanz hängt
        app()->forgetInstance(ThemeService::class);

        $theme = $this->service()->resolveActive();
        $this->assertInstanceOf(NatureTheme::class, $theme);
    }

    public function test_user_preference_wins_when_allowed(): void
    {
        $general = app(GeneralSetting::class);
        $general->default_theme = 'default';
        $general->allow_user_theme = true;
        $general->save();

        $user = User::factory()->create();
        UserAppSettings::create([
            'user_id' => $user->id,
            'settings' => ['theme' => 'dark'],
        ]);

        $this->actingAs($user);
        app()->forgetInstance(ThemeService::class);

        $theme = $this->service()->resolveActive();
        $this->assertInstanceOf(DarkTheme::class, $theme);
    }

    public function test_user_preference_ignored_when_disallowed(): void
    {
        $general = app(GeneralSetting::class);
        $general->default_theme = 'nature';
        $general->allow_user_theme = false;
        $general->save();

        $user = User::factory()->create();
        UserAppSettings::create([
            'user_id' => $user->id,
            'settings' => ['theme' => 'dark'],
        ]);

        $this->actingAs($user);
        app()->forgetInstance(ThemeService::class);

        $theme = $this->service()->resolveActive();
        $this->assertInstanceOf(NatureTheme::class, $theme);
    }

    public function test_unknown_user_theme_falls_back_to_default(): void
    {
        $general = app(GeneralSetting::class);
        $general->default_theme = 'default';
        $general->allow_user_theme = true;
        $general->save();

        $user = User::factory()->create();
        UserAppSettings::create([
            'user_id' => $user->id,
            'settings' => ['theme' => 'does-not-exist'],
        ]);

        $this->actingAs($user);
        app()->forgetInstance(ThemeService::class);

        $theme = $this->service()->resolveActive();
        $this->assertInstanceOf(DefaultTheme::class, $theme);
    }

    public function test_render_css_variables_contains_root_block(): void
    {
        app()->forgetInstance(ThemeService::class);
        $css = $this->service()->renderCssVariables();

        $this->assertStringContainsString('<style id="theme-vars">', $css);
        $this->assertStringContainsString(':root {', $css);
        $this->assertStringContainsString('--color-primary:', $css);
        $this->assertStringContainsString('</style>', $css);
    }

    public function test_registry_accessor_returns_registry(): void
    {
        $this->assertInstanceOf(ThemeRegistry::class, $this->service()->registry());
    }
}

