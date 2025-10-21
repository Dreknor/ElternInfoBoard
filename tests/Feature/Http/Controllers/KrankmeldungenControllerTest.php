<?php

namespace Tests\Feature\Http\Controllers;

use App\Model\Child;
use App\Model\Krankmeldungen;
use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\KrankmeldungenController
 */
class KrankmeldungenControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function index_returns_an_ok_response()
    {
        $user = User::factory()->create();
        $child = Child::factory()->create();
        $user->children()->attach($child);

        Krankmeldungen::factory()->count(3)->create([
            'child_id' => $child->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('krankmeldung');

        $response->assertOk();
        $response->assertViewIs('krankmeldung.index');
        $response->assertViewHas('krankmeldungen');
    }

    /**
     * @test
     */
    public function store_creates_new_krankmeldung()
    {
        $user = User::factory()->create();
        $child = Child::factory()->create();
        $user->children()->attach($child);

        $response = $this->actingAs($user)->post('krankmeldung', [
            'child_id' => $child->id,
            'date' => now()->format('Y-m-d'),
            'reason' => 'Erkältung',
            'comment' => 'Wird Zuhause betreut',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('krankmeldungens', [
            'child_id' => $child->id,
            'user_id' => $user->id,
            'reason' => 'Erkältung',
        ]);
    }

    /**
     * @test
     */
    public function store_validates_with_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\KrankmeldungenController::class,
            'store',
            \App\Http\Requests\KrankmeldungRequest::class
        );
    }

    /**
     * @test
     */
    public function unauthenticated_user_cannot_access_krankmeldung()
    {
        $response = $this->get('krankmeldung');

        $response->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function user_can_only_see_own_krankmeldungen()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $child1 = Child::factory()->create();
        $child2 = Child::factory()->create();

        $user1->children()->attach($child1);
        $user2->children()->attach($child2);

        $krankmeldung1 = Krankmeldungen::factory()->create([
            'child_id' => $child1->id,
            'user_id' => $user1->id,
        ]);

        $krankmeldung2 = Krankmeldungen::factory()->create([
            'child_id' => $child2->id,
            'user_id' => $user2->id,
        ]);

        $response = $this->actingAs($user1)->get('krankmeldung');

        $response->assertOk();
        $viewData = $response->viewData('krankmeldungen');
        $this->assertTrue($viewData->contains($krankmeldung1));
        $this->assertFalse($viewData->contains($krankmeldung2));
    }
}
