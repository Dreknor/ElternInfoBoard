<?php

namespace Tests\Unit\Model;

use App\Model\Child;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit-Tests für das Child Model
 */
class ChildTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function child_can_be_created_with_factory(): void
    {
        $child = Child::factory()->create();

        $this->assertInstanceOf(Child::class, $child);
        $this->assertNotNull($child->id);
    }

    /**
     * @test
     */
    public function child_has_name_attribute(): void
    {
        $child = Child::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'Child',
        ])->fresh();

        $this->assertEquals('Test Child', $child->first_name.' '.$child->last_name);

    }

    /**
     * @test
     */
    public function child_auto_check_in_is_boolean(): void
    {
        $child = Child::factory()->create([
            'auto_check_in' => true,
        ]);

        $this->assertTrue(is_bool($child->auto_check_in));
    }
}
