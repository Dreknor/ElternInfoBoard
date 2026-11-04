<?php

namespace Tests\Feature;

use App\Model\Module;
use App\Model\User;
use App\Services\App\Modules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\ActivatesModules;
use Tests\TestCase;

class ModuleInstanceLockTest extends TestCase
{
    use ActivatesModules;
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Module::query()->delete();
        config(['modules.restricted' => ['Premium'], 'modules.unlocked' => []]);

        Permission::firstOrCreate(['name' => 'edit settings', 'guard_name' => 'web']);
        $this->admin = User::factory()->create(['password_changed_at' => now()]);
        $this->admin->givePermissionTo('edit settings');
    }

    /** @test */
    public function locked_module_is_inactive_even_if_stored_active(): void
    {
        $module = $this->activateModule('Premium');

        $this->assertTrue($module->fresh()->isLocked());
        $this->assertSame('0', $module->fresh()->options['active']);
        $this->assertFalse(Modules::isActiveFor($this->admin, 'Premium'));
    }

    /** @test */
    public function unlocked_module_behaves_normally(): void
    {
        config(['modules.unlocked' => ['Premium']]);
        $this->activateModule('Premium');

        $this->assertTrue(Modules::isActiveFor($this->admin, 'Premium'));
    }

    /** @test */
    public function unrestricted_modules_are_not_affected(): void
    {
        $this->activateModule('Termine');

        $this->assertTrue(Modules::isActiveFor($this->admin, 'Termine'));
        $this->assertSame(['Termine'], Module::available()->pluck('setting')->all());
    }

    /** @test */
    public function locked_module_is_hidden_from_module_settings(): void
    {
        $this->withoutVite();
        $this->activateModule('Premium');
        $this->activateModule('Termine');

        $this->actingAs($this->admin)
            ->get('/modules')
            ->assertOk()
            ->assertSee('Termine')
            ->assertDontSee('Premium');
    }

    /** @test */
    public function locked_module_cannot_be_toggled_by_admin(): void
    {
        $module = Module::create([
            'setting' => 'Premium',
            'category' => 'module',
            'description' => 'Premium',
            'options' => ['active' => '0', 'rights' => [], 'nav' => ['bottom-nav' => 'false']],
        ]);

        $this->actingAs($this->admin)->get('/modules/modul/Premium');
        $this->actingAs($this->admin)->get('/modules/modul/bottomnav/Premium');

        config(['modules.restricted' => []]);
        $options = $module->fresh()->options;
        $this->assertSame('0', $options['active']);
        $this->assertSame('false', $options['nav']['bottom-nav']);
    }

    /** @test */
    public function locked_module_is_not_listed_in_api(): void
    {
        $this->activateModule('Premium');
        $this->activateModule('Termine');

        Sanctum::actingAs($this->admin);

        $this->getJson('/api/modules')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.setting', 'Termine');
    }
}
