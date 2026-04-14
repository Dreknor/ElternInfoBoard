<?php

namespace Tests\Feature\API;

use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tests für GET /api/me/permissions
 */
class UserPermissionsControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    // -------------------------------------------------------------------------
    // Authentifizierung
    // -------------------------------------------------------------------------

    /** @test */
    public function unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/me/permissions');

        $response->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // Response-Struktur
    // -------------------------------------------------------------------------

    /** @test */
    public function authenticated_user_gets_correct_structure(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/me/permissions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'permissions',
                    'roles',
                ],
            ])
            ->assertJsonPath('success', true);
    }

    // -------------------------------------------------------------------------
    // Leere Berechtigungen und Rollen
    // -------------------------------------------------------------------------

    /** @test */
    public function user_without_permissions_and_roles_returns_empty_arrays(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/me/permissions');

        $response->assertStatus(200)
            ->assertExactJson([
                'success' => true,
                'data'    => [
                    'permissions' => [],
                    'roles'       => [],
                ],
            ]);
    }

    // -------------------------------------------------------------------------
    // Direkte Berechtigungen
    // -------------------------------------------------------------------------

    /** @test */
    public function user_with_direct_permissions_returns_them(): void
    {
        Permission::firstOrCreate(['name' => 'view vertretungsplan', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'use messenger',        'guard_name' => 'web']);

        $this->user->givePermissionTo(['view vertretungsplan', 'use messenger']);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/me/permissions');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $permissions = $response->json('data.permissions');
        $this->assertContains('view vertretungsplan', $permissions);
        $this->assertContains('use messenger', $permissions);
    }

    // -------------------------------------------------------------------------
    // Geerbte Berechtigungen über Rolle
    // -------------------------------------------------------------------------

    /** @test */
    public function user_inherits_permissions_from_role(): void
    {
        $role       = Role::create(['name' => 'Eltern', 'guard_name' => 'web']);
        $permission = Permission::firstOrCreate(['name' => 'view stundenplan', 'guard_name' => 'web']);

        $role->givePermissionTo($permission);
        $this->user->assignRole($role);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/me/permissions');

        $response->assertStatus(200);

        $permissions = $response->json('data.permissions');
        $this->assertContains('view stundenplan', $permissions);
    }

    // -------------------------------------------------------------------------
    // Rollen
    // -------------------------------------------------------------------------

    /** @test */
    public function user_with_role_returns_role_name(): void
    {
        $role = Role::create(['name' => 'Eltern', 'guard_name' => 'web']);
        $this->user->assignRole($role);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/me/permissions');

        $response->assertStatus(200)
            ->assertJsonPath('data.roles.0', 'Eltern');
    }

    /** @test */
    public function user_with_multiple_roles_returns_all_role_names(): void
    {
        Role::create(['name' => 'Eltern',    'guard_name' => 'web']);
        Role::create(['name' => 'Lehrkraft', 'guard_name' => 'web']);

        $this->user->assignRole(['Eltern', 'Lehrkraft']);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/me/permissions');

        $response->assertStatus(200);

        $roles = $response->json('data.roles');
        $this->assertContains('Eltern',    $roles);
        $this->assertContains('Lehrkraft', $roles);
        $this->assertCount(2, $roles);
    }

    // -------------------------------------------------------------------------
    // Kombiniert: direkte + geerbte Berechtigungen
    // -------------------------------------------------------------------------

    /** @test */
    public function returns_combined_direct_and_inherited_permissions(): void
    {
        $role            = Role::create(['name' => 'Eltern', 'guard_name' => 'web']);
        $inheritedPerm   = Permission::firstOrCreate(['name' => 'view vertretungsplan', 'guard_name' => 'web']);
        $directPerm      = Permission::firstOrCreate(['name' => 'use messenger',        'guard_name' => 'web']);

        $role->givePermissionTo($inheritedPerm);
        $this->user->assignRole($role);
        $this->user->givePermissionTo($directPerm);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/me/permissions');

        $response->assertStatus(200);

        $permissions = $response->json('data.permissions');
        $this->assertContains('view vertretungsplan', $permissions);
        $this->assertContains('use messenger',        $permissions);

        $roles = $response->json('data.roles');
        $this->assertContains('Eltern', $roles);
    }

    // -------------------------------------------------------------------------
    // Unterschiedliche Benutzer erhalten eigene Daten
    // -------------------------------------------------------------------------

    /** @test */
    public function different_users_receive_their_own_permissions_and_roles(): void
    {
        Permission::firstOrCreate(['name' => 'view Pflichtstunden', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'upload files',        'guard_name' => 'web']);
        Role::create(['name' => 'Eltern',    'guard_name' => 'web']);
        Role::create(['name' => 'Lehrkraft', 'guard_name' => 'web']);

        $userA = $this->user;
        $userA->givePermissionTo('view Pflichtstunden');
        $userA->assignRole('Eltern');

        $userB = User::factory()->create();
        $userB->givePermissionTo('upload files');
        $userB->assignRole('Lehrkraft');

        // UserA
        Sanctum::actingAs($userA);
        $responseA = $this->getJson('/api/me/permissions');
        $responseA->assertStatus(200);
        $this->assertContains('view Pflichtstunden', $responseA->json('data.permissions'));
        $this->assertNotContains('upload files',     $responseA->json('data.permissions'));
        $this->assertContains('Eltern',    $responseA->json('data.roles'));
        $this->assertNotContains('Lehrkraft', $responseA->json('data.roles'));

        // UserB
        Sanctum::actingAs($userB);
        $responseB = $this->getJson('/api/me/permissions');
        $responseB->assertStatus(200);
        $this->assertContains('upload files',           $responseB->json('data.permissions'));
        $this->assertNotContains('view Pflichtstunden', $responseB->json('data.permissions'));
        $this->assertContains('Lehrkraft', $responseB->json('data.roles'));
        $this->assertNotContains('Eltern', $responseB->json('data.roles'));
    }
}

