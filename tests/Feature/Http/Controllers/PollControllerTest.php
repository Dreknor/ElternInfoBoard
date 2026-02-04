<?php

namespace Tests\Feature\Http\Controllers;

use App\Model\Poll;
use App\Model\Poll_Option;
use App\Model\Post;
use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\PollController
 */
class PollControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function store_creates_new_poll(): void
    {
        $user = User::factory()->create(['password_changed_at' => now()]);
        $post = Post::factory()->create(['author' => $user->id]);

        $response = $this->actingAs($user)->post("poll/{$post->id}/create", [
            'question' => 'Was ist deine Lieblingsfarbe?',
            'max_number' => 1,
            'multiple' => false,
            'options' => ['Rot', 'Blau', 'Grün'],
        ]);

        $response->assertRedirect(url('/'));

        $this->assertDatabaseHas('polls', [
            'post_id' => $post->id,
            'question' => 'Was ist deine Lieblingsfarbe?',
            'author_id' => $user->id,
        ]);

        $this->assertDatabaseHas('poll__options', [
            'option' => 'Rot',
        ]);
    }

    /**
     * @test
     */
    public function store_validates_with_a_form_request(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\PollController::class,
            'store',
            \App\Http\Requests\StorePollRequest::class
        );
    }

    /**
     * @test
     */
    public function vote_allows_user_to_vote_on_poll(): void
    {
        $user = User::factory()->create(['password_changed_at' => now()]);
        $post = Post::factory()->create();
        $poll = Poll::factory()->create([
            'post_id' => $post->id,
            'max_number' => 1,
        ]);
        $option = Poll_Option::factory()->create(['poll_id' => $poll->id]);

        $response = $this->actingAs($user)->post("poll/{$post->id}/vote", [
            "{$poll->id}_answers" => [$option->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('type', 'success');

        $this->assertDatabaseHas('poll__votes', [
            'poll_id' => $poll->id,
            'author_id' => $user->id,
        ]);

        $this->assertDatabaseHas('poll__answers', [
            'poll_id' => $poll->id,
            'option_id' => $option->id,
        ]);
    }

    /**
     * @test
     */
    public function vote_prevents_duplicate_votes(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $poll = Poll::factory()->create(['post_id' => $post->id]);
        $option = Poll_Option::factory()->create(['poll_id' => $poll->id]);

        // Erste Abstimmung
        $poll->votes()->create(['author_id' => $user->id]);

        // Versuch einer zweiten Abstimmung
        $response = $this->actingAs($user)->post("poll/{$post->id}/vote", [
            "{$poll->id}_answers" => [$option->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('type', 'warning');
        $response->assertSessionHas('Meldung', 'Stimme wurde bereits abgegeben.');
    }

    /**
     * @test
     */
    public function vote_prevents_too_many_answers(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $poll = Poll::factory()->create([
            'post_id' => $post->id,
            'max_number' => 1,
        ]);
        $option1 = Poll_Option::factory()->create(['poll_id' => $poll->id]);
        $option2 = Poll_Option::factory()->create(['poll_id' => $poll->id]);

        $response = $this->actingAs($user)->post("poll/{$post->id}/vote", [
            "{$poll->id}_answers" => [$option1->id, $option2->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('type', 'warning');
        $response->assertSessionHas('Meldung', 'Zuviele Antworten ausgewählt.');
    }

    /**
     * @test
     */
    public function update_validates_with_a_form_request(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\PollController::class,
            'update',
            \App\Http\Requests\UpdatePollRequest::class
        );
    }

    /**
     * @test
     */
    public function unauthenticated_user_cannot_create_poll(): void
    {
        $post = Post::factory()->create();

        $response = $this->post("poll/{$post->id}/create", [
            'question' => 'Test Question',
            'options' => ['Option 1', 'Option 2'],
        ]);

        $response->assertRedirect(route('login'));
    }
}
