<?php

namespace Tests\Feature\Familienmodell;

use App\Model\ChildGuardian;
use App\Model\Family;
use App\Model\GuardianLinkReport;
use App\Model\Group;
use App\Model\User;
use App\Services\Family\FamilyResolver;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\BuildsFamilies;
use Tests\TestCase;

/**
 * FAM-10: Admin-Oberfläche für Bezugspersonen und Familien (nur „manage families“, E6).
 */
class FamilyAdminTest extends TestCase
{
    use BuildsFamilies;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useResolver(FamilyResolver::MODE_CHILD_CENTRIC);
        foreach (['manage families', 'edit schickzeiten', 'edit user'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->admin = $this->makeParent();
        $this->admin->givePermissionTo(['manage families', 'edit schickzeiten', 'edit user']);
    }

    #[Test]
    public function parents_cannot_access_family_administration(): void
    {
        [$a, , $child] = $this->coupleWithSharedChild();

        $this->actingAs($a)->get(route('families.index'))->assertForbidden();
        $this->actingAs($a)->post(route('guardians.store', $child), ['user_id' => $this->makeParent()->id, 'relation' => 'grandparent'])->assertForbidden();
    }

    #[Test]
    public function admin_adds_grandparent_with_default_rights_and_updates_rights(): void
    {
        $class = Group::factory()->create(['protected' => false]);
        [, , $child] = $this->coupleWithSharedChild(['class_id' => $class->id]);
        $grandma = $this->makeParent();

        $this->actingAs($this->admin)->post(route('guardians.store', $child), ['user_id' => $grandma->id, 'relation' => 'grandparent'])
            ->assertSessionHas('type', 'success');

        $pivot = $child->parents()->where('users.id', $grandma->id)->first()->pivot;
        $this->assertFalse($pivot->has_custody);
        $this->assertFalse($pivot->can_manage);
        $this->assertTrue($pivot->receives_information);
        $this->assertTrue($grandma->groups()->where('groups.id', $class->id)->exists(), 'abgeleitete Klassengruppe');

        $this->actingAs($this->admin)->put(route('guardians.update', [$child, $grandma]), [
            'relation' => 'grandparent',
            'can_manage' => 1,
            'receives_information' => 1,
        ])->assertSessionHas('type', 'success');
        $this->assertTrue($child->parents()->where('users.id', $grandma->id)->first()->pivot->can_manage);

        $this->actingAs($this->admin)->post(route('guardians.defaults', [$child, $grandma]));
        $this->assertFalse($child->parents()->where('users.id', $grandma->id)->first()->pivot->can_manage);

        $this->actingAs($this->admin)->delete(route('guardians.destroy', [$child, $grandma]));
        $this->assertFalse($child->parents()->where('users.id', $grandma->id)->exists());
        $this->assertFalse($grandma->groups()->where('groups.id', $class->id)->exists());
    }

    #[Test]
    public function ucs_links_are_protected_from_accidental_removal(): void
    {
        $child = $this->childFor([]);
        $parent = $this->makeParent();
        $child->parents()->attach($parent->id, ['source' => ChildGuardian::SOURCE_UCS, 'is_auto_provisioned' => true]);

        $this->actingAs($this->admin)->delete(route('guardians.destroy', [$child, $parent]))->assertSessionHas('type', 'warning');
        $this->assertTrue($child->parents()->where('users.id', $parent->id)->exists());
    }

    #[Test]
    public function child_edit_page_shows_guardian_management(): void
    {
        [$a, , $child] = $this->coupleWithSharedChild();

        $this->actingAs($this->admin)->get(route('child.edit', $child))
            ->assertOk()
            ->assertSee('Bezugspersonen')
            ->assertSee($a->name)
            ->assertSee('Bezugsperson hinzufügen');
    }

    #[Test]
    public function family_pages_render_and_manage_members(): void
    {
        [$a, $b, , $family] = $this->coupleWithSharedChild();
        $single = $this->makeParent();
        $other = $this->familyOf($this->makeParent());

        $this->actingAs($this->admin)->get(route('families.index', ['search' => $a->name]))->assertOk()->assertSee($family->name);
        $this->actingAs($this->admin)->get(route('families.show', $family))->assertOk()->assertSee($b->name);

        $this->actingAs($this->admin)->post(route('families.members.add', $family), ['user_id' => $single->id]);
        $this->assertSame($family->id, $single->fresh()->family_id);
        $this->assertTrue($family->fresh()->is_locked);

        $this->actingAs($this->admin)->post(route('families.split', $family), ['user_ids' => [$single->id], 'name' => 'Familie Single'])->assertRedirect();
        $this->assertNotSame($family->id, $single->fresh()->family_id);

        $this->actingAs($this->admin)->post(route('families.merge', $family), ['source_id' => $other->id]);
        $this->assertSoftDeleted('families', ['id' => $other->id]);

        $this->actingAs($this->admin)->put(route('families.update', $family), ['name' => 'Familie Neu']);
        $this->assertSame('Familie Neu', $family->fresh()->name);
        $this->assertFalse($family->fresh()->is_locked);
    }

    #[Test]
    public function review_page_lists_cases_pending_links_and_reports(): void
    {
        [$a, $b, $c] = [$this->makeParent(), $this->makeParent(), $this->makeParent()];
        $this->childFor([$a, $b]);
        $this->childFor([$a, $c]);
        $migrated = $this->childFor([$a]);
        $migrated->parents()->attach($b->id, ['source' => ChildGuardian::SOURCE_MIGRATION]);
        GuardianLinkReport::create(['child_id' => $migrated->id, 'user_id' => $b->id, 'reported_by' => $b->id, 'note' => 'Nicht mein Kind']);

        $this->actingAs($this->admin)->get(route('families.review'))
            ->assertOk()
            ->assertSee('Fall 1')
            ->assertSee('Nicht mein Kind')
            ->assertSee($migrated->first_name);

        $this->actingAs($this->admin)->post(route('guardians.review', [$migrated, $b]));
        $this->assertFalse($migrated->parents()->where('users.id', $b->id)->first()->pivot->isPendingReview());
        $this->assertSame(0, GuardianLinkReport::query()->open()->count());
    }

    #[Test]
    public function marking_case_users_as_family_locks_it(): void
    {
        [$a, $c] = [$this->makeParent(), $this->makeParent()];

        $this->actingAs($this->admin)->post(route('families.store'), ['user_ids' => [$a->id, $c->id], 'name' => 'Familie AC'])->assertRedirect();

        $family = Family::where('name', 'Familie AC')->first();
        $this->assertTrue($family->is_locked);
        $this->assertSame(2, $family->users()->count());
    }

    #[Test]
    public function user_page_shows_family_and_children_and_links_family(): void
    {
        [$a, $b, $child] = $this->coupleWithSharedChild();

        $this->actingAs($this->admin)->get('users/'.$a->id)
            ->assertOk()
            ->assertSee($a->family->name)
            ->assertSee($b->name)
            ->assertSee($child->first_name);

        $this->actingAs($this->admin)->get('users/'.$a->id.'/remove/sorg2/0');
        $this->assertNull($a->fresh()->family_id);
    }
}
