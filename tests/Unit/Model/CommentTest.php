<?php

namespace Tests\Unit\Model;

use App\Model\Comment;
use App\Model\Post;
use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit-Tests für das Comment Model
 */
class CommentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function comment_can_be_created_with_factory(): void
    {
        $comment = Comment::factory()->create();

        $this->assertInstanceOf(Comment::class, $comment);
        $this->assertNotNull($comment->id);
    }

    /**
     * @test
     */
    public function comment_has_body_attribute(): void
    {
        $comment = Comment::factory()->create([
            'body' => 'Test comment body',
        ]);

        $this->assertEquals('Test comment body', $comment->body);
    }

    /**
     * @test
     */
    public function comment_has_commentable_type(): void
    {
        $post = Post::factory()->create();
        $comment = Comment::factory()->create([
            'commentable_type' => Post::class,
            'commentable_id' => $post->id,
        ]);

        $this->assertEquals(Post::class, $comment->commentable_type);
        $this->assertEquals($post->id, $comment->commentable_id);
    }

    /**
     * @test
     */
    public function comment_has_creator(): void
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create([
            'creator_type' => User::class,
            'creator_id' => $user->id,
        ]);

        $this->assertEquals(User::class, $comment->creator_type);
        $this->assertEquals($user->id, $comment->creator_id);
    }

    /**
     * @test
     */
    public function comment_can_have_parent(): void
    {
        $parentComment = Comment::factory()->create([
            'parent_id' => null,
        ]);

        $childComment = Comment::factory()->create([
            'parent_id' => $parentComment->id,
        ]);

        $this->assertEquals($parentComment->id, $childComment->parent_id);
    }

    /**
     * @test
     */
    public function comment_has_nested_set_attributes(): void
    {
        $comment = Comment::factory()->create([
            '_lft' => 1,
            '_rgt' => 2,
        ]);

        $this->assertEquals(1, $comment->_lft);
        $this->assertEquals(2, $comment->_rgt);
    }
}
