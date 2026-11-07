<?php

namespace Tests\Feature;

use App\Model\Group;
use App\Model\Liste;
use App\Model\Listen_Eintragungen;
use App\Model\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsFamilies;
use Tests\TestCase;

/**
 * Eintragungslisten: Eltern können beim Übernehmen eines vorgegebenen Eintrags
 * (z. B. „Kuchen“) einen Freitext angeben (z. B. „Apfelkuchen“).
 */
class ListenEintragBemerkungTest extends TestCase
{
    use BuildsFamilies;

    private User $parent;

    private Liste $liste;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        $group = Group::withoutGlobalScopes()->create(['name' => 'Klasse 3a', 'protected' => false]);
        $this->parent = $this->makeParent(['changePassword' => false]);
        DB::table('group_user')->insert(['group_id' => $group->id, 'user_id' => $this->parent->id]);

        $this->liste = Liste::create([
            'listenname' => 'Sommerfest',
            'type' => 'eintrag',
            'besitzer' => $this->makeParent()->id,
            'visible_for_all' => true,
            'active' => true,
            'ende' => now()->addWeek(),
            'multiple' => true,
            'make_new_entry' => false,
        ]);
        DB::table('group_listen')->insert(['group_id' => $group->id, 'liste_id' => $this->liste->id]);
    }

    private function eintrag(string $text = 'Kuchen'): Listen_Eintragungen
    {
        return Listen_Eintragungen::create(['listen_id' => $this->liste->id, 'eintragung' => $text, 'created_by' => $this->liste->besitzer]);
    }

    #[Test]
    public function web_reservation_stores_and_release_clears_bemerkung(): void
    {
        $eintrag = $this->eintrag();

        $this->actingAs($this->parent)->put("listen/eintragungen/{$eintrag->id}", ['bemerkung' => '  Apfelkuchen  ']);
        $this->assertSame($this->parent->id, $eintrag->fresh()->user_id);
        $this->assertSame('Apfelkuchen', $eintrag->fresh()->bemerkung);
        $this->assertSame('Kuchen', $eintrag->fresh()->eintragung);

        $this->actingAs($this->parent)->get("listen/{$this->liste->id}")->assertSee('Apfelkuchen');

        $this->actingAs($this->parent)->delete("listen/eintragungen/{$eintrag->id}");
        $this->assertNull($eintrag->fresh()->user_id);
        $this->assertNull($eintrag->fresh()->bemerkung);
    }

    #[Test]
    public function web_reservation_without_bemerkung_still_works(): void
    {
        $eintrag = $this->eintrag('Aufbau');

        $this->actingAs($this->parent)->put("listen/eintragungen/{$eintrag->id}", ['bemerkung' => '']);
        $this->assertSame($this->parent->id, $eintrag->fresh()->user_id);
        $this->assertNull($eintrag->fresh()->bemerkung);
    }

    #[Test]
    public function api_v1_accepts_and_returns_note(): void
    {
        $eintrag = $this->eintrag();
        Sanctum::actingAs($this->parent);

        $this->postJson("/api/v1/listen/eintraege/{$eintrag->id}/reservation", ['note' => str_repeat('x', 256)])
            ->assertStatus(422);
        $this->postJson("/api/v1/listen/eintraege/{$eintrag->id}/reservation", ['note' => 'Käsekuchen'])
            ->assertCreated();

        $this->getJson("/api/v1/listen/{$this->liste->id}")
            ->assertOk()
            ->assertJsonPath('data.eintraege.0.text', 'Kuchen')
            ->assertJsonPath('data.eintraege.0.note', 'Käsekuchen')
            ->assertJsonPath('data.eintraege.0.status', 'mine');

        $this->deleteJson("/api/v1/listen/eintraege/{$eintrag->id}/reservation")->assertOk();
        $this->assertNull($eintrag->fresh()->bemerkung);
    }

    #[Test]
    public function basis_api_accepts_bemerkung(): void
    {
        $eintrag = $this->eintrag();
        Sanctum::actingAs($this->parent);

        $this->putJson("/api/listen/eintrag/{$eintrag->id}/reservieren", ['bemerkung' => 'Zupfkuchen'])->assertOk();
        $this->assertSame('Zupfkuchen', $eintrag->fresh()->bemerkung);
    }
}
