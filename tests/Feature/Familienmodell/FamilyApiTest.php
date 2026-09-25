<?php

namespace Tests\Feature\Familienmodell;

use App\Enums\GuardianRelation;
use App\Model\Pflichtstunde;
use App\Services\Family\FamilyResolver;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\BuildsFamilies;
use Tests\TestCase;

/**
 * FAM-14: API-Erweiterungen (nur additiv – bestehende Strukturen bleiben).
 */
class FamilyApiTest extends TestCase
{
    use BuildsFamilies;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useResolver(FamilyResolver::MODE_CHILD_CENTRIC);
    }

    #[Test]
    public function parent_children_contains_guardians_and_own_rights_additively(): void
    {
        [$a, $b, $child] = $this->coupleWithSharedChild();
        $grandma = $this->makeParent();
        $this->linkGuardian($child, $grandma, GuardianRelation::Grandparent);

        Sanctum::actingAs($grandma);
        $this->getJson('api/parent/children')
            ->assertOk()
            ->assertJsonStructure(['success', 'count', 'data' => [[
                'id', 'first_name', 'last_name', 'full_name', 'group_id', 'group', 'class_id', 'class',
                'notification', 'auto_checkIn', 'is_in_care_module',
                'guardians' => [['id', 'name', 'relation', 'relation_label', 'has_custody', 'receives_information', 'can_manage']],
                'my_relation', 'my_rights' => ['custody', 'information', 'manage', 'view_health'],
            ]]])
            ->assertJsonPath('data.0.my_relation', 'grandparent')
            ->assertJsonPath('data.0.my_rights.manage', false)
            ->assertJsonPath('data.0.my_rights.information', true)
            ->assertJsonCount(3, 'data.0.guardians');
    }

    #[Test]
    public function family_endpoint_returns_members_and_children(): void
    {
        [$a, $b, $child, $family] = $this->coupleWithSharedChild();

        Sanctum::actingAs($a);
        $this->getJson('api/family')
            ->assertOk()
            ->assertJsonPath('data.id', $family->id)
            ->assertJsonPath('data.name', $family->name)
            ->assertJsonCount(2, 'data.members')
            ->assertJsonPath('data.children.0.id', $child->id);
    }

    #[Test]
    public function relations_endpoint_lists_own_links_with_source_and_review_state(): void
    {
        $user = $this->makeParent();
        $child = $this->childFor([]);
        $child->parents()->attach($user->id, ['source' => 'migration']);

        Sanctum::actingAs($user);
        $this->getJson('api/user/relations')
            ->assertOk()
            ->assertJsonPath('children.0.id', $child->id)
            ->assertJsonPath('children.0.source', 'migration')
            ->assertJsonPath('children.0.pending_review', true)
            ->assertJsonPath('children.0.rights.custody', true);
    }

    #[Test]
    public function guardians_endpoint_requires_relation_or_staff(): void
    {
        [$a, , $child] = $this->coupleWithSharedChild();

        Sanctum::actingAs($a);
        $this->getJson("api/children/{$child->id}/guardians")->assertOk()->assertJsonCount(2, 'data');

        Sanctum::actingAs($this->makeParent());
        $this->getJson("api/children/{$child->id}/guardians")->assertForbidden();

        Permission::findOrCreate('edit schickzeiten', 'web');
        $staff = $this->makeParent();
        $staff->givePermissionTo('edit schickzeiten');
        Sanctum::actingAs($staff);
        $this->getJson("api/children/{$child->id}/guardians")->assertOk();
    }

    #[Test]
    public function pflichtstunden_stats_expose_unit(): void
    {
        Permission::findOrCreate('view Pflichtstunden', 'web');
        [$a, $b] = $this->coupleWithSharedChild();
        $a->givePermissionTo('view Pflichtstunden');
        $b->givePermissionTo('view Pflichtstunden');
        $start = now()->subDay()->setTime(9, 0);
        Pflichtstunde::factory()->create(['user_id' => $b->id, 'start' => $start, 'end' => $start->copy()->addHour(), 'approved' => true]);

        Sanctum::actingAs($a);
        $this->getJson('api/pflichtstunden/stats')
            ->assertOk()
            ->assertJsonPath('ranking.total_families', 1)
            ->assertJsonPath('progress.total_minutes_completed', 60)
            ->assertJsonPath('unit.required_minutes', 20 * 60)
            ->assertJsonPath('unit.basis', 'family')
            ->assertJsonCount(2, 'unit.members');

        $this->getJson('api/pflichtstunden')->assertOk()->assertJsonPath('settings.unit_required_minutes', 1200);
    }
}
