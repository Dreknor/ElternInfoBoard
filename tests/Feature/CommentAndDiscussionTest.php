<?php

namespace Tests\Feature;

use App\Model\Comment;
use App\Model\Discussion;
use App\Model\Post;
use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-Tests für Kommentar- und Diskussionsfunktionen
 */
class CommentAndDiscussionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function user_can_create_comment(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $comment = Comment::factory()->create([
            'creator_id' => $user->id,
            'creator_type' => User::class,
            'commentable_id' => $post->id,
            'commentable_type' => Post::class,
            'body' => 'Dies ist ein Testkommentar',
        ]);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'creator_id' => $user->id,
            'body' => 'Dies ist ein Testkommentar',
        ]);
    }

    /**
     * @test
     */
    public function comment_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create([
            'creator_id' => $user->id,
            'creator_type' => User::class,
        ]);

        $this->assertInstanceOf(User::class, $comment->creator);
        $this->assertEquals($user->id, $comment->creator->id);
    }

    /**
     * @test
     */
    public function post_can_have_multiple_comments(): void
    {
        $post = Post::factory()->create();
        $comments = Comment::factory()->count(5)->create([
            'commentable_id' => $post->id,
            'commentable_type' => Post::class,
        ]);

        $this->assertEquals(5, $post->comments()->count());
    }

    /**
     * @test
     */
    public function user_can_create_discussion(): void
    {
        $user = User::factory()->create();

        $discussion = Discussion::factory()->create([
            'owner' => $user->id,
            'header' => 'Wichtige Diskussion',
        ]);

        $this->assertDatabaseHas('discussions', [
            'id' => $discussion->id,
            'owner' => $user->id,
            'header' => 'Wichtige Diskussion',
        ]);
    }

    /**
     * @test
     */
    public function discussion_can_have_comments(): void
    {
        $discussion = Discussion::factory()->create();
        $comments = Comment::factory()->count(10)->create([
            'commentable_id' => $discussion->id,
            'commentable_type' => Discussion::class,
        ]);

        $this->assertEquals(10, $discussion->comments()->count());
    }

    /**
     * @test
     */
    public function user_can_delete_own_comment(): void
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create([
            'creator_id' => $user->id,
            'creator_type' => User::class,
        ]);

        $comment->delete();

        $this->assertDatabaseMissing('comments', [
            'id' => $comment->id,
        ]);
    }
}
