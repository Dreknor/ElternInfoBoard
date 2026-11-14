<?php

namespace Tests\Feature\Security;

use App\Model\SecurityEvent;
use App\Model\User;
use App\Services\UserService;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Rollen-/Rechteänderungen, Passwortänderungen und Löschen von Protokollen.
 */
class PrivilegedActionLogTest extends TestCase
{
    private function events(string $event)
    {
        return SecurityEvent::where('event', $event)->orderBy('id')->get();
    }

    private function admin(string ...$permissions): User
    {
        $admin = User::factory()->create(['changePassword' => false]);
        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }
        $admin->givePermissionTo($permissions);

        return $admin;
    }

    public function test_rollenaenderung_eines_kontos(): void
    {
        $admin = $this->admin('edit permission');
        Role::firstOrCreate(['name' => 'Eltern']);
        Role::firstOrCreate(['name' => 'Administrator']);
        $user = User::factory()->create();
        $user->assignRole('Eltern');
        $this->actingAs($admin);

        app(UserService::class)->syncRoles($user, ['Eltern', 'Administrator'], $admin);
        app(UserService::class)->syncRoles($user, ['Eltern', 'Administrator'], $admin); // unverändert: kein Eintrag

        $e = $this->events('rbac.user_roles_changed')->sole();
        $this->assertSame($user->id, $e->user_id);
        $this->assertSame($admin->id, $e->actor_id);
        $this->assertSame(['Eltern'], $e->context['before']);
        $this->assertSame(['Administrator', 'Eltern'], $e->context['after']);
    }

    public function test_direktrechte_eines_kontos(): void
    {
        $admin = $this->admin('edit permission');
        Permission::firstOrCreate(['name' => 'see logs', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $this->actingAs($admin);

        app(UserService::class)->syncPermissions($user, ['see logs']);

        $e = $this->events('rbac.user_permissions_changed')->sole();
        $this->assertSame(['see logs'], $e->context['added']);
        $this->assertSame([], $e->context['removed']);
    }

    public function test_rollen_und_rechteverwaltung(): void
    {
        $admin = $this->admin('edit permission');
        $role = Role::firstOrCreate(['name' => 'Lehrer']);
        Permission::firstOrCreate(['name' => 'edit posts', 'guard_name' => 'web']);

        $this->actingAs($admin)->post('/roles', ['name' => 'Hort'])->assertRedirect();
        $this->actingAs($admin)->post('/roles/permission', ['name' => 'neues recht'])->assertRedirect();
        $this->actingAs($admin)->put('/roles', ['Lehrer' => ['edit posts']])->assertRedirect();

        $actions = $this->events('rbac.role_changed')->pluck('context.action')->all();
        $this->assertSame(['role_created', 'permission_created', 'permissions_synced'], $actions);
        $synced = $this->events('rbac.role_changed')->last();
        $this->assertSame($role->id, $synced->context['role_id']);
        $this->assertSame(['edit posts'], $synced->context['added']);
    }

    public function test_passwort_durch_admin_widerruft_tokens_und_remember_token(): void
    {
        $admin = $this->admin('edit user');
        $user = User::factory()->create();
        $user->createToken('App');
        $oldRemember = $user->fresh()->getRememberToken();
        $this->actingAs($admin);

        app(UserService::class)->setPassword($user, 'Neu-Gesetzt-123');

        $this->assertSame(0, $user->tokens()->count());
        $this->assertNotSame($oldRemember, $user->fresh()->getRememberToken());
        $e = $this->events('account.password_set_by_admin')->sole();
        $this->assertSame($user->id, $e->user_id);
        $this->assertSame($admin->id, $e->actor_id);
        $this->assertSame(1, $e->context['revoked_token_count']);
        $this->assertStringNotContainsString('Neu-Gesetzt-123', SecurityEvent::all()->toJson());
    }

    public function test_eigenes_passwort_im_web(): void
    {
        $user = User::factory()->create(['changePassword' => false, 'password' => Hash::make('Altes-Passwort1')]);
        $user->createToken('App');

        $this->actingAs($user)->put('/einstellungen', [
            'name' => $user->name,
            'email' => $user->email,
            'benachrichtigung' => 'weekly',
            'current_password' => 'Altes-Passwort1',
            'password' => 'Neues-Passwort-123!',
            'password_confirmation' => 'Neues-Passwort-123!',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('Neues-Passwort-123!', $user->fresh()->password));
        $this->assertSame(0, $user->tokens()->count());
        $this->assertSame('web', $this->events('account.password_changed')->sole()->context['client']);
    }

    public function test_eigenes_passwort_ueber_api_behaelt_aktuelles_token(): void
    {
        $user = User::factory()->create(['changePassword' => false, 'password' => Hash::make('Altes-Passwort1')]);
        $current = $user->createToken('App');
        $user->createToken('Tablet');

        $this->putJson('/api/v1/me/password', [
            'current_password' => 'Altes-Passwort1',
            'password' => 'Neues-Passwort-123',
            'password_confirmation' => 'Neues-Passwort-123',
        ], ['Authorization' => 'Bearer '.$current->plainTextToken])->assertOk();

        $this->assertSame([$current->accessToken->id], $user->tokens()->pluck('id')->all());
        $e = $this->events('account.password_changed')->sole();
        $this->assertSame(1, $e->context['revoked_token_count']);
    }

    public function test_abgelaufenes_passwort_aendern(): void
    {
        $user = User::factory()->create(['changePassword' => true, 'password' => Hash::make('Altes-Passwort1')]);
        $user->createToken('App');

        $this->actingAs($user)->post('/password/post_expired', [
            'current_password' => 'Altes-Passwort1',
            'password' => 'Neues-Passwort-123!',
            'password_confirmation' => 'Neues-Passwort-123!',
        ]);

        $this->assertSame(0, $user->tokens()->count());
        $this->assertSame('expired', $this->events('account.password_changed')->sole()->context['reason']);
    }

    public function test_loeschen_von_suchprotokollen_wird_protokolliert(): void
    {
        $admin = $this->admin('see logs', 'delete logs');

        $this->actingAs($admin)->delete('/logs/search/cleanup', ['days' => 30])->assertRedirect();

        $e = $this->events('logs.deleted')->sole();
        $this->assertSame('search_log', $e->context['type']);
        $this->assertSame(30, $e->context['older_than_days']);
        $this->assertSame(0, $e->context['count']);
    }
}
