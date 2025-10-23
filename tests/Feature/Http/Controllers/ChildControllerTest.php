<?php

namespace Tests\Feature\Http\Controllers;

use App\Model\Child;
use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
    public function user_can_view_their_children()
    {
        $user = User::factory()->create(['password_changed_at' => now()]);
        \Spatie\Permission\Models\Permission::create(['name' => 'edit Schickzeiten']);
        $user->givePermissionTo('edit Schickzeiten');

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

    public function user_can_create_child()
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
    public function user_can_update_their_child()
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
    public function user_cannot_update_other_users_child()
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
        $this->assertDatabaseHas('children',
            [
            'id' => $child->id,
            'first_name' => 'Hacked Name'
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
    public function user_can_delete_their_child()
    {
        $user = User::factory()->create();
        $child = Child::factory()->create();
        $user->children_rel()->attach($child->id);

        $response = $this->actingAs($user)->delete(route('child.destroy', $child));

        $response->assertRedirect();

        $this->assertSoftDeleted('children', [
            'id' => $child->id,
        ]);
    }

    /**
     * @test
     */
    public function unauthenticated_user_cannot_access_children()
    {
        $response = $this->get(route('child.index'));

        $response->assertRedirect(route('login'));
    }
}

