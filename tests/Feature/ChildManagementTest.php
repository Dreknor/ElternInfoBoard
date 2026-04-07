<?php

namespace Tests\Feature;

use App\Model\Child;
use App\Model\Group;
use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-Tests für Child-Model und zugehörige Funktionalität
 */
class ChildManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function child_belongs_to_group(): void
    {
        $group = Group::factory()->create();
        $child = Child::factory()->create(['group_id' => $group->id]);

        $this->assertInstanceOf(Group::class, $child->group);
        $this->assertEquals($group->id, $child->group->id);
    }

    /**
     * @test
     */
    public function child_can_have_multiple_parents(): void
    {
        $child = Child::factory()->create();
        $parent1 = User::factory()->create();
        $parent2 = User::factory()->create();

        $child->parents()->attach([$parent1->id, $parent2->id]);

        $this->assertCount(2, $child->parents);
        $this->assertTrue($child->parents->contains($parent1));
        $this->assertTrue($child->parents->contains($parent2));
    }

    /**
     * @test
     */
    public function parent_can_access_child_information(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create();
        $user->children_rel()->attach($child->id);

        // Reload the user to get fresh relationship data
        $user = $user->fresh();

        $this->assertTrue($user->children_rel->contains($child));
        $this->assertEquals($child->first_name, $user->children_rel->first()->first_name);
    }

    /**
     * @test
     */
    public function child_notification_setting_works(): void
    {
        $childWithNotification = Child::factory()->create(['notification' => true]);
        $childWithoutNotification = Child::factory()->withoutNotification()->create();

        $this->assertTrue($childWithNotification->notification);
        $this->assertFalse($childWithoutNotification->notification);
    }

    /**
     * @test
     */
    public function child_auto_check_in_works(): void
    {
        $childWithAutoCheckIn = Child::factory()->withAutoCheckIn()->create();
        $childWithoutAutoCheckIn = Child::factory()->create(['auto_checkIn' => false]);

        $this->assertTrue($childWithAutoCheckIn->auto_checkIn);
        $this->assertFalse($childWithoutAutoCheckIn->auto_checkIn);
    }
}
