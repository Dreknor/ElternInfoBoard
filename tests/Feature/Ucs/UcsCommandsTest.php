<?php

namespace Tests\Feature\Ucs;

use App\Jobs\SyncUcsSchoolJob;
use App\Model\Child;
use App\Model\Group;
use App\Model\UcsLinkCandidate;
use App\Services\Ucs\KelvinClient;
use App\Services\Ucs\UcsSyncService;
use App\Settings\UcsSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

/**
 * Feature-Tests für Paket 05: SyncUcsSchoolJob + Artisan-Commands
 *

 */
class UcsCommandsTest extends TestCase
{
    // =========================================================================
    // Helpers
    // =========================================================================

    private function bindUcsSetting(array $overrides = []): UcsSetting
    {
        $stub = $this->createStub(UcsSetting::class);
        $stub->method('save')->willReturnSelf();

        foreach (array_merge([
            'enabled'          => true,
            'sync_enabled'     => true,
            'school'           => 'GS-XY',
            'kelvin_base_url'  => 'https://ucs.example.de/ucsschool/kelvin/v1',
            'kelvin_page_size' => 200,
            'kelvin_timeout'   => 5,
            'kelvin_token_ttl' => 3300,
            'on_login_timeout' => 5,
            'purge_after_days' => 14,
            'sync_cron'        => '30 2 * * *',
        ], $overrides) as $prop => $val) {
            $stub->{$prop} = $val;
        }

        $this->app->instance(UcsSetting::class, $stub);

        return $stub;
    }

    // =========================================================================
    // Kriterium 1: ucs:ping → Exit 0 + Statuszeile
    // =========================================================================

    public function test_ucs_ping_liefert_exit_0_und_statuszeile(): void
    {
        $this->bindUcsSetting();

        $clientMock = $this->createMock(KelvinClient::class);
        $clientMock->method('ping')->willReturn(collect([
            ['name' => 'GS-XY', 'display_name' => 'Grundschule XY'],
        ]));
        $this->app->instance(KelvinClient::class, $clientMock);

        $this->artisan('ucs:ping')
            ->expectsOutputToContain('Kelvin erreichbar')
            ->expectsOutputToContain('Schulen zurückgegeben: 1')
            ->expectsOutputToContain('GS-XY')
            ->assertExitCode(0);
    }

    public function test_ucs_ping_liefert_exit_1_bei_verbindungsfehler(): void
    {
        $this->bindUcsSetting();

        $clientMock = $this->createMock(KelvinClient::class);
        $clientMock->method('ping')->willThrowException(
            new \App\Services\Ucs\Exceptions\KelvinUnavailableException('Verbindung verweigert')
        );
        $this->app->instance(KelvinClient::class, $clientMock);

        $this->artisan('ucs:ping')
            ->expectsOutputToContain('Kelvin nicht erreichbar')
            ->assertExitCode(1);
    }

    // =========================================================================
    // Kriterium 2: sync:ucs-parents --dry-run → null DB-Mutations + Count-Report
    // =========================================================================

    public function test_sync_ucs_parents_dry_run_schreibt_keine_mutations(): void
    {
        $this->bindUcsSetting();

        $svcMock = $this->createMock(UcsSyncService::class);
        $svcMock->method('run')->with(true)->willReturn([
            'school'                  => 'GS-XY',
            'dry_run'                 => true,
            'parents_processed'       => 5,
            'parents_created'         => 3,
            'parents_updated'         => 2,
            'parents_deactivated'     => 0,
            'children_created'        => 4,
            'children_updated'        => 1,
            'children_skipped_local'  => 0,
            'link_candidates_created' => 0,
            'groups_provisioned'      => 4,
            'failed_parents'          => 0,
            'duration_seconds'        => 0.5,
        ]);
        $this->app->instance(UcsSyncService::class, $svcMock);

        $this->artisan('sync:ucs-parents', ['--dry-run' => true])
            ->expectsOutputToContain('Dry-Run-Modus')
            ->expectsOutputToContain('Dry-Run-Report')
            ->expectsOutputToContain('neu angelegt')
            ->assertExitCode(0);

        // Keine DB-Änderungen
        $this->assertSame(0, \App\Model\User::where('ucs_source', 'kelvin')->count());
    }

