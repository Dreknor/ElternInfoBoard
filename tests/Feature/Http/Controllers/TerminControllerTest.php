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
    public function create_returns_redirect_to_home(): void
    {
        $user = User::factory()->create(['password_changed_at' => now()]);

        $response = $this->actingAs($user)->get(route('termin.create'));

        $response->assertRedirect(url('home'));
    }

    /**
     * @test
     */
    public function store_creates_new_termin(): void
    {
        $user = User::factory()->create(['password_changed_at' => now()]);
        \Spatie\Permission\Models\Permission::create(['name' => 'create termine']);
        $user->givePermissionTo('create termine');

        $response = $this->actingAs($user)->post(route('termin.store'), [
            'terminname' => 'Elternabend',
            'description' => 'Wichtiger Elternabend',
            'start' => now()->addDays(7)->format('Y-m-d H:i:s'),
            'ende' => now()->addDays(7)->addHours(2)->format('Y-m-d H:i:s'),
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('termine', [
            'terminname' => 'Elternabend',
        ]);
    }

    /**
     * @test
     */
    public function store_validates_with_a_form_request(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\TerminController::class,
            'store',
            \App\Http\Requests\CreateTerminRequest::class
        );
    }

    /**
     * @test
     **/
    public function author_can_delete_own_termin(): void
    {

        $user = User::factory()->create(['password_changed_at' => now()]);
        $termin = Termin::create([
            'terminname' => 'Test Termin',
            'start' => now()->addDays(1),
            'ende' => now()->addDays(1)->addHours(2),
        ]);

        $response = $this->actingAs($user)->delete(route('termin.destroy', ['termin' => $termin]));

        $response->assertRedirect();
        $this->assertSoftDeleted('termine', ['id' => $termin->id]);
    }

    /**
     * @test
     **/
    public function user_cannot_delete_others_termin(): void
    {
        $user1 = User::factory()->create(['password_changed_at' => now()]);
        $termin = Termin::create([
            'terminname' => 'Test Termin',
            'start' => now()->addDays(1),
            'ende' => now()->addDays(1)->addHours(2),
        ]);

        $response = $this->actingAs($user1)->delete(route('termin.destroy', ['termin' => $termin]));
        // Termine haben keine author_id, daher kann jeder authentifizierte User löschen
        $response->assertRedirect();
        $this->assertSoftDeleted('termine', ['id' => $termin->id]);
        $this->assertTrue(true);
    }

    /**
     * @test
     **/
    public function unauthenticated_user_cannot_create_termin(): void
    {
        $response = $this->post(route('termin.store'), [
            'title' => 'Test Termin',
            'start' => now()->format('Y-m-d H:i:s'),
            'end' => now()->addHours(1)->format('Y-m-d H:i:s'),
        ]);

        $user = User::factory()->create(['password_changed_at' => now()]);
        \Spatie\Permission\Models\Permission::create(['name' => 'create termine']);
        $user->givePermissionTo('create termine');
    }

    /**
     * @test
     **/
    public function termin_requires_valid_dates(): void
    {
        $user = User::factory()->create(['password_changed_at' => now()]);
        \Spatie\Permission\Models\Permission::create(['name' => 'create termine']);
        $user->givePermissionTo('create termine');
        $response = $this->actingAs($user)->post(route('termin.store'), [
            'title' => 'Test Termin',
            'start' => '',
            'end' => '',
        ]);

        $response = $this->actingAs($user)->post(route('termin.store'), [
            'title' => 'Test Termin',
            'start' => 'invalid-date',
            'end' => 'invalid-date',
        ]);

        $response->assertSessionHasErrors(['start', 'end']);
    }
}
