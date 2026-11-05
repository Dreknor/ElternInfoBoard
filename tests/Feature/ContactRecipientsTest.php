<?php

namespace Tests\Feature;

use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Kontaktformular: auswählbar sind alle Nutzer mit „show in contact form“ –
 * direkt vergeben oder über eine beliebige Rolle. Die Rolle „Mitarbeiter“
 * allein genügt nicht mehr.
 */
class ContactRecipientsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function permission_via_any_role_or_direct_counts_role_name_alone_does_not(): void
    {
        $permission = Permission::findOrCreate('show in contact form', 'web');

        $viaRole = User::factory()->create(['name' => 'A via Rolle']);
        $viaRole->assignRole(Role::findOrCreate('Hortleitung', 'web')->givePermissionTo($permission));

        $direct = User::factory()->create(['name' => 'B direkt']);
        $direct->givePermissionTo($permission);

        $roleOnly = User::factory()->create(['name' => 'C nur Rolle']);
        $roleOnly->assignRole(Role::findOrCreate('Mitarbeiter', 'web'));

        $nobody = User::factory()->create(['name' => 'D ohne']);

        $ids = User::contactRecipients()->pluck('id');

        $this->assertTrue($ids->contains($viaRole->id));
        $this->assertTrue($ids->contains($direct->id));
        $this->assertFalse($ids->contains($roleOnly->id));
        $this->assertFalse($ids->contains($nobody->id));
        $this->assertCount(2, $ids);
    }

    /** @test */
    public function web_contact_form_lists_users_with_permission_via_role(): void
    {
        $permission = Permission::findOrCreate('show in contact form', 'web');
        $teacher = User::factory()->create(['name' => 'Frau Lehrkraft', 'changePassword' => false]);
        $teacher->assignRole(Role::findOrCreate('Hortleitung', 'web')->givePermissionTo($permission));

        $parent = User::factory()->create(['changePassword' => false]);

        $this->actingAs($parent)->get('/feedback')
            ->assertOk()
            ->assertViewHas('mitarbeiter', fn ($list) => $list->contains('id', $teacher->id));
    }
}
