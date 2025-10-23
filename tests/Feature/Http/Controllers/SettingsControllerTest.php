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
        $user = User::factory()->create(['password_changed_at' => now()]);
        Permission::create(['name' => 'edit settings']);
        $user->givePermissionTo('edit settings');

        Module::factory()->count(5)->create();

        $response = $this->actingAs($user)->get('settings');

        $response->assertOk();
        $response->assertViewIs('settings.index');
    }

    /**
     * @test
     */
    public function unauthorized_user_cannot_access_settings()
    {
        $user = User::factory()->create(['password_changed_at' => now()]);
        $response = $this->actingAs($user)->get('settings');

        $response->assertForbidden();
    }

}


