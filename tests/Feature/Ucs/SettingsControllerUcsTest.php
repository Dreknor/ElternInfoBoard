<?php

namespace Tests\Feature\Ucs;

use App\Jobs\SyncUcsSchoolJob;
use App\Services\Ucs\KelvinClient;
use App\Settings\UcsSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Feature-Tests für SettingsController – UCS-Tab (TODO-07)
 *
 * Deckt alle 9 Gelingenskriterien aus docs/todos/07-settings-tab-ucs.md ab.
 */
class SettingsControllerUcsTest extends TestCase
{
    // =========================================================================
    // Setup: Permissions und Admin-User anlegen
    // =========================================================================

    protected function setUp(): void
    {
        parent::setUp();

        // Permissions anlegen (via RefreshDatabase nach jedem Test zurückgesetzt)
        Permission::firstOrCreate(['name' => 'edit settings',   'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage ucs sync', 'guard_name' => 'web']);
    }

    /** Admin-User mit 'edit settings' UND 'manage ucs sync'. */
    private function adminUser(): \App\Model\User
    {
        $user = \App\Model\User::factory()->create();
        $user->givePermissionTo('edit settings');
        $user->givePermissionTo('manage ucs sync');

        return $user;
    }

    /** User nur mit 'edit settings', OHNE 'manage ucs sync'. */
    private function editorUser(): \App\Model\User
    {
        $user = \App\Model\User::factory()->create();
        $user->givePermissionTo('edit settings');

        return $user;
    }

    // =========================================================================
    // Kriterium 1: Tab sichtbar und navigierbar
    // =========================================================================

    /**
     * @test
     * Gelingenskriterium 1: GET /settings ist 200 und enthält den UCS-Tab.
     */
    public function test_settings_seite_enthaelt_ucs_tab(): void
    {
        $this->actingAs($this->adminUser());

        $response = $this->get('/settings');

        $response->assertStatus(200);
        $response->assertSee('UCS@school');
        $response->assertSee('ucs-tab');          // Tab-Button data-target
    }

    // =========================================================================
    // Kriterium 2: Formular speichert korrekt; Passwort bleibt erhalten
    // =========================================================================

    /**
     * @test
     * Gelingenskriterium 2: PUT /settings/ucs speichert alle Felder.
     * Passwort wird nicht überschrieben, wenn Feld leer ist.
     */
    public function test_ucs_formular_speichert_korrekt_und_passwort_bleibt_erhalten(): void
    {
        $this->actingAs($this->adminUser());

        // Erst ein Passwort setzen
        $this->put('/settings/ucs', [
            'kelvin_base_url'  => 'https://ucs.example.de/ucsschool/kelvin/v1',
            'school'           => 'GS-Test',
            'kelvin_username'  => 'admin',
            'kelvin_password'  => 'geheimes_passwort',
            'kelvin_page_size' => 100,
            'kelvin_timeout'   => 30,
            'kelvin_token_ttl' => 3300,
            'sync_cron'        => '0 3 * * *',
            'on_login_timeout' => 5,
            'purge_after_days' => 14,
        ])->assertRedirect()->assertSessionHas('type', 'success');

        // Dann ohne Passwort erneut speichern
        $this->put('/settings/ucs', [
            'kelvin_base_url'  => 'https://ucs.example.de/ucsschool/kelvin/v1',
            'school'           => 'GS-Geändert',
            'kelvin_username'  => 'admin',
            'kelvin_password'  => '',  // leer → bestehendes Passwort beibehalten
            'kelvin_page_size' => 200,
            'kelvin_timeout'   => 30,
            'kelvin_token_ttl' => 3300,
            'sync_cron'        => '0 3 * * *',
            'on_login_timeout' => 5,
            'purge_after_days' => 14,
        ])->assertRedirect();

        $ucs = new UcsSetting;
        $this->assertSame('GS-Geändert', $ucs->school, 'school wurde gespeichert');
        $this->assertNotEmpty($ucs->kelvin_password, 'Passwort blieb erhalten');
    }

    // =========================================================================
    // Kriterium 3: Ungültiger sync_cron → Validierungsfehler
    // =========================================================================

    /**
     * @test
     * Gelingenskriterium 3: Ungültiger Cron-Ausdruck wird abgelehnt.
     */
    public function test_ungueliger_sync_cron_wird_abgelehnt(): void
    {
        $this->actingAs($this->adminUser());

        $response = $this->put('/settings/ucs', [
            'kelvin_page_size' => 200,
            'kelvin_timeout'   => 30,
            'kelvin_token_ttl' => 3300,
            'sync_cron'        => 'kaputt',  // ungültiger Cron
            'on_login_timeout' => 5,
            'purge_after_days' => 14,
        ]);

        $response->assertSessionHasErrors('sync_cron');
    }

    // =========================================================================
    // Kriterium 4: Test-Button mit gültigen Credentials → grüne Flash-Message
    // =========================================================================

    /**
     * @test
     * Gelingenskriterium 4: Test-Button mit gültigem KelvinClient → Erfolg-Flash.
     */
    public function test_verbindungstest_mit_gueltigen_credentials_liefert_erfolg(): void
    {
        $this->actingAs($this->adminUser());

        $clientMock = $this->createMock(KelvinClient::class);
        $clientMock->expects($this->once())
                   ->method('ping')
                   ->willReturn(collect(['GS-XY', 'GS-AB']));

        $this->app->instance(KelvinClient::class, $clientMock);

        $response = $this->post(route('settings.ucs.test'));

        $response->assertRedirect();
        $response->assertSessionHas('type', 'success');
        $this->assertStringContainsString(
            'Verbindung OK',
            session('Meldung') ?? ''
        );
        $this->assertStringContainsString('2', session('Meldung') ?? '');
    }

