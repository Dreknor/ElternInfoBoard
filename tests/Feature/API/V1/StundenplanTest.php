<?php

namespace Tests\Feature\API\V1;

use App\Model\Child;
use App\Services\StundenplanDataProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;

/**
 * Stundenplan je Kind (B-43): Zuordnung Kind → Stundenplan-Klasse.
 */
class StundenplanTest extends AppApiTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->partialMock(StundenplanDataProvider::class, function ($mock) {
            $mock->shouldReceive('getStundenplanData')->andReturn([
                'Basisdaten' => [],
                'Zeitslots' => [],
                'Klassen' => [
                    ['Kurzform' => '1Frue', 'Name' => '1Frue', 'Plan' => [
                        ['PlTg' => '1', 'PlSt' => '1', 'PlFa' => 'SU', 'PlLe' => ['Ab'], 'PlRa' => ['R1']],
                    ]],
                    ['Kurzform' => '3a', 'Name' => '3a', 'Plan' => [
                        ['PlTg' => '1', 'PlSt' => '2', 'PlFa' => 'MA', 'PlLe' => ['Mü'], 'PlRa' => ['R3']],
                        ['PlTg' => '1', 'PlSt' => '1', 'PlFa' => 'DE', 'PlLe' => ['Mü'], 'PlRa' => ['R3']],
                    ]],
                ],
            ]);
        });
    }

    private function lessonsMonday(Child $child): array
    {
        $user = $child->parents()->first();
        Sanctum::actingAs($user);

        return $this->getJson("/api/v1/parent/children/{$child->id}/stundenplan")
            ->assertOk()
            ->json('data.days.0.lessons');
    }

    #[Test]
    public function klasse_mit_gleichem_namen_wie_kurzform(): void
    {
        $child = $this->childOf($this->parentIn($this->group('3a')), $this->group('3a'));

        $lessons = $this->lessonsMonday($child);

        $this->assertSame(['DE', 'MA'], array_column($lessons, 'fach'));
    }

    #[Test]
    public function gruppenname_mit_praefix_klasse(): void
    {
        $group = $this->group('Klasse 3a');
        $child = $this->childOf($this->parentIn($group), $group);

        $this->assertSame(['DE', 'MA'], array_column($this->lessonsMonday($child), 'fach'));
    }

    #[Test]
    public function klasse_nur_als_weitere_gruppe_hortgruppe_als_gruppe(): void
    {
        $hort = $this->group('Hort Bären');
        $user = $this->parentIn($hort);
        $child = Child::create(['first_name' => 'Mia', 'last_name' => 'Muster', 'group_id' => $hort->id]);
        $user->children_rel()->attach($child->id);
        $child->additionalGroups()->attach($this->group('1Frue')->id, ['source' => 'manual']);

        $response = $this->lessonsMonday($child);

        $this->assertSame(['SU'], array_column($response, 'fach'));
    }

    #[Test]
    public function ohne_passende_klasse_bleibt_der_plan_leer(): void
    {
        $group = $this->group('Hort Bären');
        $child = $this->childOf($this->parentIn($group), $group);

        $this->assertSame([], $this->lessonsMonday($child));
    }

    #[Test]
    public function klassenliste_fuer_die_auswahl(): void
    {
        Sanctum::actingAs($this->parentIn($this->group('Hort')));

        $this->getJson('/api/v1/parent/stundenplan/klassen')
            ->assertOk()
            ->assertJsonPath('data.*.kurzform', ['1Frue', '3a']);
    }

    #[Test]
    public function ohne_treffer_meldet_die_api_klasse_nicht_gefunden(): void
    {
        $group = $this->group('Klassenstufe 1');
        $child = $this->childOf($user = $this->parentIn($group), $group);
        Sanctum::actingAs($user);

        $this->getJson("/api/v1/parent/children/{$child->id}/stundenplan")
            ->assertOk()
            ->assertJsonPath('data.klasse_gefunden', false)
            ->assertJsonPath('data.klasse_auswahl', null);
    }

    #[Test]
    public function gewaehlte_klasse_wird_gespeichert_und_genutzt(): void
    {
        $group = $this->group('Klassenstufe 1');
        $child = $this->childOf($user = $this->parentIn($group), $group);
        Sanctum::actingAs($user);
        // Wochendaten zuerst ohne Auswahl laden (Cache darf die Auswahl nicht verdecken)
        $this->getJson("/api/v1/parent/children/{$child->id}/stundenplan")->assertJsonPath('data.days.0.lessons', []);

        $this->putJson("/api/v1/parent/children/{$child->id}/stundenplan-klasse", ['klasse' => '1Frue'])
            ->assertOk()
            ->assertJsonPath('data.klasse_auswahl', '1Frue');

        $response = $this->getJson("/api/v1/parent/children/{$child->id}/stundenplan")
            ->assertOk()
            ->assertJsonPath('data.klasse', '1Frue')
            ->assertJsonPath('data.klasse_gefunden', true)
            ->assertJsonPath('data.klasse_auswahl', '1Frue');
        $this->assertSame(['SU'], array_column($response->json('data.days.0.lessons'), 'fach'));

        $this->putJson("/api/v1/parent/children/{$child->id}/stundenplan-klasse", ['klasse' => null])->assertOk();
        $this->assertNull($child->fresh()->stundenplan_klasse);
    }

    #[Test]
    public function unbekannte_klasse_wird_abgelehnt(): void
    {
        $group = $this->group('Klassenstufe 1');
        $child = $this->childOf($user = $this->parentIn($group), $group);
        Sanctum::actingAs($user);

        $this->putJson("/api/v1/parent/children/{$child->id}/stundenplan-klasse", ['klasse' => '9z'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('klasse');
    }

    #[Test]
    public function fremdes_kind_darf_nicht_geaendert_werden(): void
    {
        $group = $this->group('Klassenstufe 1');
        $child = $this->childOf($this->parentIn($group), $group);
        Sanctum::actingAs($this->parentIn($group));

        $this->putJson("/api/v1/parent/children/{$child->id}/stundenplan-klasse", ['klasse' => '1Frue'])
            ->assertForbidden();
        $this->assertNull($child->fresh()->stundenplan_klasse);
    }
}