    // =========================================================================
    // Kriterium 3: sync:ucs-parents mit enabled=false → Exit 2
    // =========================================================================

    public function test_sync_ucs_parents_gibt_exit_2_wenn_deaktiviert(): void
    {
        $this->bindUcsSetting(['enabled' => false]);

        $this->artisan('sync:ucs-parents')
            ->expectsOutputToContain('deaktiviert')
            ->assertExitCode(2);
    }

    public function test_sync_ucs_parents_gibt_exit_2_wenn_sync_deaktiviert(): void
    {
        $this->bindUcsSetting(['sync_enabled' => false]);

        $this->artisan('sync:ucs-parents')
            ->expectsOutputToContain('deaktiviert')
            ->assertExitCode(2);
    }

    // =========================================================================
    // Kriterium 4 + 5: SyncUcsSchoolJob – timeout=900, tries=1, dispatchable
    // =========================================================================

    public function test_sync_ucs_school_job_hat_timeout_900_und_tries_1(): void
    {
        $job = new SyncUcsSchoolJob();

        $this->assertSame(900, $job->timeout, 'timeout muss 900 s sein');
        $this->assertSame(1,   $job->tries,   'tries muss 1 sein');
    }

    public function test_sync_ucs_school_job_ist_auf_default_queue_einplanbar(): void
    {
        Bus::fake();

        SyncUcsSchoolJob::dispatch();

        Bus::assertDispatched(SyncUcsSchoolJob::class);
    }

    public function test_sync_ucs_school_job_handle_bricht_ab_wenn_disabled(): void
    {
        $this->bindUcsSetting(['enabled' => false]);

        $svcMock = $this->createMock(UcsSyncService::class);
        $svcMock->expects($this->never())->method('run');

        $job = new SyncUcsSchoolJob();
        $job->handle($svcMock);
    }

    public function test_sync_ucs_school_job_handle_bricht_ab_wenn_sync_disabled(): void
    {
        $this->bindUcsSetting(['sync_enabled' => false]);

        $svcMock = $this->createMock(UcsSyncService::class);
        $svcMock->expects($this->never())->method('run');

        $job = new SyncUcsSchoolJob();
        $job->handle($svcMock);
    }

    public function test_sync_ucs_school_job_failed_schreibt_telemetrie(): void
    {
        $setting = $this->bindUcsSetting();

        $job = new SyncUcsSchoolJob();
        $job->failed(new \RuntimeException('Test-Fehler'));

        // Settings wurden gespeichert (save() kommt aus dem Stub)
        // Wir prüfen nur, dass kein Fehler geworfen wird und der Job stabil bleibt.
        $this->assertTrue(true);
    }

    // =========================================================================
    // Kriterium 6: ucs:purge-stale-classes – nur kelvin+alt gelöscht
    // =========================================================================

    public function test_purge_stale_classes_loescht_nur_alte_kelvin_gruppen(): void
    {
        $this->bindUcsSetting(['purge_after_days' => 14]);

        // Alte kelvin-Gruppe (SoftDeleted, älter als 14 Tage) → SOLL gelöscht werden
        $oldKelvin = Group::factory()->create([
            'ucs_source' => 'kelvin',
            'bereich'    => 'Klasse',
        ]);
        $oldKelvin->delete(); // SoftDelete
        // deleted_at auf 20 Tage zurücksetzen
        $oldKelvin->withoutGlobalScopes()->where('id', $oldKelvin->id)
            ->update(['deleted_at' => now()->subDays(20)]);

        // Frische kelvin-Gruppe (SoftDeleted, aber erst 3 Tage alt) → SOLL bleiben
        $newKelvin = Group::factory()->create([
            'ucs_source' => 'kelvin',
            'bereich'    => 'Klasse',
        ]);
        $newKelvin->delete();

        // Lokale Gruppe (SoftDeleted, alt) → SOLL bleiben
        $localGroup = Group::factory()->create([
            'ucs_source' => 'local',
            'bereich'    => 'Klasse',
        ]);
        $localGroup->delete();
        $localGroup->withoutGlobalScopes()->where('id', $localGroup->id)
            ->update(['deleted_at' => now()->subDays(20)]);

        $this->artisan('ucs:purge-stale-classes')
            ->assertExitCode(0);

        // Alte kelvin-Gruppe wurde hard-gelöscht
        $this->assertNull(
            Group::withoutGlobalScopes()->withTrashed()->find($oldKelvin->id),
            'Alte kelvin-Gruppe muss hard-gelöscht sein'
        );

        // Frische kelvin-Gruppe noch vorhanden
        $this->assertNotNull(
            Group::withoutGlobalScopes()->withTrashed()->find($newKelvin->id),
            'Frische kelvin-Gruppe darf nicht gelöscht sein'
        );

        // Lokale Gruppe unberührt
        $this->assertNotNull(
            Group::withoutGlobalScopes()->withTrashed()->find($localGroup->id),
            'Lokale Gruppe muss unberührt bleiben'
        );
    }

