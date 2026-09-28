<?php

namespace Tests\Unit\Services\Ucs;

use App\Model\Child;
use App\Model\UcsLinkCandidate;
use App\Services\Ucs\LinkCandidateService;
use Tests\TestCase;

/**
 * Unit-Tests für App\Services\Ucs\LinkCandidateService
 *
 * Deckt §10 Tabellen-Einträge ab:
 *  - confirm() setzt ucs_username + confirmed_at
 *  - Idempotenz: zweites confirm() → no-op
 *  - reject() markiert payload.status='rejected'
 */
class LinkCandidateServiceTest extends TestCase
{
    // =========================================================================
    // Helpers
    // =========================================================================

    private function makeCandidate(array $childOverrides = [], array $candidateOverrides = []): UcsLinkCandidate
    {
        $child = Child::factory()->create(array_merge([
            'first_name'   => 'Test',
            'last_name'    => 'Kind',
            'ucs_username' => null,
            'ucs_source'   => 'local',
        ], $childOverrides));

        return UcsLinkCandidate::create(array_merge([
            'child_id'     => $child->id,
            'ucs_username' => 'test.kind',
            'ucs_uuid'     => 'uid-test-0001',
            'reason'       => 'name_match',
            'payload'      => [],
            'detected_at'  => now(),
        ], $candidateOverrides));
    }

    private function service(): LinkCandidateService
    {
        return app(LinkCandidateService::class);
    }

    // =========================================================================
    // confirm() – Kriterium: setzt ucs_username + confirmed_at
    // =========================================================================

    public function test_confirm_setzt_ucs_username_und_confirmed_at(): void
    {
        $candidate = $this->makeCandidate();
        $service   = $this->service();

        $child = $service->confirm($candidate);

        // ucs_username und ucs_uuid wurden aufs Kind geschrieben
        $this->assertSame('test.kind',    $child->ucs_username, 'ucs_username gesetzt');
        $this->assertSame('uid-test-0001', $child->ucs_uuid,    'ucs_uuid gesetzt');

        // confirmed_at des Kandidaten ist gesetzt
        $candidate->refresh();
        $this->assertNotNull($candidate->confirmed_at, 'confirmed_at gesetzt');
    }

    public function test_confirm_setzt_kein_ucs_uuid_wenn_leer(): void
    {
        $candidate = $this->makeCandidate([], ['ucs_uuid' => '']);
        $service   = $this->service();

        $child = $service->confirm($candidate);

        // ucs_username gesetzt, ucs_uuid bleibt leer
        $this->assertSame('test.kind', $child->ucs_username);
        $this->assertEmpty($child->ucs_uuid, 'ucs_uuid bleibt leer wenn Kandidat keinen uuid hat');
    }

    public function test_confirm_behaelt_ucs_source_local(): void
    {
        $candidate = $this->makeCandidate(['ucs_source' => 'local']);
        $service   = $this->service();

        $child = $service->confirm($candidate);

        $this->assertSame('local', $child->ucs_source, 'ucs_source bleibt local nach confirm()');
    }

    // =========================================================================
    // Idempotenz bei zweitem confirm()
    // =========================================================================

    public function test_zweites_confirm_ist_no_op(): void
    {
        $candidate = $this->makeCandidate();
        $service   = $this->service();

        // Erstes Confirm
        $child1     = $service->confirm($candidate);
        $confirmedAt = $candidate->refresh()->confirmed_at;

        // Zweites Confirm auf denselben (bereits bestätigten) Kandidaten
        $child2 = $service->confirm($candidate);

        // Gibt dasselbe Kind zurück
        $this->assertSame($child1->id, $child2->id, 'Dasselbe Kind-Modell zurückgegeben');
        $this->assertSame('test.kind', $child2->ucs_username, 'ucs_username unverändert');

        // confirmed_at wurde NICHT erneut geschrieben
        $this->assertEquals(
            $confirmedAt,
            $candidate->refresh()->confirmed_at,
            'confirmed_at nicht verändert beim zweiten confirm()'
        );
    }

    public function test_zweites_confirm_wirft_keine_exception(): void
    {
        $candidate = $this->makeCandidate();
        $service   = $this->service();

        $service->confirm($candidate);

        // Darf keine Exception werfen
        $this->expectNotToPerformAssertions();
        try {
            $service->confirm($candidate);
        } catch (\Throwable $e) {
            $this->fail('Zweites confirm() warf unexpectedly: '.$e->getMessage());
        }
    }

    // =========================================================================
    // reject() – Kriterium: payload.status='rejected'
    // =========================================================================

    public function test_reject_setzt_payload_status_rejected(): void
    {
        $candidate = $this->makeCandidate();
        $service   = $this->service();

        $service->reject($candidate, null, 'Test-Ablehnung');

        $candidate->refresh();
        $this->assertNotNull($candidate->confirmed_at, 'confirmed_at gesetzt');
        $this->assertSame('rejected', $candidate->payload['status'] ?? null, 'payload.status=rejected');
        $this->assertSame('Test-Ablehnung', $candidate->payload['rejected_note'] ?? null, 'rejected_note gesetzt');
    }

    public function test_reject_ohne_note_setzt_kein_rejected_note(): void
    {
        $candidate = $this->makeCandidate();
        $service   = $this->service();

        $service->reject($candidate, null);

        $candidate->refresh();
        $this->assertSame('rejected', $candidate->payload['status'] ?? null, 'payload.status=rejected');
        $this->assertArrayNotHasKey('rejected_note', $candidate->payload ?? [], 'kein rejected_note wenn leer');
    }

    public function test_reject_auf_bereits_verarbeitetem_kandidaten_ist_no_op(): void
    {
        $candidate = $this->makeCandidate();
        $service   = $this->service();

        // Zuerst bestätigen
        $service->confirm($candidate);
        $candidate->refresh();
        $confirmedAtVorher = $candidate->confirmed_at;

        // Jetzt reject versuchen – muss still bleiben
        $service->reject($candidate, null, 'zu spät');

        $candidate->refresh();
        // confirmed_at bleibt unverändert (kein zweites Überschreiben)
        $this->assertEquals($confirmedAtVorher, $candidate->confirmed_at, 'confirmed_at nicht verändert');
        // payload.status steht NICHT auf rejected (war confirm, nicht reject)
        $this->assertNotSame('rejected', $candidate->payload['status'] ?? '', 'status nicht auf rejected geändert');
    }

    // =========================================================================
    // confirm() – Konflikt: ucs_username bereits bei anderem Kind vergeben
    // =========================================================================

    public function test_confirm_wirft_exception_bei_username_konflikt(): void
    {
        // Anderes Kind hat bereits denselben ucs_username
        Child::factory()->create([
            'ucs_username' => 'test.kind',
            'ucs_source'   => 'kelvin',
        ]);

        $candidate = $this->makeCandidate();
        $service   = $this->service();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches("/test\.kind/");

        $service->confirm($candidate);
    }
}

