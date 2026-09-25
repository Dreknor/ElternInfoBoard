<?php

namespace Tests\Feature\Familienmodell;

use App\Enums\GuardianRelation;
use App\Enums\GuardianRight;
use App\Model\Child;
use App\Model\ChildGuardian;
use App\Model\Family;
use App\Model\Rueckmeldungen;
use App\Model\User;
use App\Settings\PflichtstundenSetting;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * FAM-02: Schema-Erweiterungen und Models des kind-zentrierten Familienmodells.
 */
class GuardianshipSchemaTest extends TestCase
{
    #[Test]
    public function legacy_link_gets_all_rights_and_manual_source_by_default(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create();
        $child->parents()->attach($user->id);

        /** @var ChildGuardian $pivot */
        $pivot = $child->parents()->first()->pivot;

        $this->assertInstanceOf(ChildGuardian::class, $pivot);
        $this->assertTrue($pivot->has_custody);
        $this->assertTrue($pivot->receives_information);
        $this->assertTrue($pivot->can_manage);
        $this->assertSame('manual', $pivot->source);
        $this->assertFalse($pivot->isPendingReview());
    }

    #[Test]
    public function child_user_rejects_duplicate_links(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create();
        $child->parents()->attach($user->id);

        $this->expectException(QueryException::class);
        DB::table('child_user')->insert(['child_id' => $child->id, 'user_id' => $user->id]);
    }

    #[Test]
    public function relation_defaults_follow_concept_e5(): void
    {
        $this->assertSame(['has_custody' => true, 'receives_information' => true, 'can_manage' => true], GuardianRelation::Father->defaultRights());
        $this->assertSame(['has_custody' => false, 'receives_information' => true, 'can_manage' => true], GuardianRelation::Partner->defaultRights());
        $this->assertSame(['has_custody' => false, 'receives_information' => true, 'can_manage' => false], GuardianRelation::Grandparent->defaultRights());
        $this->assertSame(GuardianRelation::Grandparent, GuardianRelation::fromInput('Oma'));
        $this->assertSame(GuardianRelation::LegalGuardian, GuardianRelation::fromInput(''));
    }

    #[Test]
    public function factory_applies_relation_default_rights(): void
    {
        $grandma = User::factory()->create();
        $child = Child::factory()->withGuardian($grandma, GuardianRelation::Grandparent)->create();

        $pivot = $child->parents()->first()->pivot;
        $this->assertSame('grandparent', $pivot->relation);
        $this->assertTrue($pivot->grants(GuardianRight::Information));
        $this->assertFalse($pivot->grants(GuardianRight::Custody));
        $this->assertFalse($pivot->grants(GuardianRight::Manage));
    }

    #[Test]
    public function expired_relation_grants_nothing(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->withGuardian($user, GuardianRelation::FosterParent, ['valid_until' => now()->subDay()->toDateString()])->create();

        $this->assertFalse($child->parents()->first()->pivot->grants(GuardianRight::Manage));
    }

    #[Test]
    public function migrated_link_is_pending_review_until_reviewed(): void
    {
        $user = User::factory()->create();
        $child = Child::factory()->create();
        $child->parents()->attach($user->id, ['source' => ChildGuardian::SOURCE_MIGRATION]);

        $pivot = $child->parents()->first()->pivot;
        $this->assertTrue($pivot->isPendingReview());
        $this->assertSame('aus früherer Kontoverknüpfung übernommen', $pivot->sourceLabel());

        $child->parents()->updateExistingPivot($user->id, ['reviewed_at' => now()]);
        $this->assertFalse($child->parents()->first()->pivot->isPendingReview());
    }

    #[Test]
    public function family_groups_members_and_derives_children(): void
    {
        $family = Family::factory()->create();
        $a = User::factory()->create(['family_id' => $family->id]);
        $b = User::factory()->create(['family_id' => $family->id]);
        $outsider = User::factory()->create();
        $childA = Child::factory()->withGuardian($a)->create();
        $childB = Child::factory()->withGuardian($b)->create();
        $shared = Child::factory()->withGuardian($a)->withGuardian($b)->create();
        Child::factory()->withGuardian($outsider)->create();

        $this->assertEqualsCanonicalizing([$a->id, $b->id], $family->users()->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $a->familyMembers()->pluck('id')->all());
        $this->assertSame([$outsider->id], $outsider->familyMembers()->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$childA->id, $childB->id, $shared->id], $family->children()->pluck('id')->all());
        $this->assertTrue($a->family->is($family));
    }

    #[Test]
    public function deleting_family_detaches_members(): void
    {
        $family = Family::factory()->create();
        $user = User::factory()->create(['family_id' => $family->id]);

        $family->forceDelete();

        $this->assertNull($user->fresh()->family_id);
    }

    #[Test]
    public function suggested_family_name_uses_last_names(): void
    {
        $a = User::factory()->make(['name' => 'Anna Muster']);
        $b = User::factory()->make(['name' => 'Bernd Muster']);
        $c = User::factory()->make(['name' => 'Clara Beispiel']);

        $this->assertSame('Familie Muster', Family::suggestName([$a, $b]));
        $this->assertSame('Familie Muster / Beispiel', Family::suggestName([$a, $c]));
    }

    #[Test]
    public function new_rueckmeldung_defaults_to_child_scope(): void
    {
        $rueckmeldung = Rueckmeldungen::factory()->create();

        $this->assertSame('child', $rueckmeldung->fresh()->scope);
    }

    #[Test]
    public function external_id_is_unique(): void
    {
        Child::factory()->create(['external_id' => 'S-1']);

        $this->expectException(QueryException::class);
        Child::factory()->create(['external_id' => 'S-1']);
    }

    #[Test]
    public function pflichtstunden_defaults_keep_family_basis(): void
    {
        $settings = app(PflichtstundenSetting::class);

        $this->assertSame('family', $settings->pflichtstunden_basis);
        $this->assertSame('combined', $settings->pflichtstunden_geteilte_kinder);
        $this->assertNull($settings->pflichtstunden_max_kinder);
        $this->assertSame([], $settings->pflichtstunden_kinder_gruppen);
    }

    #[Test]
    public function manage_families_permission_exists(): void
    {
        $this->assertTrue(Permission::where('name', 'manage families')->where('guard_name', 'web')->exists());
    }
}