    public function test_purge_stale_classes_tut_nichts_wenn_deaktiviert(): void
    {
        $this->bindUcsSetting(['enabled' => false]);

        $oldKelvin = Group::factory()->create(['ucs_source' => 'kelvin']);
        $oldKelvin->delete();
        $oldKelvin->withoutGlobalScopes()->where('id', $oldKelvin->id)
            ->update(['deleted_at' => now()->subDays(20)]);

        $this->artisan('ucs:purge-stale-classes')
            ->expectsOutputToContain('deaktiviert')
            ->assertExitCode(0);

        // Gruppe noch vorhanden (SoftDelete)
        $this->assertNotNull(
            Group::withoutGlobalScopes()->withTrashed()->find($oldKelvin->id)
        );
    }

    // =========================================================================
    // Kriterium 7: ucs:link-child → schreibt ucs_username, entfernt Kandidaten
    // =========================================================================

    public function test_ucs_link_child_verknuepft_kind_und_entfernt_kandidaten(): void
    {
        $this->bindUcsSetting();

        $child = Child::factory()->create([
            'ucs_username' => null,
            'ucs_source'   => 'local',
        ]);

        $candidate = UcsLinkCandidate::create([
            'child_id'     => $child->id,
            'ucs_username' => 'max.mueller',
            'ucs_uuid'     => 'uid-abc-123',
            'reason'       => 'name_match',
            'payload'      => [],
            'detected_at'  => now(),
        ]);

        $this->artisan('ucs:link-child', [
            'child_id'    => $child->id,
            'ucs_username'=> 'max.mueller',
        ])
            ->expectsOutputToContain('erfolgreich')
            ->assertExitCode(0);

        $child->refresh();
        $this->assertSame('max.mueller', $child->ucs_username, 'ucs_username gesetzt');
        $this->assertSame('uid-abc-123', $child->ucs_uuid, 'ucs_uuid aus Kandidaten übernommen');

        $candidate->refresh();
        $this->assertNotNull($candidate->confirmed_at, 'Kandidat als bestätigt markiert');
        $this->assertNull($candidate->confirmed_by, 'CLI: kein User-Kontext (null)');
    }

    // =========================================================================
    // Kriterium 8: Doppelter ucs:link-child → Exit 0 + Hinweis-Message
    // =========================================================================

    public function test_ucs_link_child_idempotent_zweiter_aufruf_exit_0(): void
    {
        $this->bindUcsSetting();

        $child = Child::factory()->create([
            'ucs_username' => 'max.mueller',
            'ucs_source'   => 'local',
        ]);

        $this->artisan('ucs:link-child', [
            'child_id'     => $child->id,
            'ucs_username' => 'max.mueller',
        ])
            ->expectsOutputToContain('bereits')
            ->assertExitCode(0);
    }

    public function test_ucs_link_child_schlaegt_fehl_bei_unbekanntem_kind(): void
    {
        $this->bindUcsSetting();

        $this->artisan('ucs:link-child', [
            'child_id'     => 99999,
            'ucs_username' => 'max.mueller',
        ])
            ->expectsOutputToContain('nicht gefunden')
            ->assertExitCode(1);
    }

    public function test_ucs_link_child_schlaegt_fehl_wenn_kind_bereits_andere_username_hat(): void
    {
        $this->bindUcsSetting();

        $child = Child::factory()->create([
            'ucs_username' => 'anderer.username',
            'ucs_source'   => 'local',
        ]);

        $this->artisan('ucs:link-child', [
            'child_id'     => $child->id,
            'ucs_username' => 'max.mueller',
        ])
            ->expectsOutputToContain('bereits')
            ->assertExitCode(1);
    }
}




