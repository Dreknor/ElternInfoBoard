<?php

namespace Tests\Feature;

use App\Model\User;
use App\Model\UserAppSettings;
use App\Services\ThemeService;
use App\Settings\GeneralSetting;
use Tests\TestCase;

class UserThemeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Spatie/laravel-settings registriert Settings 'scoped' — zwischen Tests forgetten,
        // damit DB-Änderungen wirksam werden.
        $this->app->forgetScopedInstances();
        $this->app->forgetInstance(ThemeService::class);
    }

    private function enableUserThemes(bool $allow = true): void
    {
        $g = app(GeneralSetting::class);
        $g->allow_user_theme = $allow;
        $g->default_theme = 'default';
        $g->save();
    }

    private function makeUser(): User
    {
        return User::factory()->create(['changePassword' => false]);
    }

    public function test_user_can_choose_own_theme(): void
    {
        $this->enableUserThemes(true);
        $user = User::factory()->create(['changePassword' => false]);

        $this->actingAs($user)
            ->put('/einstellungen/theme', ['theme' => 'dark'])
            ->assertRedirect();

        $settings = UserAppSettings::where('user_id', $user->id)->first();
        $this->assertNotNull($settings);
        $this->assertSame('dark', data_get($settings->settings, 'theme'));
    }

    public function test_empty_theme_value_resets_to_system_default(): void
    {
        $this->enableUserThemes(true);
        $user = User::factory()->create(['changePassword' => false]);

        UserAppSettings::create([
            'user_id' => $user->id,
            'settings' => ['theme' => 'dark', 'other' => 'value'],
        ]);

        $this->actingAs($user)
            ->put('/einstellungen/theme', ['theme' => ''])
            ->assertRedirect();

        $settings = UserAppSettings::where('user_id', $user->id)->first();
        $this->assertArrayNotHasKey('theme', $settings->settings ?? []);
        // andere Settings bleiben erhalten
        $this->assertSame('value', data_get($settings->settings, 'other'));
    }

    public function test_invalid_theme_returns_error(): void
    {
        $this->enableUserThemes(true);
        $user = User::factory()->create(['changePassword' => false]);

        $response = $this->actingAs($user)
            ->put('/einstellungen/theme', ['theme' => 'does-not-exist']);

        $response->assertSessionHasErrors('theme');
    }

    public function test_user_cannot_set_theme_when_disallowed(): void
    {
        $this->enableUserThemes(false);
        $user = User::factory()->create(['changePassword' => false]);

        $response = $this->actingAs($user)
            ->put('/einstellungen/theme', ['theme' => 'dark']);

        // abort_unless(false, 403) → 403 HttpException; manche Setups rendern als Redirect
        $this->assertContains($response->status(), [302, 403], 'Erwartete 302 oder 403, bekam '.$response->status());
        $this->assertNotEquals(200, $response->status());

        // Theme darf NICHT gesetzt worden sein
        $settings = UserAppSettings::where('user_id', $user->id)->first();
        $this->assertNull(data_get($settings?->settings, 'theme'));
    }

    public function test_guest_is_redirected(): void
    {
        $this->enableUserThemes(true);

        $this->put('/einstellungen/theme', ['theme' => 'dark'])
            ->assertRedirect();
    }
}

