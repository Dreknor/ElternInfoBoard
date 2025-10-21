<?php

namespace Tests\Feature\Http\Controllers;

use App\Model\Termin;
use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\TerminController
 */
class TerminControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function create_returns_redirect_to_home()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('termin.create'));

        $response->assertRedirect(url('home'));
    }

    /**
     * @test
     */
    public function store_creates_new_termin()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('termin.store'), [
            'title' => 'Elternabend',
            'description' => 'Wichtiger Elternabend',
            'start' => now()->addDays(7)->format('Y-m-d H:i:s'),
            'end' => now()->addDays(7)->addHours(2)->format('Y-m-d H:i:s'),
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('termins', [
            'title' => 'Elternabend',
            'author_id' => $user->id,
        ]);
    }

    /**
     * @test
     */
    public function store_validates_with_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\TerminController::class,
            'store',
            \App\Http\Requests\CreateTerminRequest::class
        );
    }

    /**
     * @test
     */
    public function author_can_delete_own_termin()
    {
        $user = User::factory()->create();
        $termin = Termin::factory()->create(['author_id' => $user->id]);

        $response = $this->actingAs($user)->delete(route('termin.destroy', ['termin' => $termin]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('termins', ['id' => $termin->id]);
    }

    /**
     * @test
     */
    public function user_cannot_delete_others_termin()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $termin = Termin::factory()->create(['author_id' => $user2->id]);

        $response = $this->actingAs($user1)->delete(route('termin.destroy', ['termin' => $termin]));

        $response->assertForbidden();
        $this->assertDatabaseHas('termins', ['id' => $termin->id]);
    }

    /**
     * @test
     */
    public function unauthenticated_user_cannot_create_termin()
    {
        $response = $this->post(route('termin.store'), [
            'title' => 'Test Termin',
            'start' => now()->format('Y-m-d H:i:s'),
            'end' => now()->addHours(1)->format('Y-m-d H:i:s'),
        ]);

        $response->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function termin_requires_valid_dates()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('termin.store'), [
            'title' => 'Test Termin',
            'start' => 'invalid-date',
            'end' => 'invalid-date',
        ]);

        $response->assertSessionHasErrors(['start', 'end']);
    }
}

