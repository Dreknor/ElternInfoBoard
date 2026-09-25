<?php

namespace Tests\Feature\Familienmodell;

use App\Enums\GuardianRelation;
use App\Model\ChildGuardian;
use App\Model\GuardianLinkReport;
use App\Services\Family\FamilyResolver;
use App\Services\Family\Sorg2MigrationService;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsFamilies;
use Tests\TestCase;

/**
 * FAM-11: Eltern-Ansicht „Familie & Kinder“, Meldung falscher Verbindungen
 * und Datenschutz-Auskunft mit Herkunft der Beziehungen (E3/E6).
 */
class ParentViewAndPrivacyTest extends TestCase
{
    use BuildsFamilies;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useResolver(FamilyResolver::MODE_CHILD_CENTRIC);
    }

    #[Test]
    public function settings_show_family_children_rights_and_migration_hint(): void
    {
        [$a, $b, $child] = $this->coupleWithChildOfA();
        app(Sorg2MigrationService::class)->run();

        $this->actingAs($b->fresh())->get('einstellungen')
            ->assertOk()
            ->assertSee('Ihre Familie')
            ->assertSee($a->name)
            ->assertSee($child->first_name)
            ->assertSee('Sorgeberechtigte/r')
            ->assertSee('aus der früheren Kontoverknüpfung übernommen')
            ->assertSee('Verbindung ist falsch');
    }

    #[Test]
    public function parent_can_report_wrong_link_but_not_for_foreign_child(): void
    {
        [$a, , $child] = $this->coupleWithSharedChild();
        $foreign = $this->childFor([$this->makeParent()]);

        $this->actingAs($a)->post(route('einstellungen.guardian.report', $child))->assertSessionHas('type', 'success');
        $this->actingAs($a)->post(route('einstellungen.guardian.report', $child)); // doppelt → eine Meldung
        $this->actingAs($a)->post(route('einstellungen.guardian.report', $foreign))->assertSessionHas('type', 'danger');

        $this->assertSame(1, GuardianLinkReport::count());
        $this->assertSame($a->id, GuardianLinkReport::first()->reported_by);
        // Eltern ändern die Beziehung nicht selbst (E6)
        $this->assertTrue($child->parents()->where('users.id', $a->id)->exists());
    }

    #[Test]
    public function krankmeldung_form_only_offers_children_with_manage_right(): void
    {
        [$a, , $child] = $this->coupleWithSharedChild();
        $grandchild = $this->childFor([]);
        $this->linkGuardian($grandchild, $a, GuardianRelation::Grandparent);

        $this->actingAs($a)->get('krankmeldung')
            ->assertOk()
            ->assertSee('value="'.$child->id.'"', false)
            ->assertDontSee('value="'.$grandchild->id.'"', false);
    }

    #[Test]
    public function privacy_export_contains_family_and_relations_with_source(): void
    {
        [$a, $b, $child] = $this->coupleWithChildOfA();
        app(Sorg2MigrationService::class)->run();

        $json = $this->actingAs($b->fresh())->get(route('datenschutz.export.json'))->assertOk()->json();

        $this->assertSame([$a->name], $json['familie']['mitglieder']);
        $this->assertArrayNotHasKey('sorgeberechtigter_2', $json['benutzerkonto']);
        $this->assertSame($child->first_name, $json['kinder'][0]['vorname']);
        $this->assertSame('Sorgeberechtigte/r', $json['kinder'][0]['beziehung']);
        $this->assertSame('aus früherer Kontoverknüpfung übernommen', $json['kinder'][0]['herkunft']);
        $this->assertTrue($json['kinder'][0]['ungeprueft']);

        $this->actingAs($b->fresh())->get('datenschutz')
            ->assertOk()
            ->assertSee('Familie')
            ->assertSee('ungeprüft');

        $this->actingAs($b->fresh())->get(route('datenschutz.export.pdf'))->assertOk();
    }

    #[Test]
    public function reviewed_link_is_no_longer_marked_pending(): void
    {
        [$a, , $child] = $this->coupleWithSharedChild();
        $child->parents()->updateExistingPivot($a->id, ['source' => ChildGuardian::SOURCE_MIGRATION, 'reviewed_at' => now()]);

        $json = $this->actingAs($a)->get(route('datenschutz.export.json'))->json();

        $this->assertFalse($json['kinder'][0]['ungeprueft']);
    }
}
