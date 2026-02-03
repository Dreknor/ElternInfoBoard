<?php

namespace Tests\Feature\Http\Controllers;

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
    public function index_returns_an_ok_response(): void
    {
        $user = User::factory()->create(['password_changed_at' => now()]);

        Krankmeldungen::factory()->count(3)->create([
            'users_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('krankmeldung');

        $response->assertOk();
        $response->assertViewIs('krankmeldung.index');
        $response->assertViewHas('krankmeldungen');
    }

    /**
     * @test
     */
    public function store_creates_new_krankmeldung(): void
    {
        $user = User::factory()->create(['password_changed_at' => now()]);

        $response = $this->actingAs($user)->post('krankmeldung', [
            'name' => 'Test Kind',
            'start' => now()->format('Y-m-d'),
            'ende' => now()->addDays(2)->format('Y-m-d'),
            'kommentar' => 'Erkältung - wird Zuhause betreut',
        ]);

        $response->assertRedirect();

        // Der Name wird mit Gruppendaten angehängt, daher prüfen wir mit LIKE
        $this->assertDatabaseHas('krankmeldungen', [
            'users_id' => $user->id,
        ]);

        $krankmeldung = Krankmeldungen::where('users_id', $user->id)->first();
        $this->assertStringContainsString('Test Kind', $krankmeldung->name);
    }

    /**
     * @test
     */
    public function store_validates_with_a_form_request(): void
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
    public function unauthenticated_user_cannot_access_krankmeldung(): void
    {
        $response = $this->get('krankmeldung');

        $response->assertRedirect(route('login'));
    }

    /**
     * @test
     **/
    public function user_can_only_view_their_own_krankmeldungen(): void
    {
        $user1 = User::factory()->create(['password_changed_at' => now()]);
        $user2 = User::factory()->create(['password_changed_at' => now()]);

        $krankmeldung1 = Krankmeldungen::factory()->create([
            'users_id' => $user1->id,
        ]);

        $krankmeldung2 = Krankmeldungen::factory()->create([
            'users_id' => $user2->id,
        ]);

        $response = $this->actingAs($user1)->get('krankmeldung');

        $response->assertOk();
        $viewData = $response->viewData('krankmeldungen');
        $this->assertTrue($viewData->contains($krankmeldung1));
        $this->assertFalse($viewData->contains($krankmeldung2));
    }
}
