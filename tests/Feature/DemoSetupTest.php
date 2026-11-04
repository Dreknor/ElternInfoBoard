<?php

namespace Tests\Feature;

use App\Console\Commands\DemoSetup;
use App\Http\View\Composers\ModulesComposer;
use App\Model\Module;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DemoSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_role_receives_permissions_added_by_newer_modules(): void
    {
        $permission = Permission::create([
            'name' => 'new module permission',
            'guard_name' => 'web',
        ]);

        $this->invokeSetupMethod('createPermissionsAndRoles');

        $this->assertTrue(
            Role::findByName('Administrator', 'web')->hasPermissionTo($permission)
        );
    }

    public function test_activating_modules_invalidates_the_cached_module_list(): void
    {
        $module = Module::create([
            'setting' => 'Demo module',
            'category' => 'module',
            'description' => 'Demo module',
            'options' => ['active' => '0', 'rights' => []],
        ]);

        Cache::forget('modules');
        $this->assertFalse(ModulesComposer::activeModules()->contains('setting', $module->setting));

        $this->invokeSetupMethod('activateModules');

        $this->assertTrue(ModulesComposer::activeModules()->contains('setting', $module->setting));
    }

    private function invokeSetupMethod(string $method): void
    {
        (new \ReflectionMethod(DemoSetup::class, $method))->invoke(new DemoSetup());
    }
}
