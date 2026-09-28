<?php

namespace Tests\Feature\Familienmodell;

use App\Enums\GuardianRelation;
use App\Model\Arbeitsgemeinschaft;
use App\Model\ChildGuardian;
use App\Model\Conversation;
use App\Model\Family;
use App\Model\Group;
use App\Model\User;
use App\Services\Family\FamilyResolver;
use App\Services\Family\FamilyService;
use App\Services\Family\GroupMembershipService;
use App\Services\Family\GuardianshipService;
use App\Services\UserService;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsFamilies;
use Tests\TestCase;

/**
 * FAM-06: GuardianshipService, FamilyService, GroupMembershipService.
 */
class FamilyServicesTest extends TestCase
{
    use BuildsFamilies;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useResolver(FamilyResolver::MODE_CHILD_CENTRIC);
    }

    private function groupIds(User $user, bool $auto): array
    {
        return DB::table('group_user')->where('user_id', $user->id)->where('is_auto_provisioned', $auto)
            ->pluck('group_id')->map(fn ($id) => (int) $id)->sort()->values()->all();
    }

    // ── GroupMembershipService ───────────────────────────────────────────────

    #[Test]
    public function linking_a_child_derives_class_and_group_membership(): void
    {
        $class = Group::factory()->create(['protected' => false]);
        $hort = Group::factory()->create(['protected' => false]);
        $parent = $this->makeParent();
        $child = $this->childFor([], ['class_id' => $class->id, 'group_id' => $hort->id]);

        app(GuardianshipService::class)->link($child, $parent, GuardianRelation::Mother);

        $this->assertSame(collect([$class->id, $hort->id])->sort()->values()->all(), $this->groupIds($parent, true));
        $this->assertSame($child->id, (int) DB::table('group_user')->where('user_id', $parent->id)->where('group_id', $class->id)->value('provisioned_via_child_id'));
    }

    #[Test]
    public function manual_memberships_are_never_removed(): void
    {
        $elternrat = Group::factory()->create(['protected' => false]);
        $class = Group::factory()->create(['protected' => false]);
        $parent = $this->makeParent();
        $parent->groups()->attach([$elternrat->id, $class->id]); // Altbestand manuell
        $child = $this->childFor([$parent], ['class_id' => $class->id]);

        app(GroupMembershipService::class)->syncDerivedGroups($parent);
        app(GuardianshipService::class)->unlink($child, $parent);

        $this->assertEqualsCanonicalizing([$elternrat->id, $class->id], $this->groupIds($parent, false));
        $this->assertSame([], $this->groupIds($parent, true));
    }

    #[Test]
    public function class_change_moves_derived_membership_but_second_child_keeps_group(): void
    {
        $class1 = Group::factory()->create(['protected' => false]);
        $class2 = Group::factory()->create(['protected' => false]);
        $parent = $this->makeParent();
        $childA = $this->childFor([], ['class_id' => $class1->id, 'group_id' => null]);
        $childB = $this->childFor([], ['class_id' => $class1->id, 'group_id' => null]);
        $service = app(GuardianshipService::class);
        $service->link($childA, $parent);
        $service->link($childB, $parent);

        $childA->update(['class_id' => $class2->id]); // ChildObserver

        $this->assertEqualsCanonicalizing([$class1->id, $class2->id], $this->groupIds($parent, true));

        $childB->update(['class_id' => $class2->id]);
        $this->assertSame([$class2->id], $this->groupIds($parent, true));
    }

    #[Test]
    public function guardian_without_information_right_gets_no_groups(): void
    {
        $class = Group::factory()->create(['protected' => false]);
        $child = $this->childFor([], ['class_id' => $class->id, 'group_id' => null]);
        $observer = $this->makeParent();

        app(GuardianshipService::class)->link($child, $observer, GuardianRelation::Other, ['receives_information' => false]);

        $this->assertSame([], $this->groupIds($observer, true));
    }

    #[Test]
    public function additional_child_groups_and_deleted_children_are_respected(): void
    {
        $class = Group::factory()->create(['protected' => false]);
        $kombi = Group::factory()->create(['protected' => false]);
        $parent = $this->makeParent();
        $child = $this->childFor([], ['class_id' => $class->id, 'group_id' => null]);
        $child->additionalGroups()->attach($kombi->id, ['source' => 'ucs']);

        app(GuardianshipService::class)->link($child, $parent);
        $this->assertEqualsCanonicalizing([$class->id, $kombi->id], $this->groupIds($parent, true));

        $child->delete();
        $this->assertSame([], $this->groupIds($parent, true));
    }

    #[Test]
    public function derived_groups_join_and_leave_group_conversations(): void
    {
        $class = Group::factory()->create(['protected' => false]);
        $conversation = Conversation::withoutGlobalScopes()->create(['type' => 'group', 'group_id' => $class->id, 'is_active' => true, 'title' => 'Klasse', 'created_by' => $this->makeParent()->id]);
        $parent = $this->makeParent();
        $child = $this->childFor([], ['class_id' => $class->id, 'group_id' => null]);

        app(GuardianshipService::class)->link($child, $parent);
        $this->assertTrue($conversation->users()->where('users.id', $parent->id)->exists());

        app(GuardianshipService::class)->unlink($child, $parent);
        $this->assertFalse($conversation->users()->where('users.id', $parent->id)->exists());
    }

    #[Test]
    public function ag_group_follows_participation_and_adopts_manual_pivot(): void
    {
        $agGroup = Group::factory()->create(['name' => 'Schach-AG', 'protected' => false]);
        $ag = Arbeitsgemeinschaft::create([
            'name' => 'Schach-AG', 'weekday' => 1, 'start_time' => '14:00', 'end_time' => '15:00',
            'start_date' => now()->subMonth(), 'end_date' => now()->addMonth(), 'max_participants' => 10,
            'manager_id' => $this->makeParent()->id,
        ]);
        $parent = $this->makeParent();
        $parent->groups()->attach($agGroup->id); // früher manuell durch AG-Logik gesetzt
        $child = $this->childFor([$parent], ['class_id' => null, 'group_id' => null]);
        $ag->participants()->attach($child->id, ['user_id' => $parent->id]);

        app(GroupMembershipService::class)->syncForChild($child, [$agGroup->id]);
        $this->assertSame([$agGroup->id], $this->groupIds($parent, true));

        $ag->participants()->detach($child->id);
        app(GroupMembershipService::class)->syncForChild($child, [$agGroup->id]);
        $this->assertSame([], $this->groupIds($parent, true));
        $this->assertSame([], $this->groupIds($parent, false));
    }

    #[Test]
    public function admin_group_sync_keeps_derived_memberships(): void
    {
        $class = Group::factory()->create(['protected' => false]);
        $manual = Group::factory()->create(['protected' => false]);
        $parent = $this->makeParent();
        app(GuardianshipService::class)->link($this->childFor([], ['class_id' => $class->id, 'group_id' => null]), $parent);

        app(UserService::class)->syncGroups($parent, [$manual->id]);

        $this->assertSame([$class->id], $this->groupIds($parent, true));
        $this->assertSame([$manual->id], $this->groupIds($parent, false));
    }

    // ── GuardianshipService ──────────────────────────────────────────────────

    #[Test]
    public function link_applies_default_rights_and_keeps_existing_rights(): void
    {
        $child = $this->childFor([]);
        $grandma = $this->makeParent();
        $service = app(GuardianshipService::class);

        $pivot = $service->link($child, $grandma, GuardianRelation::Grandparent);
        $this->assertFalse($pivot->can_manage);

        $service->update($child, $grandma, ['can_manage' => true]);
        $pivot = $service->link($child, $grandma, GuardianRelation::Grandparent);
        $this->assertTrue($pivot->can_manage, 'erneutes link() überschreibt angepasste Rechte nicht');

        $pivot = $service->applyDefaultRights($child, $grandma);
        $this->assertFalse($pivot->can_manage);
    }

    #[Test]
    public function sync_from_source_only_touches_its_own_auto_links(): void
    {
        $parent = $this->makeParent();
        $manualChild = $this->childFor([$parent]);
        $ucsChild = $this->childFor([]);
        $otherUcsChild = $this->childFor([]);
        $service = app(GuardianshipService::class);

        $service->syncFromSource($parent, [$ucsChild->id, $otherUcsChild->id], ChildGuardian::SOURCE_UCS, detach: true);
        $result = $service->syncFromSource($parent, [$ucsChild->id], ChildGuardian::SOURCE_UCS, detach: true);

        $this->assertSame([$otherUcsChild->id], $result['detached']);
        $this->assertEqualsCanonicalizing([$manualChild->id, $ucsChild->id], $parent->children_rel()->pluck('children.id')->all());
        $this->assertSame('ucs', $service->pivot($ucsChild, $parent)->source);
    }

    #[Test]
    public function mark_reviewed_clears_pending_state(): void
    {
        $parent = $this->makeParent();
        $child = $this->childFor([]);
        app(GuardianshipService::class)->link($child, $parent, source: ChildGuardian::SOURCE_MIGRATION);

        $this->assertTrue(app(GuardianshipService::class)->pivot($child, $parent)->isPendingReview());
        app(GuardianshipService::class)->markReviewed($child, $parent);
        $this->assertFalse(app(GuardianshipService::class)->pivot($child, $parent)->isPendingReview());
    }

    // ── FamilyService ────────────────────────────────────────────────────────

    #[Test]
    public function create_family_dual_writes_sorg2_for_two_members(): void
    {
        $a = $this->makeParent(['name' => 'Anna Muster']);
        $b = $this->makeParent(['name' => 'Bernd Muster']);

        $family = app(FamilyService::class)->create([$a, $b]);

        $this->assertSame('Familie Muster', $family->name);
        $this->assertSame($b->id, $a->fresh()->sorg2);
        $this->assertSame($a->id, $b->fresh()->sorg2);
    }

    #[Test]
    public function third_member_keeps_pair_in_sorg2_and_removal_cleans_up(): void
    {
        $a = $this->makeParent();
        $b = $this->makeParent();
        $g = $this->makeParent();
        $service = app(FamilyService::class);
        $family = $service->create([$a, $b]);

        $service->addMember($family, $g);
        $this->assertSame([$a->id, $b->id, $g->id], $family->users()->orderBy('id')->pluck('id')->all());
        $this->assertSame($b->id, $a->fresh()->sorg2);
        $this->assertNull($g->fresh()->sorg2);

        $service->removeMember($a->fresh());
        $this->assertNull($a->fresh()->family_id);
        $this->assertNull($a->fresh()->sorg2);
        $this->assertSame($g->id, $b->fresh()->sorg2, 'verbleibende zwei Mitglieder werden gekoppelt');
    }

    #[Test]
    public function last_member_leaving_deletes_family(): void
    {
        $a = $this->makeParent();
        $family = app(FamilyService::class)->create([$a]);

        app(FamilyService::class)->removeMember($a->fresh());

        $this->assertSoftDeleted('families', ['id' => $family->id]);
    }

    #[Test]
    public function merge_and_split_lock_families(): void
    {
        $service = app(FamilyService::class);
        $f1 = $service->create([$a = $this->makeParent()]);
        $f2 = $service->create([$b = $this->makeParent(), $c = $this->makeParent()]);

        $merged = $service->merge($f1, $f2);
        $this->assertTrue($merged->is_locked);
        $this->assertSame(3, $merged->users()->count());
        $this->assertSoftDeleted('families', ['id' => $f2->id]);

        $new = $service->split($merged, [$c->id], 'Familie C');
        $this->assertTrue($new->is_locked);
        $this->assertSame([$c->id], $new->users()->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $merged->users()->pluck('id')->all());
    }

    #[Test]
    public function user_service_link_and_unlink_use_families(): void
    {
        $a = $this->makeParent();
        $b = $this->makeParent();
        $userService = app(UserService::class);

        $userService->linkSorgeberechtigte($a, $b->id);
        $this->assertNotNull($a->family_id);
        $this->assertSame($a->family_id, $b->fresh()->family_id);
        $this->assertSame($b->id, $a->sorg2);

        $userService->unlinkSorgeberechtigte($a);
        $this->assertNull($a->family_id);
        $this->assertNull($a->sorg2);
        $this->assertNull($b->fresh()->sorg2);
        $this->assertSame(1, Family::count());
    }

    #[Test]
    public function deleting_user_removes_family_membership_and_child_links(): void
    {
        [$a, $b, $child] = $this->coupleWithSharedChild();

        $error = app(UserService::class)->deleteUser($a->fresh());

        $this->assertSame('', $error);
        $this->assertNull($b->fresh()->sorg2);
        $this->assertSame([$b->id], $child->parents()->pluck('users.id')->all());
    }
}
