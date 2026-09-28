<?php

namespace Tests\Feature\Ucs;

use App\Model\Child;
use App\Model\UcsLinkCandidate;
use App\Services\Ucs\LinkCandidateService;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Feature-Tests für UcsLinkCandidateController + LinkCandidateService (TODO-08)
 *
 * Deckt Gelingenskriterien 2, 4, 5, 6, 7 aus docs/todos/08-initial-linking-workflow.md ab.
 * Kriterium 1 (Detection im Sync) ist bereits in UcsSyncServiceTest abgedeckt.
 * Kriterium 3 (nächster Sync erzeugt Pivots nach Linking) ist implizit durch TODO-04 abgedeckt.
 */
class UcsLinkCandidateTest extends TestCase
{
    // =========================================================================
    // Setup
    // =========================================================================

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'edit settings', 'guard_name' => 'web']);
    }

    /** User mit 'edit settings'. */
    private function adminUser(): \App\Model\User
    {
        $user = \App\Model\User::factory()->create();
        $user->givePermissionTo('edit settings');

        return $user;
    }

    /** Einen offenen Kandidaten anlegen. */
    private function createCandidate(array $overrides = []): UcsLinkCandidate
    {
        $child = Child::factory()->create([
            'first_name'   => 'Max',
            'last_name'    => 'Müller',
            'ucs_username' => null,
            'ucs_source'   => 'local',
        ]);

        return UcsLinkCandidate::create(array_merge([
            'child_id'     => $child->id,
            'ucs_username' => 'max.mueller',
            'ucs_uuid'     => 'uid-abc-123',
            'reason'       => 'name_match',
            'payload'      => [],
            'detected_at'  => now(),
        ], $overrides));
    }

    // =========================================================================
    // Kriterium 2: UI „Verknüpfen" setzt ucs_username
    // =========================================================================

    /**
     * @test
     * Gelingenskriterium 2: POST confirm setzt children.ucs_username und markiert
     * den Kandidaten als bestätigt.
     */
    public function test_confirm_setzt_ucs_username_und_markiert_kandidat(): void
    {
        $this->actingAs($this->adminUser());

        $candidate = $this->createCandidate();
        $childId   = $candidate->child_id;

        $response = $this->post(
            route('settings.ucs.link.confirm', $candidate)
        );

        $response->assertRedirect();
        $response->assertSessionHas('type', 'success');

        // Kind hat jetzt ucs_username gesetzt
        $this->assertDatabaseHas('children', [
            'id'           => $childId,
            'ucs_username' => 'max.mueller',
            'ucs_uuid'     => 'uid-abc-123',
        ]);

        // Kandidat ist als bestätigt markiert
        $candidate->refresh();
        $this->assertNotNull($candidate->confirmed_at);
    }

    /**
     * @test
     * Gelingenskriterium 2b: Nach Confirm ist der Kandidat NICHT mehr in der
     * offenen Vorschlagsliste (open()-Scope gibt ihn nicht mehr zurück).
     */
    public function test_confirm_entfernt_kandidaten_aus_offener_liste(): void
    {
        $this->actingAs($this->adminUser());
        $candidate = $this->createCandidate();

        $this->post(route('settings.ucs.link.confirm', $candidate));

        $this->assertSame(
            0,
            UcsLinkCandidate::open()->where('id', $candidate->id)->count(),
            'Bestätigter Kandidat erscheint nicht mehr in open()-Scope'
        );
    }

    // =========================================================================
    // Kriterium 4: „Verwerfen" entfernt aus UI + verhindert Re-Detect
    // =========================================================================

    /**
     * @test
     * Gelingenskriterium 4a: POST reject setzt payload.status='rejected'.
     */
    public function test_reject_setzt_payload_status_rejected(): void
    {
        $this->actingAs($this->adminUser());
        $candidate = $this->createCandidate();

        $response = $this->post(
            route('settings.ucs.link.reject', $candidate),
            ['note' => 'Falsche Namensgleichheit']
        );

        $response->assertRedirect();
        $response->assertSessionHas('type', 'success');

        $candidate->refresh();
        $this->assertNotNull($candidate->confirmed_at, 'confirmed_at gesetzt');
        $this->assertSame('rejected', $candidate->payload['status'] ?? null);
        $this->assertSame('Falsche Namensgleichheit', $candidate->payload['rejected_note'] ?? null);
    }

    /**
     * @test
     * Gelingenskriterium 4b: Verworfener Kandidat erscheint nicht mehr im open()-Scope.
     */
    public function test_reject_entfernt_kandidaten_aus_offener_liste(): void
    {
        $this->actingAs($this->adminUser());
        $candidate = $this->createCandidate();

        $this->post(route('settings.ucs.link.reject', $candidate));

        $this->assertSame(
            0,
            UcsLinkCandidate::open()->where('id', $candidate->id)->count(),
            'Verworfener Kandidat erscheint nicht mehr in open()-Scope'
        );
    }

    // =========================================================================
    // Kriterium 5: CLI und UI erzeugen identischen DB-Zustand (Service-Test)
    // =========================================================================

    /**
     * @test
     * Gelingenskriterium 5: LinkCandidateService::confirm() erzeugt denselben
     * DB-Zustand wie das CLI-Command ucs:link-child.
     */
    public function test_cli_und_ui_erzeugen_identischen_db_zustand(): void
    {
        $service = app(LinkCandidateService::class);

        $child = Child::factory()->create([
            'first_name'   => 'Anna',
            'last_name'    => 'Schmidt',
            'ucs_username' => null,
            'ucs_source'   => 'local',
        ]);

        $candidate = UcsLinkCandidate::create([
            'child_id'     => $child->id,
            'ucs_username' => 'anna.schmidt',
            'ucs_uuid'     => 'uid-anna-123',
            'reason'       => 'name_match',
            'payload'      => [],
            'detected_at'  => now(),
        ]);

        // Service direkt aufrufen (wie es der Controller UND das CLI tun)
        $updatedChild = $service->confirm($candidate, null);

        // Ergebnis identisch, egal ob CLI oder UI
        $this->assertSame('anna.schmidt', $updatedChild->ucs_username);
        $this->assertSame('uid-anna-123', $updatedChild->ucs_uuid);
        $this->assertSame('local',        $updatedChild->ucs_source,
            'ucs_source bleibt local – manuell gepflegt');

        $candidate->refresh();
        $this->assertNotNull($candidate->confirmed_at);
        $this->assertNull($candidate->confirmed_by, 'CLI: kein User-Kontext');
    }

    // =========================================================================
    // Kriterium 6: Ohne 'edit settings' → 403
    // =========================================================================

    /**
     * @test
     * Gelingenskriterium 6a: User ohne 'edit settings' → confirm → 403.
     */
    public function test_confirm_liefert_403_ohne_berechtigung(): void
    {
        $user      = \App\Model\User::factory()->create();
        $candidate = $this->createCandidate();

        $this->actingAs($user)
             ->post(route('settings.ucs.link.confirm', $candidate))
             ->assertStatus(403);
    }

    /**
     * @test
     * Gelingenskriterium 6b: User ohne 'edit settings' → reject → 403.
     */
    public function test_reject_liefert_403_ohne_berechtigung(): void
    {
        $user      = \App\Model\User::factory()->create();
        $candidate = $this->createCandidate();

        $this->actingAs($user)
             ->post(route('settings.ucs.link.reject', $candidate))
             ->assertStatus(403);
    }

    // =========================================================================
    // Kriterium 7: Doppeltes Confirm → no-op, keine Exception
    // =========================================================================

    /**
     * @test
     * Gelingenskriterium 7: Doppeltes Confirm (Race-Condition) ist no-op.
     * Der Service wirft keine Exception, das Kind bleibt unverändert.
     */
    public function test_doppeltes_confirm_ist_no_op(): void
    {
        $service   = app(LinkCandidateService::class);
        $candidate = $this->createCandidate();

        // Erstes Confirm
        $child1 = $service->confirm($candidate);
        $confirmed1 = $candidate->refresh()->confirmed_at;

        // Zweites Confirm – darf keine Exception werfen
        $child2 = $service->confirm($candidate);

        $this->assertSame($child1->id, $child2->id, 'Gleiches Kind zurückgegeben');
        $this->assertEquals($confirmed1, $candidate->refresh()->confirmed_at,
            'confirmed_at wurde nicht überschrieben');

        // ucs_username darf nur einmal gesetzt sein
        $this->assertSame('max.mueller', $child2->ucs_username);
    }

    /**
     * @test
     * Gelingenskriterium 7 via HTTP: Doppelter POST confirm → kein 500.
     */
    public function test_doppelter_post_confirm_kein_500(): void
    {
        $this->actingAs($this->adminUser());
        $candidate = $this->createCandidate();

        $this->post(route('settings.ucs.link.confirm', $candidate))
             ->assertRedirect();

        // Zweiter Post auf denselben Kandidaten
        $this->post(route('settings.ucs.link.confirm', $candidate))
             ->assertRedirect()
             ->assertSessionHas('type', 'success'); // no-op, kein 500
    }
}

