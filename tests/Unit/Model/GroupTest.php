<?php

namespace Tests\Unit\Model;

use App\Model\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit-Tests für das Group Model
 */
class GroupTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function group_can_be_created_with_factory()
    {
        $group = Group::factory()->create();

        $this->assertInstanceOf(Group::class, $group);
        $this->assertNotNull($group->id);
    }

    /**
     * @test
     */
    public function group_has_name_attribute()
    {
        $group = Group::factory()->create([
            'name' => 'Test Group',
        ]);

        $this->assertEquals('Test Group', $group->name);
    }

    /**
     * @test
     */
    public function group_can_have_protected_flag()
    {
        $group = Group::factory()->create([
            'protected' => true,
        ]);

        $this->assertTrue($group->protected);
    }
}
