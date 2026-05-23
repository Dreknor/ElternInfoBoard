<?php

namespace Tests\Feature\Ucs;

use App\Settings\UcsSetting;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SettingsControllerUcsDebugTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Permission::firstOrCreate(['name' => 'edit settings', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage ucs sync', 'guard_name' => 'web']);
    }

    public function test_debug_ucs_put_response(): void
    {
        $user = \App\Model\User::factory()->create([
            'changePassword' => false,
        ]);
        $user->givePermissionTo('edit settings');
        $user->givePermissionTo('manage ucs sync');

        $response = $this->actingAs($user)
            ->put('/settings/ucs', [
                'sync_cron' => 'kaputt',
                'kelvin_page_size' => 200,
                'kelvin_timeout' => 30,
                'kelvin_token_ttl' => 3300,
                'on_login_timeout' => 5,
                'purge_after_days' => 14,
            ]);

        dump('Status: ' . $response->getStatusCode());
        dump('Location: ' . $response->headers->get('Location', 'none'));
        dump('Session errors: ', session('errors'));
        dump('Session all: ', session()->all());
        $this->assertTrue(true);
    }

    public function test_debug_settings_db(): void
    {
        $count = \Illuminate\Support\Facades\DB::table('settings')->count();
        dump("Settings rows in DB: $count");
        dump(\Illuminate\Support\Facades\DB::table('settings')->pluck('group')->toArray());
        $this->assertTrue(true);
    }
}

