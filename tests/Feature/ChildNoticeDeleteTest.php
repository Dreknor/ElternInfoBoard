<?php

namespace Tests\Feature;

use App\Model\ChildNotice;
use App\Model\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsFamilies;
use Tests\TestCase;

/**
 * Eltern löschen Notizen ihrer Kinder (Care-Modul); Fremde nicht.
 */
class ChildNoticeDeleteTest extends TestCase
{
    use BuildsFamilies;

    #[Test]
    public function parents_can_delete_notices_of_their_child_strangers_cannot(): void
    {
        $group = Group::factory()->create();
        $class = Group::factory()->create();
        $this->configureCare([$group->id], [$class->id]);
        [$a, $b, $child] = $this->coupleWithChildOfA(['group_id' => $group->id, 'class_id' => $class->id]);

        $first = ChildNotice::create(['child_id' => $child->id, 'notice' => 'Oma holt ab', 'date' => today()->addDay(), 'user_id' => $a->id]);
        $second = ChildNotice::create(['child_id' => $child->id, 'notice' => 'Bus', 'date' => today()->addDays(2), 'user_id' => $a->id]);

        $this->actingAs($this->makeParent())
            ->deleteJson(route('child.notice.destroy', $first))
            ->assertForbidden();
        $this->assertModelExists($first);

        $this->actingAs($a)->deleteJson(route('child.notice.destroy', $first))->assertOk();
        $this->assertSoftDeleted($first);

        $this->actingAs($b)->deleteJson(route('child.notice.destroy', $second))->assertOk();
        $this->assertSoftDeleted($second);
    }
}
