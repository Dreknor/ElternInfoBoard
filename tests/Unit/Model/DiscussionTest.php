<?php

namespace Tests\Unit\Model;

use App\Model\Discussion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit-Tests für das Discussion Model
 */
class DiscussionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function discussion_can_be_created_with_factory()
    {
        $discussion = Discussion::factory()->create();

        $this->assertInstanceOf(Discussion::class, $discussion);
        $this->assertNotNull($discussion->id);
    }

    /**
     * @test
     */
    public function discussion_has_required_attributes()
    {
        $discussion = Discussion::factory()->create([
            'header' => 'Test Discussion',
            'text' => 'Test Content',
        ]);

        $this->assertEquals('Test Discussion', $discussion->header);
        $this->assertEquals('Test Content', $discussion->text);
    }

    /**
     * @test
     */
    public function discussion_can_be_sticky()
    {
        $discussion = Discussion::factory()->create([
            'sticky' => true,
        ]);

        $this->assertTrue($discussion->sticky);
    }

    /**
     * @test
     */
    public function discussion_can_be_soft_deleted()
    {
        $discussion = Discussion::factory()->create();
        $discussionId = $discussion->id;

        $discussion->delete();

        $this->assertSoftDeleted('discussions', ['id' => $discussionId]);
    }

    /**
     * @test
     */
    public function discussion_sticky_is_cast_to_boolean()
    {
        $discussion = Discussion::factory()->create([
            'sticky' => 1,
        ]);

        $this->assertTrue(is_bool($discussion->sticky));
    }
}
