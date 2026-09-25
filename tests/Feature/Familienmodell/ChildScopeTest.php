<?php

namespace Tests\Feature\Familienmodell;

use App\Enums\GuardianRelation;
use App\Mail\SchickzeitenReminder;
use App\Model\ChildNotice;
use App\Model\Group;
use App\Model\Krankmeldungen;
use App\Model\Schickzeiten;
use App\Services\Family\FamilyResolver;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsFamilies;
use Tests\TestCase;

/**
 * FAM-04: Kindbezogene Daten im kind-zentrierten Modus – Zugriff nur über
 * eigene Beziehung mit passendem Recht (Patchwork, eingeschränkte Rechte).
 */
class ChildScopeTest extends TestCase
{
    use BuildsFamilies;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->useResolver(FamilyResolver::MODE_CHILD_CENTRIC);
    }

    #[Test]
    public function ex_partner_does_not_see_sick_report_of_other_child(): void
    {
        $p = $this->patchwork();
        $active = ['start' => today(), 'ende' => today()->addDay()];
        $reportX = Krankmeldungen::factory()->create(['users_id' => $p['a']->id, 'child_id' => $p['x']->id] + $active);
        $reportY = Krankmeldungen::factory()->create(['users_id' => $p['a']->id, 'child_id' => $p['y']->id] + $active);

        $this->actingAs($p['b'])->get('krankmeldung')
            ->assertOk()
            ->assertViewHas('krankmeldungen', function ($paginator) use ($reportX, $reportY) {
                $ids = collect($paginator->items())->pluck('id');

                return $ids->contains($reportX->id) && ! $ids->contains($reportY->id);
            });

        Sanctum::actingAs($p['b']);
        $this->getJson('api/parent/krankmeldungen')
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('data.0.child_id', $p['x']->id);
    }

    #[Test]
    public function freetext_report_is_visible_to_family_but_not_to_ex_partner(): void
    {
        $p = $this->patchwork();
        $free = Krankmeldungen::factory()->create(['users_id' => $p['a']->id, 'child_id' => null]);

        $this->assertTrue(Krankmeldungen::visibleTo($p['c'])->whereKey($free->id)->exists());
        $this->assertFalse(Krankmeldungen::visibleTo($p['b'])->whereKey($free->id)->exists());
    }

    #[Test]
    public function grandparent_without_manage_right_cannot_report_sick_or_edit_schickzeiten(): void
    {
        [, , $child] = $this->coupleWithSharedChild();
        $grandma = $this->makeParent();
        $this->linkGuardian($child, $grandma, GuardianRelation::Grandparent);

        $this->actingAs($grandma)->post('krankmeldung', [
            'child_id' => $child->id,
            'start' => now()->format('Y-m-d'),
            'ende' => now()->addDay()->format('Y-m-d'),
            'kommentar' => 'Fieber',
        ])->assertSessionHas('type', 'danger');

        $this->actingAs($grandma)->post("schickzeiten/{$child->id}/1", ['type' => 'genau', 'time' => '14:00'])
            ->assertSessionHas('type', 'warning');

        $this->assertSame(0, Krankmeldungen::count());
        $this->assertSame(0, Schickzeiten::count());
    }

    #[Test]
    public function partner_with_manage_right_can_report_sick(): void
    {
        $p = $this->patchwork();

        $this->actingAs($p['c'])->post('krankmeldung', [
            'child_id' => $p['y']->id,
            'start' => now()->format('Y-m-d'),
            'ende' => now()->addDay()->format('Y-m-d'),
            'kommentar' => 'Fieber',
        ])->assertSessionHas('type', 'success');

        $this->assertDatabaseHas('krankmeldungen', ['child_id' => $p['y']->id, 'users_id' => $p['c']->id]);
    }

    #[Test]
    public function family_member_without_own_link_has_no_child_access(): void
    {
        [$a, $b, $child] = $this->coupleWithChildOfA();
        $this->familyOf($a, $b);

        $this->assertFalse($b->children()->contains($child));

        Sanctum::actingAs($b);
        $this->getJson('api/parent/children')->assertOk()->assertJsonPath('count', 0);
    }

    #[Test]
    public function api_child_notice_requires_manage_right(): void
    {
        $group = Group::factory()->create();
        $class = Group::factory()->create();
        $this->configureCare([$group->id], [$class->id]);
        [, , $child] = $this->coupleWithSharedChild(['group_id' => $group->id, 'class_id' => $class->id]);
        $grandma = $this->makeParent();
        $this->linkGuardian($child, $grandma, GuardianRelation::Grandparent);

        Sanctum::actingAs($grandma);
        $this->postJson('api/parent/child-notices', [
            'child_id' => $child->id,
            'date' => today()->toDateString(),
            'notice' => 'Ich hole ab',
        ])->assertForbidden();

        $this->assertSame(0, ChildNotice::count());
    }

    #[Test]
    public function schickzeiten_reminder_goes_to_all_guardians_with_manage_right(): void
    {
        $group = Group::factory()->create();
        $class = Group::factory()->create();
        $this->configureCare([$group->id], [$class->id]);
        [$a, $b, $child] = $this->coupleWithSharedChild(['group_id' => $group->id, 'class_id' => $class->id]);
        $grandma = $this->makeParent();
        $this->linkGuardian($child, $grandma, GuardianRelation::Grandparent);
        Schickzeiten::create(['child_id' => $child->id, 'users_id' => $a->id, 'weekday' => 1, 'type' => 'genau', 'time' => '14:00']);

        app(\App\Http\Controllers\SchickzeitenController::class)->sendReminder();

        Mail::assertQueued(SchickzeitenReminder::class, 2);
        Mail::assertQueued(SchickzeitenReminder::class, fn ($mail) => $mail->hasTo($b->email));
        Mail::assertNotQueued(SchickzeitenReminder::class, fn ($mail) => $mail->hasTo($grandma->email));
    }
}
