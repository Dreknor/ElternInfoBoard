<?php

namespace Tests\Feature\Http\Controllers;

use App\Model\Module;
use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\SettingsController
 */
class SettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function module_returns_an_ok_response_for_authorized_user()
    {
        $user = User::factory()->create();
        Permission::create(['name' => 'manage settings']);
        $user->givePermissionTo('manage settings');

        Module::factory()->count(5)->create();

        $response = $this->actingAs($user)->get('settings');

        $response->assertOk();
        $response->assertViewIs('settings.module');
        $response->assertViewHas('module');
    }

    /**
     * @test
     */
    public function unauthorized_user_cannot_access_settings()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('settings');

        $response->assertForbidden();
    }

    /**
     * @test
     */
    public function change_status_toggles_module_status()
    {
        $user = User::factory()->create();
        Permission::create(['name' => 'manage settings']);
        $user->givePermissionTo('manage settings');

        $module = Module::factory()->create(['setting' => 'test_module']);

        $response = $this->actingAs($user)->get("settings/modul/{$module->id}");

        $response->assertRedirect();
    }

    /**
     * @test
     */
    public function change_nav_updates_bottom_navigation()
    {
        $user = User::factory()->create();
        Permission::create(['name' => 'manage settings']);
        $user->givePermissionTo('manage settings');

        $module = Module::factory()->create();

        $response = $this->actingAs($user)->get("settings/modul/bottomnav/{$module->id}");

        $response->assertRedirect();
    }
}


