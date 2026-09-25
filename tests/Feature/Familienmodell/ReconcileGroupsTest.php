<?php

namespace Tests\Feature\Familienmodell;

use App\Model\Group;
use App\Services\Family\FamilyResolver;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsFamilies;
use Tests\TestCase;

/**
 * FAM-12 / §12.4: Abgleich manueller Klassen-Mitgliedschaften nach dem Kind-Import.
 */
class ReconcileGroupsTest extends TestCase
{
    use BuildsFamilies;

    #[Test]
    public function converts_explainable_memberships_and_reports_orphans(): void
    {
        $this->useResolver(FamilyResolver::MODE_CHILD_CENTRIC);
        $class = Group::factory()->create(['protected' => false]);
        $otherClass = Group::factory()->create(['protected' => false]);
        $elternrat = Group::factory()->create(['protected' => false]);
        $parent = $this->makeParent();
        $withoutChild = $this->makeParent();
        $parent->groups()->attach([$class->id, $elternrat->id]);          // Altbestand manuell
        $withoutChild->groups()->attach($otherClass->id);
        DB::table('children')->insert(['first_name' => 'X', 'last_name' => 'Y', 'class_id' => $otherClass->id, 'created_at' => now(), 'updated_at' => now()]);
        $child = $this->childFor([], ['class_id' => $class->id, 'group_id' => null]);
        DB::table('child_user')->insert(['child_id' => $child->id, 'user_id' => $parent->id, 'created_at' => now(), 'updated_at' => now()]);

        $this->artisan('groups:reconcile-parent-memberships', ['--dry-run' => true])->assertSuccessful();
        $this->assertFalse((bool) DB::table('group_user')->where('user_id', $parent->id)->where('group_id', $class->id)->value('is_auto_provisioned'));

        $this->artisan('groups:reconcile-parent-memberships', ['--remove-orphans' => true])
            ->expectsOutputToContain('Als abgeleitet übernommen: 1')
            ->assertSuccessful();

        $this->assertTrue((bool) DB::table('group_user')->where('user_id', $parent->id)->where('group_id', $class->id)->value('is_auto_provisioned'));
        $this->assertFalse((bool) DB::table('group_user')->where('user_id', $parent->id)->where('group_id', $elternrat->id)->value('is_auto_provisioned'));
        $this->assertFalse(DB::table('group_user')->where('user_id', $withoutChild->id)->where('group_id', $otherClass->id)->exists());
    }
}
