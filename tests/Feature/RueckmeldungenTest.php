<?php

namespace Tests\Feature;

use App\Model\Rueckmeldungen;
use App\Model\User;
use App\Model\UserRueckmeldungen;
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
        $user = User::factory()->create(['password_changed_at' => now()]);
        $post = \App\Model\Post::factory()->create();

        $rueckmeldung = Rueckmeldungen::factory()->create([
            'post_id' => $post->id,
            'text' => 'Nehmen Sie teil?',
        ]);

        $this->assertDatabaseHas('rueckmeldungen', [
            'id' => $rueckmeldung->id,
            'post_id' => $post->id,
            'text' => 'Nehmen Sie teil?',
        ]);
    }

    /**
     * @test
     */
    public function user_can_respond_to_rueckmeldung()
    {
        $rueckmeldung = Rueckmeldungen::factory()->create();
        $user = User::factory()->create(['password_changed_at' => now()]);

        $userRueckmeldung = UserRueckmeldungen::factory()->create([
            'rueckmeldung_id' => $rueckmeldung->id,
            'user_id' => $user->id,
            'response' => 'Ja',
        ]);

        $this->assertDatabaseHas('user_rueckmeldungen', [
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
        $user = User::factory()->create(['password_changed_at' => now()]);

        $userRueckmeldung = UserRueckmeldungen::factory()->create([
            'rueckmeldung_id' => $rueckmeldung->id,
            'user_id' => $user->id,
            'response' => 'Ja',
        ]);

        $userRueckmeldung->update(['response' => 'Nein']);

        $this->assertDatabaseHas('user_rueckmeldungen', [
            'id' => $userRueckmeldung->id,
            'response' => 'Nein',
        ]);
    }

    /**
     * @test
     */
    public function rueckmeldung_belongs_to_post()
    {
        $post = \App\Model\Post::factory()->create();
        $rueckmeldung = Rueckmeldungen::factory()->create(['post_id' => $post->id]);

        $this->assertInstanceOf(\App\Model\Post::class, $rueckmeldung->post);
        $this->assertEquals($post->id, $rueckmeldung->post->id);
    }
}
