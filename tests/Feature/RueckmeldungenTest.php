<?php

namespace Tests\Feature;

use App\Model\Rueckmeldungen;
use App\Model\UserRueckmeldungen;
use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-Tests für Rückmeldungen-System
 */
class RueckmeldungenTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function user_can_create_rueckmeldung()
    {
        $user = User::factory()->create();

        $rueckmeldung = Rueckmeldungen::factory()->create([
            'author_id' => $user->id,
            'question' => 'Nehmen Sie teil?',
        ]);

        $this->assertDatabaseHas('rueckmeldungens', [
            'id' => $rueckmeldung->id,
            'author_id' => $user->id,
        ]);
    }

    /**
     * @test
     */
    public function user_can_respond_to_rueckmeldung()
    {
        $rueckmeldung = Rueckmeldungen::factory()->create();
        $user = User::factory()->create();

        $userRueckmeldung = UserRueckmeldungen::factory()->create([
            'rueckmeldung_id' => $rueckmeldung->id,
            'user_id' => $user->id,
            'response' => 'Ja',
        ]);

        $this->assertDatabaseHas('user_rueckmeldungens', [
            'rueckmeldung_id' => $rueckmeldung->id,
            'user_id' => $user->id,
            'response' => 'Ja',
        ]);
    }

    /**
     * @test
     */
    public function rueckmeldung_has_many_user_responses()
    {
        $rueckmeldung = Rueckmeldungen::factory()->create();
        $responses = UserRueckmeldungen::factory()->count(10)->create([
            'rueckmeldung_id' => $rueckmeldung->id,
        ]);

        $this->assertCount(10, $rueckmeldung->userRueckmeldungen);
    }

    /**
     * @test
     */
    public function user_can_change_response()
    {
        $rueckmeldung = Rueckmeldungen::factory()->create();
        $user = User::factory()->create();

        $userRueckmeldung = UserRueckmeldungen::factory()->create([
            'rueckmeldung_id' => $rueckmeldung->id,
            'user_id' => $user->id,
            'response' => 'Ja',
        ]);

        $userRueckmeldung->update(['response' => 'Nein']);

        $this->assertDatabaseHas('user_rueckmeldungens', [
            'id' => $userRueckmeldung->id,
            'response' => 'Nein',
        ]);
    }

    /**
     * @test
     */
    public function rueckmeldung_belongs_to_author()
    {
        $user = User::factory()->create();
        $rueckmeldung = Rueckmeldungen::factory()->create(['author_id' => $user->id]);

        $this->assertInstanceOf(User::class, $rueckmeldung->author);
        $this->assertEquals($user->id, $rueckmeldung->author->id);
    }
}

