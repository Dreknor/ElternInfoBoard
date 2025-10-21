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
        $user = User::factory()->create();
        $child = Child::factory()->create();
        $user->children()->attach($child);

        $response = $this->actingAs($user)->get(route('children.index'));

        $response->assertOk();
        $response->assertSee($child->first_name);
        $response->assertSee($child->last_name);
    }

    /**
     * @test
     */
    public function user_can_create_child()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('children.store'), [
            'first_name' => 'Max',
            'last_name' => 'Mustermann',
            'notification' => true,
            'auto_checkIn' => false,
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
        $user->children()->attach($child);

        $response = $this->actingAs($user)->put(route('children.update', $child), [
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
        $user2->children()->attach($child);

        $response = $this->actingAs($user1)->put(route('children.update', $child), [
            'first_name' => 'Hacked Name',
            'last_name' => 'Test',
        ]);

        $response->assertForbidden();

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
        $user->children()->attach($child);

        $response = $this->actingAs($user)->delete(route('children.destroy', $child));

        $response->assertRedirect();

        $this->assertDatabaseMissing('children', [
            'id' => $child->id,
        ]);
    }

    /**
     * @test
     */
    public function unauthenticated_user_cannot_access_children()
    {
        $response = $this->get(route('children.index'));

        $response->assertRedirect(route('login'));
    }
}

