<?php

namespace Tests\Feature\Familienmodell;

use App\Enums\GuardianRelation;
use App\Model\Family;
use App\Services\Family\FamilyBuilder;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsFamilies;
use Tests\TestCase;

/**
 * FAM-08: Automatische Familienbildung (§7).
 */
class FamilyBuilderTest extends TestCase
{
    use BuildsFamilies;

    private function builder(): FamilyBuilder
    {
        return app(FamilyBuilder::class);
    }

    #[Test]
    public function single_parent_and_couple_get_families(): void
    {
        $single = $this->makeParent();
        $this->childFor([$single]);
        $a = $this->makeParent();
        $b = $this->makeParent();
        $this->childFor([$a, $b]);
        $this->childFor([$a, $b]);

        $report = $this->builder()->rebuild();

        $this->assertCount(2, $report->created);
        $this->assertNotNull($single->fresh()->family_id);
        $this->assertSame($a->fresh()->family_id, $b->fresh()->family_id);
        $this->assertNotSame($single->fresh()->family_id, $a->fresh()->family_id);
        $this->assertSame(Family::SOURCE_AUTO, $a->fresh()->family->source);
    }

    #[Test]
    public function three_guardians_with_identical_children_form_one_family(): void
    {
        [$a, $b, $g] = [$this->makeParent(), $this->makeParent(), $this->makeParent()];
        $x = $this->childFor([$a, $b]);
        $y = $this->childFor([$a, $b]);
        $this->linkGuardian($x, $g, GuardianRelation::Grandparent);
        $this->linkGuardian($y, $g, GuardianRelation::Grandparent);

        $report = $this->builder()->rebuild();

        $this->assertCount(1, $report->created);
        $this->assertSame([], $report->reviewCases);
        $this->assertSame(1, Family::count());
        $this->assertSame(3, Family::first()->users()->count());
    }

    #[Test]
    public function patchwork_becomes_review_case_without_changes(): void
    {
        [$a, $b, $c] = [$this->makeParent(), $this->makeParent(), $this->makeParent()];
        $this->childFor([$a, $b]);
        $this->childFor([$a, $c]);

        $report = $this->builder()->rebuild();

        $this->assertCount(1, $report->reviewCases);
        $this->assertEqualsCanonicalizing([$a->id, $b->id, $c->id], $report->reviewCases[0]['userIds']);
        $this->assertSame(0, Family::count());
        $this->assertCount(1, $this->builder()->reviewCases());
    }

    #[Test]
    public function locked_families_are_never_touched_and_split_components(): void
    {
        [$a, $b, $c] = [$this->makeParent(), $this->makeParent(), $this->makeParent()];
        $this->childFor([$a, $b]);
        $this->childFor([$a, $c]);
        // Verwaltung hat A und C als Familie festgelegt
        $locked = Family::factory()->locked()->create();
        $a->update(['family_id' => $locked->id]);
        $c->update(['family_id' => $locked->id]);

        $report = $this->builder()->rebuild();

        $this->assertSame([], $report->reviewCases);
        $this->assertSame($locked->id, $a->fresh()->family_id);
        $this->assertSame($locked->id, $c->fresh()->family_id);
        $this->assertNotNull($b->fresh()->family_id);
        $this->assertNotSame($locked->id, $b->fresh()->family_id);
    }

    #[Test]
    public function rebuild_is_idempotent(): void
    {
        [$a, $b] = [$this->makeParent(), $this->makeParent()];
        $this->childFor([$a, $b]);

        $this->builder()->rebuild();
        $second = $this->builder()->rebuild();

        $this->assertSame([], $second->created);
        $this->assertSame([], $second->merged);
        $this->assertCount(1, $second->unchanged);
        $this->assertSame(1, Family::count());
    }

    #[Test]
    public function dry_run_writes_nothing(): void
    {
        $a = $this->makeParent();
        $this->childFor([$a]);

        $report = $this->builder()->rebuild(dryRun: true);

        $this->assertCount(1, $report->created);
        $this->assertSame(0, Family::count());
        $this->assertNull($a->fresh()->family_id);
    }

    #[Test]
    public function only_unassigned_adds_new_guardian_to_existing_family(): void
    {
        [$a, $b] = [$this->makeParent(), $this->makeParent()];
        $family = $this->familyOf($a);
        $this->childFor([$a, $b]);

        $report = $this->builder()->rebuild(onlyUnassigned: true);

        $this->assertCount(1, $report->assigned);
        $this->assertSame($family->id, $b->fresh()->family_id);
    }

    #[Test]
    public function separate_auto_families_sharing_a_child_are_merged(): void
    {
        [$a, $b] = [$this->makeParent(), $this->makeParent()];
        $familyA = $this->familyOf($a);
        $this->familyOf($b);
        $this->childFor([$a, $b]);

        $report = $this->builder()->rebuild();

        $this->assertCount(1, $report->merged);
        $this->assertSame($familyA->id, $b->fresh()->family_id);
    }

    #[Test]
    public function scoping_to_users_only_touches_their_components(): void
    {
        $a = $this->makeParent();
        $this->childFor([$a]);
        $other = $this->makeParent();
        $this->childFor([$other]);

        $this->builder()->rebuild(onlyUserIds: [$a->id]);

        $this->assertNotNull($a->fresh()->family_id);
        $this->assertNull($other->fresh()->family_id);
    }

    #[Test]
    public function commands_run(): void
    {
        [$a, $b, $c] = [$this->makeParent(), $this->makeParent(), $this->makeParent()];
        $this->childFor([$a, $b]);
        $this->childFor([$a, $c]);

        $this->artisan('family:rebuild', ['--dry-run' => true])->assertSuccessful();
        $this->artisan('family:review')->expectsOutputToContain('Klärungsfall 1')->assertSuccessful();
    }
}
