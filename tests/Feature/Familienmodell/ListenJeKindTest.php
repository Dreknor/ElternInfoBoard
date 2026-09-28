<?php

namespace Tests\Feature\Familienmodell;

use App\Model\Child;
use App\Model\Group;
use App\Model\Liste;
use App\Model\Listen_Eintragungen;
use App\Model\listen_termine;
use App\Model\User;
use App\Services\Family\FamilyResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsFamilies;
use Tests\TestCase;

/**
 * Listen „je Kind“ (Spezifikation 2.2: „1 Helfer je Kind“): die Begrenzung gilt je Kind
 * über alle Bezugspersonen, auch über Familiengrenzen (getrennt lebende Eltern).
 */
class ListenJeKindTest extends TestCase
{
    use BuildsFamilies;

    private User $mother;

    private User $father;

    private Child $mia;

    private Child $ben;

    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->useResolver(FamilyResolver::MODE_CHILD_CENTRIC);

        $this->group = Group::withoutGlobalScopes()->create(['name' => 'Klasse 2b', 'protected' => false]);
        $this->mother = $this->makeParent(['changePassword' => false]);
        $this->father = $this->makeParent(['changePassword' => false]);
        $this->familyOf($this->mother);
        $this->familyOf($this->father);
        foreach ([$this->mother, $this->father] as $user) {
            DB::table('group_user')->insert(['group_id' => $this->group->id, 'user_id' => $user->id]);
        }

        // Mia: getrennt lebende Eltern; Ben: nur Mutter
        $this->mia = Child::create(['first_name' => 'Mia', 'last_name' => 'M', 'class_id' => $this->group->id, 'group_id' => $this->group->id]);
        $this->ben = Child::create(['first_name' => 'Ben', 'last_name' => 'M', 'class_id' => $this->group->id, 'group_id' => $this->group->id]);
        $this->mia->parents()->attach([$this->mother->id, $this->father->id]);
        $this->ben->parents()->attach($this->mother->id);
    }

    private function liste(string $type): Liste
    {
        $liste = Liste::create([
            'listenname' => 'Kuchen',
            'type' => $type,
            'besitzer' => $this->makeParent()->id,
            'visible_for_all' => false,
            'active' => true,
            'ende' => now()->addWeek(),
            'multiple' => false,
            'booking_scope' => Liste::BOOKING_CHILD,
        ]);
        DB::table('group_listen')->insert(['group_id' => $this->group->id, 'liste_id' => $liste->id]);

        return $liste;
    }

    #[Test]
    public function eintrag_per_child_is_limited_across_separated_parents(): void
    {
        $liste = $this->liste('eintrag');
        $e1 = Listen_Eintragungen::create(['listen_id' => $liste->id, 'eintragung' => 'Kuchen', 'created_by' => $liste->besitzer]);
        $e2 = Listen_Eintragungen::create(['listen_id' => $liste->id, 'eintragung' => 'Saft', 'created_by' => $liste->besitzer]);
        $e3 = Listen_Eintragungen::create(['listen_id' => $liste->id, 'eintragung' => 'Obst', 'created_by' => $liste->besitzer]);

        // Mutter hat zwei Kinder: ohne Auswahl keine Buchung
        $this->actingAs($this->mother)->put("listen/eintragungen/{$e1->id}");
        $this->assertNull($e1->fresh()->user_id);

        $this->actingAs($this->mother)->put("listen/eintragungen/{$e1->id}", ['child_id' => $this->mia->id]);
        $this->assertSame($this->mia->id, $e1->fresh()->child_id);

        // Vater (andere Familie) kann für Mia nicht erneut buchen …
        $this->actingAs($this->father)->put("listen/eintragungen/{$e2->id}");
        $this->assertNull($e2->fresh()->user_id);

        // … die Mutter aber für Ben
        $this->actingAs($this->mother)->put("listen/eintragungen/{$e3->id}", ['child_id' => $this->ben->id]);
        $this->assertSame($this->ben->id, $e3->fresh()->child_id);

        // Der Vater darf die Buchung für das gemeinsame Kind wieder austragen
        $this->actingAs($this->father)->delete("listen/eintragungen/{$e1->id}");
        $this->assertNull($e1->fresh()->user_id);
        $this->assertNull($e1->fresh()->child_id);
    }

    #[Test]
    public function termin_per_child_via_app_api(): void
    {
        $liste = $this->liste('termin');
        $t1 = listen_termine::create(['listen_id' => $liste->id, 'termin' => now()->addDays(2)]);
        $t2 = listen_termine::create(['listen_id' => $liste->id, 'termin' => now()->addDays(3)]);

        Sanctum::actingAs($this->mother);
        $this->getJson("/api/v1/listen/{$liste->id}")
            ->assertOk()
            ->assertJsonPath('data.booking_scope', 'child')
            ->assertJsonCount(2, 'data.bookable_children');
        $this->postJson("/api/v1/listen/termine/{$t1->id}/reservation")->assertStatus(422);
        $this->postJson("/api/v1/listen/termine/{$t1->id}/reservation", ['child_id' => $this->mia->id])->assertCreated();

        // Vater: nur ein Kind → automatisch Mia, die schon gebucht ist
        Sanctum::actingAs($this->father);
        $this->postJson("/api/v1/listen/termine/{$t2->id}/reservation")->assertStatus(409);
        $this->getJson("/api/v1/listen/{$liste->id}")
            ->assertJsonPath('data.termine.0.status', 'mine')
            ->assertJsonPath('data.termine.0.child.name', 'Mia M');
    }

    #[Test]
    public function family_scope_keeps_previous_behaviour(): void
    {
        $liste = $this->liste('eintrag');
        $liste->update(['booking_scope' => Liste::BOOKING_FAMILY]);
        $e1 = Listen_Eintragungen::create(['listen_id' => $liste->id, 'eintragung' => 'Kuchen', 'created_by' => $liste->besitzer]);

        $this->actingAs($this->mother)->put("listen/eintragungen/{$e1->id}");
        $this->assertSame($this->mother->id, $e1->fresh()->user_id);
        $this->assertNull($e1->fresh()->child_id);
    }
}
