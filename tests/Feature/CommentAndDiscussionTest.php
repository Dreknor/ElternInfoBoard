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
    public function user_can_create_comment()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'commentable_id' => $post->id,
            'commentable_type' => Post::class,
            'comment' => 'Dies ist ein Testkommentar',
        ]);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'user_id' => $user->id,
            'comment' => 'Dies ist ein Testkommentar',
        ]);
    }

    /**
     * @test
     */
    public function comment_belongs_to_user()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $comment->user);
        $this->assertEquals($user->id, $comment->user->id);
    }

    /**
     * @test
     */
    public function post_can_have_multiple_comments()
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
    public function user_can_create_discussion()
    {
        $user = User::factory()->create();

        $discussion = Discussion::factory()->create([
            'author_id' => $user->id,
            'title' => 'Wichtige Diskussion',
        ]);

        $this->assertDatabaseHas('discussions', [
            'id' => $discussion->id,
            'author_id' => $user->id,
            'title' => 'Wichtige Diskussion',
        ]);
    }

    /**
     * @test
     */
    public function discussion_can_have_comments()
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
    public function user_can_delete_own_comment()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $user->id]);

        $comment->delete();

        $this->assertDatabaseMissing('comments', [
            'id' => $comment->id,
        ]);
    }
}