    // =========================================================================
    // Kriterium 5: Test-Button mit falschen Credentials → rote Flash-Message
    // =========================================================================

    /**
     * @test
     * Gelingenskriterium 5: Test-Button mit fehlerhaftem Client → Error-Flash.
     */
    public function test_verbindungstest_mit_fehlerhaften_credentials_liefert_fehler(): void
    {
        $this->actingAs($this->adminUser());

        $clientMock = $this->createMock(KelvinClient::class);
        $clientMock->expects($this->once())
                   ->method('ping')
                   ->willThrowException(new \RuntimeException('401 Unauthorized'));

        $this->app->instance(KelvinClient::class, $clientMock);

        $response = $this->post(route('settings.ucs.test'));

        $response->assertRedirect();
        $response->assertSessionHas('type', 'danger');
        $this->assertStringContainsString(
            '401 Unauthorized',
            session('Meldung') ?? ''
        );
    }

    // =========================================================================
    // Kriterium 6: Sync-Button → Job in Queue + Flash-Message
    // =========================================================================

    /**
     * @test
     * Gelingenskriterium 6: POST /settings/ucs/sync dispatcht SyncUcsSchoolJob.
     */
    public function test_sync_button_stellt_job_in_queue(): void
    {
        Bus::fake();

        $this->actingAs($this->adminUser());

        $response = $this->post(route('settings.ucs.sync'));

        $response->assertRedirect();
        $response->assertSessionHas('type', 'success');
        $this->assertStringContainsString(
            'Warteschlange',
            session('Meldung') ?? ''
        );

        Bus::assertDispatched(SyncUcsSchoolJob::class);
    }

    // =========================================================================
    // Kriterium 7: User ohne 'manage ucs sync' → Sync-Button nicht sichtbar + 403 bei POST
    // =========================================================================

    /**
     * @test
     * Gelingenskriterium 7a: Sync-Button ist für User ohne 'manage ucs sync' nicht sichtbar.
     */
    public function test_sync_button_nicht_sichtbar_ohne_manage_ucs_sync(): void
    {
        $this->actingAs($this->editorUser());

        $response = $this->get('/settings');

        $response->assertStatus(200);
        // Der Sync-Button enthält diese Route
        $response->assertDontSee(route('settings.ucs.sync'));
    }

    /**
     * @test
     * Gelingenskriterium 7b: Direkter POST auf Sync-Route ohne Berechtigung → 403.
     */
    public function test_sync_route_liefert_403_ohne_berechtigung(): void
    {
        $this->actingAs($this->editorUser());

        $response = $this->post(route('settings.ucs.sync'));

        $response->assertStatus(403);
    }

    // =========================================================================
    // Kriterium 8: Credentials nicht sichtbar ohne 'edit settings'
    // =========================================================================

    /**
     * @test
     * Gelingenskriterium 8: User ohne 'edit settings' sieht kein Credentials-Formular
     * sondern den Lock-Hinweis; direkter POST auf Settings-Route → 403.
     */
    public function test_credentials_nicht_sichtbar_ohne_edit_settings(): void
    {
        // User ohne jegliche Settings-Berechtigung
        $user = \App\Model\User::factory()->create();

        // GET /settings → 403 (Controller-Middleware blockiert)
        $this->actingAs($user)
             ->get('/settings')
             ->assertStatus(403);

        // Direkter PUT → ebenfalls 403
        $this->actingAs($user)
             ->put('/settings/ucs', ['sync_cron' => '0 2 * * *'])
             ->assertStatus(403);
    }

    /**
     * @test
     * Gelingenskriterium 8b: Lock-Hinweis im UCS-Tab für User ohne 'edit settings'.
     * getestet über direktes Blade-Rendering mit withoutMiddleware().
     */
    public function test_lock_hinweis_erscheint_wenn_kein_edit_settings(): void
    {
        // User mit manage ucs sync, aber bewusst OHNE edit settings
        Permission::firstOrCreate(['name' => 'manage ucs sync', 'guard_name' => 'web']);
        $user = \App\Model\User::factory()->create();
        $user->givePermissionTo('manage ucs sync');

        // Middleware für Permission-Check deaktivieren, sodass der Controller-Code läuft
        $response = $this->actingAs($user)
                         ->withoutMiddleware(\Spatie\Permission\Middleware\PermissionMiddleware::class)
                         ->get('/settings');

        $response->assertStatus(200);
        // Lock-Hinweis muss erscheinen
        $response->assertSee('edit settings');
        // Passwort-Feld darf NICHT sichtbar sein
        $response->assertDontSee('name="kelvin_password"');
    }

    // =========================================================================
    // Kriterium 9: Status-Karte zeigt Telemetrie korrekt
    // =========================================================================

    /**
     * @test
     * Gelingenskriterium 9: Status-Karte zeigt last_sync_at, last_sync_status und Counter.
     */
    public function test_status_karte_zeigt_telemetrie(): void
    {
        // Telemetrie-Werte in der DB setzen
        $ucs = new UcsSetting;
        $ucs->last_sync_at      = '2026-05-20 02:30:00';
        $ucs->last_sync_status  = 'success';
        $ucs->last_sync_parents  = 42;
        $ucs->last_sync_students = 87;
        $ucs->save();

        $this->actingAs($this->adminUser());

        $response = $this->get('/settings');

        $response->assertStatus(200);
        $response->assertSee('20.05.2026');      // Datum formatiert
        $response->assertSee('Erfolgreich');      // Badge-Text
        $response->assertSee('42');               // Eltern-Counter
        $response->assertSee('87');               // Schüler-Counter
    }
}

