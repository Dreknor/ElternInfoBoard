<?php

namespace Tests\Feature\Http\Controllers;

use App\Model\Child;
use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\ChildController
 */
class ChildControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function user_can_view_their_children(): void
    {
        $user = User::factory()->create(['password_changed_at' => now()]);
        \Spatie\Permission\Models\Permission::findOrCreate('edit schickzeiten', 'web');
        $user->givePermissionTo('edit schickzeiten');

        $children = Child::factory()->count(2)->create();
        $user->children_rel()->attach($children->pluck('id'));

        $response = $this->actingAs($user)->get(route('child.index'));

        $response->assertOk();
        $response->assertViewIs('child.index');
        $response->assertViewHas('children');
    }

    /**
     * @test
     * */
    public function user_can_create_child(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('child.store'),
            [
                'first_name' => 'Max',
                'last_name' => 'Mustermann',
                'notification' => true,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('children', [
            'first_name' => 'Max',
            'last_name' => 'Mustermann',
        ]);
    }

    /**
     * @test
     */
    public function user_can_update_their_child(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create();
        $user->children_rel()->attach($child->id);

        $response = $this->actingAs($user)->put(route('child.update', $child), [
            'first_name' => 'Updated Name',
            'last_name' => $child->last_name,
            'notification' => false,
            'auto_checkIn' => true,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('children', [
            'id' => $child->id,
            'first_name' => 'Updated Name',
            'auto_checkIn' => true,
        ]);
    }

    /**
     * @test
     */
    public function user_cannot_update_other_users_child(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $child = Child::factory()->create();
        $user2->children_rel()->attach($child->id);

        $response = $this->actingAs($user1)->put(route('child.update', $child), [
            'first_name' => 'Hacked Name',
            'last_name' => $child->last_name,
            'notification' => false,
            'auto_checkIn' => true,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseMissing('children', [
            'id' => $child->id,
            'first_name' => 'Hacked Name',
        ]);
    }

    /**
     * @test
     */
    public function parent_cannot_delete_child_but_staff_can(): void
    {
        // Kinder und Beziehungen pflegt ausschließlich die Verwaltung (Konzept E6)
        $user = User::factory()->create();
        $child = Child::factory()->create();
        $user->children_rel()->attach($child->id);

        $this->actingAs($user)->delete(route('child.destroy', $child))->assertRedirect();
        $this->assertNotSoftDeleted('children', ['id' => $child->id]);

        \Spatie\Permission\Models\Permission::findOrCreate('edit schickzeiten', 'web');
        $staff = User::factory()->create();
        $staff->givePermissionTo('edit schickzeiten');

        $this->actingAs($staff)->delete(route('child.destroy', $child))->assertRedirect();
        $this->assertSoftDeleted('children', ['id' => $child->id]);
    }

    /**
     * @test
     */
    public function unauthenticated_user_cannot_access_children(): void
    {
        $response = $this->get(route('child.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_care_staff_can_set_a_guardians_private_phone_from_child_settings(): void
    {
        \Spatie\Permission\Models\Permission::findOrCreate('edit schickzeiten', 'web');
        $staff = User::factory()->create(['password_changed_at' => now()]);
        $staff->givePermissionTo('edit schickzeiten');
        $guardian = User::factory()->create(['phone' => null, 'publicPhone' => '+49 111 222']);
        $child = Child::factory()->withGuardian($guardian)->create();

        $response = $this->actingAs($staff)->put(route('child.guardian.phone', [$child, $guardian]), [
            'phone' => '+49 333 444',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $guardian->id,
            'phone' => '+49 333 444',
            'publicPhone' => '+49 111 222',
        ]);

        $this->get(route('child.edit', $child))
            ->assertOk()
            ->assertSee('Nichtöffentliche Telefonnummern der Sorgeberechtigten')
            ->assertSee('+49 333 444');
    }

    public function test_care_staff_cannot_set_phone_for_a_non_custodial_contact(): void
    {
        \Spatie\Permission\Models\Permission::findOrCreate('edit schickzeiten', 'web');
        $staff = User::factory()->create(['password_changed_at' => now()]);
        $staff->givePermissionTo('edit schickzeiten');
        $contact = User::factory()->create(['phone' => null]);
        $child = Child::factory()->withGuardian($contact, \App\Enums\GuardianRelation::Other)->create();

        $this->actingAs($staff)
            ->put(route('child.guardian.phone', [$child, $contact]), ['phone' => '+49 333 444'])
            ->assertNotFound();

        $this->assertDatabaseHas('users', [
            'id' => $contact->id,
            'phone' => null,
        ]);
    }

    public function test_parent_cannot_set_guardians_private_phone(): void
    {
        $guardian = User::factory()->create(['password_changed_at' => now(), 'phone' => null]);
        $child = Child::factory()->withGuardian($guardian)->create();

        $this->actingAs($guardian)
            ->put(route('child.guardian.phone', [$child, $guardian]), ['phone' => '+49 333 444'])
            ->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $guardian->id,
            'phone' => null,
        ]);
    }
}
