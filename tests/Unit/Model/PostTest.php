<?php

namespace Tests\Unit\Model;

use App\Model\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit-Tests für das Post Model
 */
class PostTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function post_can_be_created_with_factory(): void
    {
        $post = Post::factory()->create();

        $this->assertInstanceOf(Post::class, $post);
        $this->assertNotNull($post->id);
    }

    /**
     * @test
     */
    public function post_has_required_attributes(): void
    {
        $post = Post::factory()->create([
            'header' => 'Test Header',
            'news' => 'Test Content',
        ]);

        $this->assertEquals('Test Header', $post->header);
        $this->assertEquals('Test Content', $post->news);
    }

    /**
     * @test
     */
    public function post_can_be_released(): void
    {
        $post = Post::factory()->create([
            'released' => true,
        ]);

        $this->assertTrue($post->released);
    }

    /**
     * @test
     */
    public function post_can_be_sticky(): void
    {
        $post = Post::factory()->create([
            'sticky' => true,
        ]);

        $this->assertTrue($post->sticky);
    }

    /**
     * @test
     */
    public function post_can_be_reactable(): void
    {
        $post = Post::factory()->create([
            'reactable' => true,
        ]);

        $this->assertTrue($post->reactable);
    }

    /**
     * @test
     */
    public function post_has_type_attribute(): void
    {
        $post = Post::factory()->create([
            'type' => 'news',
        ]);

        $this->assertEquals('news', $post->type);
    }

    /**
     * @test
     */
    public function post_can_be_external(): void
    {
        $post = Post::factory()->create([
            'external' => true,
        ]);

        $this->assertTrue($post->external);
    }

    /**
     * @test
     */
    public function post_can_have_read_receipt(): void
    {
        $post = Post::factory()->create([
            'read_receipt' => true,
        ]);

        $this->assertTrue($post->read_receipt);
    }

    /**
     * @test
     */
    public function post_can_have_no_header(): void
    {
        $post = Post::factory()->create([
            'no_header' => true,
        ]);

        $this->assertTrue($post->no_header);
    }

    /**
     * @test
     */
    public function post_can_be_soft_deleted(): void
    {
        $post = Post::factory()->create();
        $postId = $post->id;

        $post->delete();

        $this->assertSoftDeleted('posts', ['id' => $postId]);
    }

    /**
     * @test
     */
    public function post_archiv_ab_is_cast_to_datetime(): void
    {
        $date = now()->addDays(30);
        $post = Post::factory()->create([
            'archiv_ab' => $date,
        ]);

        $this->assertInstanceOf(\DateTime::class, $post->archiv_ab);
    }
}
