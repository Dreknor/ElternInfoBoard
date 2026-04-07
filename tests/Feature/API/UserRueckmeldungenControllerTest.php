<?php

namespace Tests\Feature\API;

use App\Model\Group;
use App\Model\Post;
use App\Model\Rueckmeldungen;
use App\Model\User;
use App\Model\UserRueckmeldungen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\API\UserRueckmeldungenController
 */
class UserRueckmeldungenControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function user_can_get_own_rueckmeldungen_for_post(): void
    {
        // Arrange: Create user with password_changed_at
        $user = User::factory()->create(['password_changed_at' => now()]);
        $group = Group::factory()->create();
        $group->users()->attach($user);

        $post = Post::factory()->create();
        $post->groups()->attach($group);

        $rueckmeldung = Rueckmeldungen::factory()->create([
            'post_id' => $post->id,
            'active' => true,
        ]);

        $userRueckmeldung = UserRueckmeldungen::factory()->create([
            'post_id' => $post->id,
            'users_id' => $user->id,
            'text' => 'Ich nehme teil',
        ]);

        // Act: Make authenticated request
        Sanctum::actingAs($user);
        $response = $this->getJson("/api/rueckmeldung/{$post->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment([
            'text' => 'Ich nehme teil',
            'users_id' => $user->id,
        ]);
    }

    /**
     * @test
     */
    public function user_can_get_rueckmeldungen_including_sorgeberechtigter2(): void
    {
        // Arrange: Create user and sorgeberechtigter2
        $sorg2 = User::factory()->create(['password_changed_at' => now()]);
        $user = User::factory()->create([
            'password_changed_at' => now(),
            'sorg2' => $sorg2->id,
        ]);

        $group = Group::factory()->create();
        $group->users()->attach($user);
        $group->users()->attach($sorg2);

        $post = Post::factory()->create();
        $post->groups()->attach($group);

        $rueckmeldung = Rueckmeldungen::factory()->create([
            'post_id' => $post->id,
            'active' => true,
        ]);

        // Create rueckmeldungen for both users
        $userRueckmeldung1 = UserRueckmeldungen::factory()->create([
            'post_id' => $post->id,
            'users_id' => $user->id,
            'text' => 'Ich nehme teil',
        ]);

        $userRueckmeldung2 = UserRueckmeldungen::factory()->create([
            'post_id' => $post->id,
            'users_id' => $sorg2->id,
            'text' => 'Wir nehmen auch teil',
        ]);

        // Act: Make authenticated request as user
        Sanctum::actingAs($user);
        $response = $this->getJson("/api/rueckmeldung/{$post->id}");

        // Assert: Should get both rueckmeldungen
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment([
            'text' => 'Ich nehme teil',
        ]);
        $response->assertJsonFragment([
            'text' => 'Wir nehmen auch teil',
        ]);
    }

    /**
     * @test
     */
    public function user_cannot_get_rueckmeldungen_for_unauthorized_post(): void
    {
        // Arrange
        $user = User::factory()->create(['password_changed_at' => now()]);
        $post = Post::factory()->create();

        // User is NOT in the post's groups

        // Act
        Sanctum::actingAs($user);
        $response = $this->getJson("/api/rueckmeldung/{$post->id}");

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'error' => 'User not allowed',
            'message' => 'Keine Berechtigung für diesen Beitrag',
        ]);
    }

    /**
     * @test
     */
    public function returns_404_for_nonexistent_post(): void
    {
        // Arrange
        $user = User::factory()->create(['password_changed_at' => now()]);

        // Act
        Sanctum::actingAs($user);
        $response = $this->getJson('/api/rueckmeldung/99999');

        // Assert
        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'error' => 'Post not found',
            'message' => 'Beitrag nicht gefunden',
        ]);
    }

    /**
     * @test
     */
    public function unauthenticated_user_cannot_get_rueckmeldungen(): void
    {
        // Arrange
        $post = Post::factory()->create();

        // Act: Request without authentication
        $response = $this->getJson("/api/rueckmeldung/{$post->id}");

        // Assert
        $response->assertStatus(401);
    }

    /**
     * @test
     */
    public function user_can_store_rueckmeldung(): void
    {
        // Arrange
        $user = User::factory()->create(['password_changed_at' => now()]);
        $group = Group::factory()->create();
        $group->users()->attach($user);

        $post = Post::factory()->create();
        $post->groups()->attach($group);

        $rueckmeldung = Rueckmeldungen::factory()->create([
            'post_id' => $post->id,
            'active' => true,
            'multiple' => 1,
        ]);

        // Act
        Sanctum::actingAs($user);
        $response = $this->postJson('/api/rueckmeldung', [
            'post_id' => $post->id,
            'text' => 'Ich nehme teil',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Rückmeldung abgegeben',
        ]);

        $this->assertDatabaseHas('users_rueckmeldungen', [
            'post_id' => $post->id,
            'users_id' => $user->id,
            'text' => 'Ich nehme teil',
        ]);
    }
}

