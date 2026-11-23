<?php

namespace Tests\Feature;

use App\Model\SecurityEvent;
use App\Model\User;
use App\Settings\GeneralSetting;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Löschen von Rollen in der Rollen- und Rechteverwaltung.
 */
class RoleDeletionTest extends TestCase
{
    private function admin(): User
    {
        $admin = User::factory()->create(['changePassword' => false]);
        Permission::firstOrCreate(['name' => 'edit permission', 'guard_name' => 'web']);
        $admin->givePermissionTo('edit permission');

        return $admin;
    }

    public function test_rolle_ohne_benutzer_wird_ohne_namensbestaetigung_geloescht(): void
    {
        $admin = $this->admin();
        $role = Role::create(['name' => 'Hort']);

        $this->actingAs($admin)->from('/roles')->delete("/roles/{$role->id}")
            ->assertRedirect('/roles')
            ->assertSessionHas('type', 'success');

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_rolle_mit_benutzern_braucht_den_rollennamen(): void
    {
        $admin = $this->admin();
        $role = Role::create(['name' => 'Hort']);
        User::factory()->create()->assignRole($role);

        $this->actingAs($admin)->from('/roles')->delete("/roles/{$role->id}", ['confirm_name' => 'hort'])
            ->assertSessionHas('type', 'danger');
        $this->assertDatabaseHas('roles', ['id' => $role->id]);

        $this->actingAs($admin)->from('/roles')->delete("/roles/{$role->id}", ['confirm_name' => 'Hort'])
            ->assertSessionHas('type', 'success');
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_zuweisungen_werden_entfernt_und_andere_rollen_bleiben(): void
    {
        $admin = $this->admin();
        $role = Role::create(['name' => 'Hort']);
        Role::firstOrCreate(['name' => 'Eltern']);
        $user = User::factory()->create();
        $user->assignRole(['Hort', 'Eltern']);

        $this->actingAs($admin)->delete("/roles/{$role->id}", ['confirm_name' => 'Hort']);

        $this->assertSame(['Eltern'], $user->fresh()->getRoleNames()->all());
        $this->assertDatabaseMissing('model_has_roles', ['role_id' => $role->id]);
    }

    public function test_systemrollen_sind_gesperrt(): void
    {
        $admin = $this->admin();

        foreach (['Administrator', 'Mitarbeiter', 'Eltern', 'Elternrat', 'Aufnahme'] as $name) {
            $role = Role::firstOrCreate(['name' => $name]);

            $this->actingAs($admin)->delete("/roles/{$role->id}", ['confirm_name' => $name])
                ->assertSessionHas('type', 'danger');
            $this->assertDatabaseHas('roles', ['id' => $role->id]);
        }
    }

    public function test_kein_selbstaussperren_ueber_eigene_rolle(): void
    {
        $actor = User::factory()->create(['changePassword' => false]);
        Permission::firstOrCreate(['name' => 'edit permission', 'guard_name' => 'web']);
        $role = Role::create(['name' => 'Verwaltung']);
        $role->givePermissionTo('edit permission');
        $actor->assignRole($role);

        $this->actingAs($actor)->delete("/roles/{$role->id}", ['confirm_name' => 'Verwaltung'])
            ->assertSessionHas('type', 'danger');
        $this->assertDatabaseHas('roles', ['id' => $role->id]);

        // Mit dem Recht über eine weitere Rolle ist das Löschen erlaubt.
        $andere = Role::create(['name' => 'Technik']);
        $andere->givePermissionTo('edit permission');
        $actor->assignRole($andere);

        $this->actingAs($actor->fresh())->delete("/roles/{$role->id}", ['confirm_name' => 'Verwaltung'])
            ->assertSessionHas('type', 'success');
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_ohne_berechtigung_verboten(): void
    {
        $user = User::factory()->create(['changePassword' => false]);
        $role = Role::create(['name' => 'Hort']);

        $this->actingAs($user)->delete("/roles/{$role->id}")->assertForbidden();
        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    public function test_protokolliert_rolle_und_betroffene_benutzer(): void
    {
        $admin = $this->admin();
        Permission::firstOrCreate(['name' => 'view groups', 'guard_name' => 'web']);
        $role = Role::create(['name' => 'Hort']);
        $role->givePermissionTo('view groups');
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($admin)->delete("/roles/{$role->id}", ['confirm_name' => 'Hort']);

        $event = SecurityEvent::where('event', 'rbac.role_changed')->sole();
        $this->assertSame('role_deleted', $event->context['action']);
        $this->assertSame('Hort', $event->context['role']);
        $this->assertSame(['view groups'], $event->context['permissions']);
        $this->assertSame(1, $event->context['user_count']);

        $userEvent = SecurityEvent::where('event', 'rbac.user_roles_changed')->sole();
        $this->assertSame($user->id, $userEvent->user_id);
        $this->assertSame(['Hort'], $userEvent->context['before']);
        $this->assertSame([], $userEvent->context['after']);
    }

    public function test_entfernt_rolle_aus_protected_roles(): void
    {
        $admin = $this->admin();
        $role = Role::create(['name' => 'Schulbegleiter']);
        $settings = app(GeneralSetting::class);
        $settings->protected_roles = ['Administrator', 'Schulbegleiter'];
        $settings->save();

        $this->actingAs($admin)->delete("/roles/{$role->id}");

        $this->assertSame(['Administrator'], app(GeneralSetting::class)->refresh()->protected_roles);
    }

    public function test_verwaltungsseite_zeigt_benutzerzahlen(): void
    {
        $admin = $this->admin();
        Role::create(['name' => 'Hort']);
        Role::firstOrCreate(['name' => 'Eltern']);
        User::factory()->create()->assignRole('Hort');

        $response = $this->actingAs($admin)->get('/roles')->assertOk();

        $overview = collect($response->viewData('rollenVerwaltung'))->keyBy('name');
        $this->assertSame(1, $overview['Hort']['users_count']);
        $this->assertSame(1, $overview['Hort']['users_without_other_role']);
        $this->assertFalse($overview['Hort']['locked']);
        $this->assertTrue($overview['Eltern']['locked']);
    }
}
