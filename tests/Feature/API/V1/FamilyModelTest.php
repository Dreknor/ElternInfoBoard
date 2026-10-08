<?php

namespace Tests\Feature\API\V1;

use App\Model\Child;
use App\Model\Rueckmeldungen;
use App\Model\UserRueckmeldungen;
use App\Services\Family\FamilyResolver;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsFamilies;

/**
 * B-80: App-API v1 mit dem kind-zentrierten Familienmodell
 * (Rechte je Kind, Rückmeldungen pro Kind, Familie statt sorg2).
 */
class FamilyModelTest extends AppApiTestCase
{
    use BuildsFamilies;

    private function childIn($group, string $firstName): Child
    {
        return Child::create(['first_name' => $firstName, 'last_name' => 'Muster', 'group_id' => $group->id, 'class_id' => $group->id]);
    }

    private function feedbackPost($group, string $scope = 'child')
    {
        $post = $this->postIn($group);
        Rueckmeldungen::withoutEvents(fn () => Rueckmeldungen::create([
            'post_id' => $post->id,
            'type' => 'email',
            'empfaenger' => 'schule@example.org',
            'ende' => now()->addDays(3),
            'text' => 'Bitte antworten',
            'pflicht' => true,
            'commentable' => false,
            'multiple' => false,
            'scope' => $scope,
        ]));

        return $post;
    }

    /** @test */
    public function my_rights_reflect_guardian_rights_in_child_centric_mode(): void
    {
        $this->useResolver(FamilyResolver::MODE_CHILD_CENTRIC);
        $group = $this->group();
        $parent = $this->parentIn($group);
        $grandma = $this->parentIn($group);
        $child = $this->childIn($group, 'Mia');
        $child->parents()->attach($parent->id);
        $child->parents()->attach($grandma->id, ['relation' => 'grandparent', 'has_custody' => false, 'can_manage' => false]);

        Sanctum::actingAs($grandma);
        $this->postJson('/api/v1/parent/krankmeldungen', [
            'child_id' => $child->id,
            'start' => now()->toDateString(),
            'ende' => now()->toDateString(),
            'kommentar' => 'Fieber',
        ])->assertForbidden();

        Sanctum::actingAs($parent);
        $this->postJson('/api/v1/parent/krankmeldungen', [
            'child_id' => $child->id,
            'start' => now()->toDateString(),
            'ende' => now()->toDateString(),
            'kommentar' => 'Fieber',
        ])->assertSuccessful();
    }

    /** @test */
    public function feedback_per_child_exposes_targets_and_requires_child_id_for_several_children(): void
    {
        $this->useResolver(FamilyResolver::MODE_CHILD_CENTRIC);
        $group = $this->group();
        $parent = $this->parentIn($group);
        $other = $this->parentIn($group);
        $this->familyOf($parent);
        $this->familyOf($other);
        $mia = $this->childIn($group, 'Mia');
        $ben = $this->childIn($group, 'Ben');
        $mia->parents()->attach([$parent->id, $other->id]);
        $ben->parents()->attach($parent->id);
        $post = $this->feedbackPost($group);

        Sanctum::actingAs($parent);
        $this->getJson("/api/v1/posts/{$post->id}")
            ->assertOk()
            ->assertJsonPath('data.feedback.scope', 'child')
            ->assertJsonCount(2, 'data.feedback.targets')
            ->assertJsonPath('data.feedback.responded', false)
            ->assertJsonPath('data.todo', 'feedback');

        // Ältere App ohne child_id: bei zwei Kindern nicht eindeutig
        $this->postJson("/api/v1/posts/{$post->id}/feedback", ['text' => 'Ja'])->assertStatus(422);

        $this->postJson("/api/v1/posts/{$post->id}/feedback", ['text' => 'Ja', 'child_id' => $mia->id])->assertCreated();
        $this->assertSame($mia->id, UserRueckmeldungen::first()->child_id);

        // Getrennt lebender Elternteil (andere Familie) sieht Mia als beantwortet
        Sanctum::actingAs($other);
        $this->getJson("/api/v1/posts/{$post->id}")
            ->assertJsonPath('data.feedback.targets.0.child_id', $mia->id)
            ->assertJsonPath('data.feedback.targets.0.responded', true)
            ->assertJsonPath('data.feedback.responded', true);

        // Nochmals für Mia antworten ist nicht möglich (nicht mehrfach)
        $this->postJson("/api/v1/posts/{$post->id}/feedback", ['text' => 'Doch', 'child_id' => $mia->id])->assertStatus(409);
    }

    /** @test */
    public function legacy_mode_keeps_one_answer_per_family(): void
    {
        $this->useResolver(FamilyResolver::MODE_LEGACY);
        $group = $this->group();
        $a = $this->parentIn($group);
        $b = $this->parentIn($group);
        $this->linkPartners($a, $b);
        $child = $this->childIn($group, 'Mia');
        $child->parents()->attach($a->id);
        $post = $this->feedbackPost($group);

        Sanctum::actingAs($b);
        $this->getJson("/api/v1/posts/{$post->id}")
            ->assertJsonPath('data.feedback.scope', 'family')
            ->assertJsonPath('data.feedback.targets', []);
        $this->postJson("/api/v1/posts/{$post->id}/feedback", ['text' => 'Ja'])->assertCreated();

        Sanctum::actingAs($a);
        $this->getJson("/api/v1/posts/{$post->id}")->assertJsonPath('data.feedback.responded', true);
    }
}
