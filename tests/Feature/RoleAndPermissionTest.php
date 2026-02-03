<?php

namespace Tests\Feature;

use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Feature-Tests für Rollen- und Berechtigungssystem
 */
class RoleAndPermissionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function role_can_be_created()
    {
        $role = Role::create(['name' => 'admin']);

        $this->assertDatabaseHas('roles', [
            'name' => 'admin',
        ]);
    }

    /**
     * @test
     */
    public function permission_can_be_created()
    {
        $permission = Permission::create(['name' => 'edit posts']);

        $this->assertDatabaseHas('permissions', [
            'name' => 'edit posts',
        ]);
    }

    /**
     * @test
     */
    public function user_can_be_assigned_role()
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'moderator']);

        $user->assignRole($role);

        $this->assertTrue($user->hasRole('moderator'));
    }

    /**
     * @test
     */
    public function user_can_have_multiple_roles()
    {
        $user = User::factory()->create();
        $role1 = Role::create(['name' => 'admin']);
        $role2 = Role::create(['name' => 'moderator']);

        $user->assignRole([$role1, $role2]);

        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasRole('moderator'));
        $this->assertCount(2, $user->roles);
    }

    /**
     * @test
     */
    public function role_can_have_permissions()
    {
        $role = Role::create(['name' => 'editor']);
        $permission = Permission::create(['name' => 'edit articles']);

        $role->givePermissionTo($permission);

        $this->assertTrue($role->hasPermissionTo('edit articles'));
    }

    /**
     * @test
     */
    public function user_inherits_permissions_from_role()
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'writer']);
        $permission = Permission::create(['name' => 'write posts']);

        $role->givePermissionTo($permission);
        $user->assignRole($role);

        $this->assertTrue($user->hasPermissionTo('write posts'));
    }

    /**
     * @test
     */
    public function user_can_have_direct_permissions()
    {
        $user = User::factory()->create();
        $permission = Permission::create(['name' => 'special action']);

        $user->givePermissionTo($permission);

        $this->assertTrue($user->hasPermissionTo('special action'));
    }

    /**
     * @test
     */
    public function user_can_be_checked_for_permission_via_gate()
    {
        $user = User::factory()->create();
        $permission = Permission::create(['name' => 'view dashboard']);
        $user->givePermissionTo($permission);

        $this->actingAs($user);

        $this->assertTrue($user->can('view dashboard'));
        $this->assertFalse($user->can('delete everything'));
    }
}
