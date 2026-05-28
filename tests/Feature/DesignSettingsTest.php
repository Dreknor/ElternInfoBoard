<?php

namespace Tests\Feature;

use App\Model\User;
use App\Services\ThemeService;
use App\Settings\GeneralSetting;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DesignSettingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Spatie/laravel-settings registriert Settings als 'scoped'.
        // Da Tests im selben Application-Container laufen, müssen wir Scoped-Instanzen
        // pro Test forgetten, damit Setting-Änderungen wirklich neu aus der DB geladen werden.
        $this->app->forgetScopedInstances();
        $this->app->forgetInstance(ThemeService::class);
    }

    private function adminUser(): User
    {
        Permission::firstOrCreate(['name' => 'edit settings', 'guard_name' => 'web']);
        // changePassword=false explizit setzen, damit PasswordExpired-Middleware nicht redirected
        $user = User::factory()->create(['changePassword' => false]);
        $user->givePermissionTo('edit settings');

        return $user;
    }

    public function test_admin_can_update_default_theme(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)
            ->put('/settings/design', [
                'default_theme'    => 'nature',
                'allow_user_theme' => '1',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('type', 'success');

        // Frisch aus DB lesen (scoped-Instanz invalidieren)
        $this->app->forgetScopedInstances();
        $settings = app(GeneralSetting::class);
        $this->assertSame('nature', $settings->default_theme);
        $this->assertTrue($settings->allow_user_theme);
    }

    public function test_admin_can_disable_user_theme(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user)->put('/settings/design', [
            'default_theme'    => 'default',
            // allow_user_theme NICHT gesendet → soll false werden
        ])->assertRedirect();

        $this->app->forgetScopedInstances();
        $settings = app(GeneralSetting::class);
        $this->assertFalse($settings->allow_user_theme);
    }

    public function test_admin_cannot_set_unknown_theme(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->put('/settings/design', [
            'default_theme'    => 'does-not-exist',
            'allow_user_theme' => '1',
        ]);

        $response->assertSessionHasErrors('default_theme');

        $this->app->forgetScopedInstances();
        $settings = app(GeneralSetting::class);
        $this->assertNotSame('does-not-exist', $settings->default_theme);
    }

    public function test_non_admin_cannot_update_design_settings(): void
    {
        $user = User::factory()->create(); // ohne edit-settings Permission

        $response = $this->actingAs($user)->put('/settings/design', [
            'default_theme'    => 'dark',
            'allow_user_theme' => '1',
        ]);

        // Spatie wirft 403 wenn Permission fehlt (HttpException → Laravel rendert 403);
        // einige Apps redirecten unauthorized Requests. Beides akzeptieren, solange NICHT 2xx.
        $this->assertContains($response->status(), [302, 403], 'Erwartete 302 oder 403, bekam '.$response->status());
        $this->assertNotEquals(200, $response->status());

        // Default-Theme darf sich NICHT auf 'dark' geändert haben
        $this->app->forgetScopedInstances();
        $settings = app(GeneralSetting::class);
        $this->assertNotSame('dark', $settings->default_theme);
    }

    public function test_guest_is_redirected_when_updating_design(): void
    {
        $this->put('/settings/design', [
            'default_theme' => 'dark',
        ])->assertRedirect();
    }
}

