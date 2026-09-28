<?php

namespace Tests\Feature\Familienmodell\Characterization;

use App\Model\ChildNotice;
use App\Model\Group;
use App\Model\Krankmeldungen;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsFamilies;
use Tests\TestCase;

/**
 * Charakterisierung (FAM-01): Zugriff auf Kinder und kindbezogene Daten
 * innerhalb einer gekoppelten Familie. Diese Tests beschreiben das Verhalten,
 * das beim Umbau auf das kind-zentrierte Modell erhalten bleiben muss
 * (Partner verliert keinen Zugriff, Fremde erhalten keinen).
 */
class ChildAccessCharacterizationTest extends TestCase
{
    use BuildsFamilies;

    /**
     * Hook zwischen Datenaufbau und Prüfung. Die Variante „nach Migration“
     * überführt hier die Altdaten und schaltet auf das kind-zentrierte Modell.
     */
    protected function prepareFamilies(): void {}

    #[Test]
    public function partner_sees_children_of_linked_parent(): void
    {
        [$a, $b, $child] = $this->coupleWithChildOfA();
        $stranger = $this->makeParent();
        $this->prepareFamilies();

        $this->assertTrue($a->children()->contains($child));
        $this->assertTrue($b->children()->contains($child));
        $this->assertFalse((bool) $stranger->children()?->contains($child));
    }

    #[Test]
    public function children_of_both_partners_are_merged_without_duplicates(): void
    {
        [$a, $b, $childOfA] = $this->coupleWithChildOfA();
        $childOfB = $this->childFor([$b]);
        $shared = $this->childFor([$a, $b]);
        $this->prepareFamilies();

        $ids = $a->children()->pluck('id')->sort()->values()->all();

        $this->assertSame(collect([$childOfA->id, $childOfB->id, $shared->id])->sort()->values()->all(), $ids);
        $this->assertSame($ids, $b->children()->pluck('id')->sort()->values()->all());
    }

    #[Test]
    public function schickzeiten_index_lists_partner_children(): void
    {
        [, $b, $child] = $this->coupleWithChildOfA();
        $this->prepareFamilies();

        $this->actingAs($b)->get('schickzeiten')
            ->assertOk()
            ->assertViewHas('children', fn ($children) => $children->contains('id', $child->id));
    }

    #[Test]
    public function krankmeldungen_index_lists_reports_of_partner(): void
    {
        [$a, $b, $child] = $this->coupleWithChildOfA();
        $report = Krankmeldungen::factory()->create(['users_id' => $a->id, 'child_id' => $child->id]);
        $foreign = Krankmeldungen::factory()->create(['users_id' => $this->makeParent()->id]);
        $this->prepareFamilies();

        $this->actingAs($b)->get('krankmeldung')
            ->assertOk()
            ->assertViewHas('krankmeldungen', function ($paginator) use ($report, $foreign) {
                $ids = collect($paginator->items())->pluck('id');

                return $ids->contains($report->id) && ! $ids->contains($foreign->id);
            });
    }

    #[Test]
    public function api_parent_children_contains_partner_children_with_stable_structure(): void
    {
        [, $b, $child] = $this->coupleWithChildOfA();
        $this->prepareFamilies();
        Sanctum::actingAs($b);

        $this->getJson('api/parent/children')
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('data.0.id', $child->id)
            ->assertJsonStructure([
                'success',
                'count',
                'data' => [[
                    'id', 'first_name', 'last_name', 'full_name', 'group_id', 'group',
                    'class_id', 'class', 'notification', 'auto_checkIn', 'is_in_care_module',
                ]],
            ]);
    }

    #[Test]
    public function api_parent_children_is_empty_for_stranger(): void
    {
        $this->coupleWithChildOfA();
        $this->prepareFamilies();
        Sanctum::actingAs($this->makeParent());

        $this->getJson('api/parent/children')
            ->assertOk()
            ->assertJsonPath('count', 0);
    }

    #[Test]
    public function partner_may_store_child_notice_via_api_but_stranger_may_not(): void
    {
        $group = Group::factory()->create();
        $class = Group::factory()->create();
        $this->configureCare([$group->id], [$class->id]);
        [, $b, $child] = $this->coupleWithChildOfA(['group_id' => $group->id, 'class_id' => $class->id]);
        $this->prepareFamilies();

        Sanctum::actingAs($b);
        $this->postJson('api/parent/child-notices', [
            'child_id' => $child->id,
            'date' => today()->toDateString(),
            'notice' => 'Wird von Oma abgeholt',
        ])->assertSuccessful();

        Sanctum::actingAs($this->makeParent());
        $this->postJson('api/parent/child-notices', [
            'child_id' => $child->id,
            'date' => today()->toDateString(),
            'notice' => 'Fremd',
        ])->assertForbidden();

        $this->assertSame(1, ChildNotice::where('child_id', $child->id)->count());
    }
}
