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
    public function user_can_create_rueckmeldung(): void
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
    public function user_can_respond_to_rueckmeldung(): void
    {
        $rueckmeldung = Rueckmeldungen::factory()->create();
        $user = User::factory()->create(['password_changed_at' => now()]);
        $child = \App\Model\Child::factory()->create();

        UserRueckmeldungen::factory()->create([
            'post_id' => $rueckmeldung->post_id,
            'users_id' => $user->id,
            'child_id' => $child->id,
            'text' => 'Ja',
        ]);

        $this->assertDatabaseHas('users_rueckmeldungen', [
            'post_id' => $rueckmeldung->post_id,
            'users_id' => $user->id,
            'child_id' => $child->id,
            'text' => 'Ja',
        ]);
    }

    /**
     * @test
     */
    public function rueckmeldung_has_many_user_responses(): void
    {
        $rueckmeldung = Rueckmeldungen::factory()->create();
        UserRueckmeldungen::factory()->count(10)->create([
            'post_id' => $rueckmeldung->post_id,
        ]);

        $this->assertCount(10, $rueckmeldung->post->userRueckmeldung);
    }

    /**
     * @test
     */
    public function user_can_change_response(): void
    {
        $rueckmeldung = Rueckmeldungen::factory()->create();
        $user = User::factory()->create(['password_changed_at' => now()]);

        $userRueckmeldung = UserRueckmeldungen::factory()->create([
            'post_id' => $rueckmeldung->post_id,
            'users_id' => $user->id,
            'text' => 'Ja',
        ]);

        $userRueckmeldung->update(['text' => 'Nein']);

        $this->assertDatabaseHas('users_rueckmeldungen', [
            'id' => $userRueckmeldung->id,
            'text' => 'Nein',
        ]);
    }

    /**
     * @test
     */
    public function rueckmeldung_belongs_to_post(): void
    {
        $post = \App\Model\Post::factory()->create();
        $rueckmeldung = Rueckmeldungen::factory()->create(['post_id' => $post->id]);

        $this->assertInstanceOf(\App\Model\Post::class, $rueckmeldung->post);
        $this->assertEquals($post->id, $rueckmeldung->post->id);
    }
}
