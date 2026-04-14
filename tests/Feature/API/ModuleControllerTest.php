<?php

namespace Tests\Feature\API;

use App\Model\Module;
use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ModuleControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Migrations können Module direkt einfügen (z. B. Familienübersicht).
        // Vor jedem Test auf einen sauberen Zustand zurücksetzen.
        Module::query()->delete();

        $this->user = User::factory()->create();
    }

    // -------------------------------------------------------------------------
    // Authentifizierung
    // -------------------------------------------------------------------------

    /** @test */
    public function unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/modules');

        $response->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // Leere Liste
    // -------------------------------------------------------------------------

    /** @test */
    public function returns_empty_list_when_no_modules_exist(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/modules');

        $response->assertStatus(200)
            ->assertExactJson([
                'success' => true,
                'data'    => [],
            ]);
    }

    // -------------------------------------------------------------------------
    // Kategorie-Filter
    // -------------------------------------------------------------------------

    /** @test */
    public function only_modules_with_category_module_are_returned(): void
    {
        // Kategorie 'module' → soll erscheinen
        Module::factory()->create([
            'setting'     => 'Nachrichten',
            'category'    => 'module',
            'description' => 'Nachrichtenbereich',
            'options'     => ['active' => '1', 'rights' => []],
        ]);

        // Kategorie 'setting' → soll NICHT erscheinen
        Module::factory()->create([
            'setting'     => 'Push to WordPress',
            'category'    => 'setting',
            'description' => 'Wordpress-Integration',
            'options'     => ['active' => '0'],
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/modules');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.setting', 'Nachrichten');
    }

    // -------------------------------------------------------------------------
    // Response-Struktur
    // -------------------------------------------------------------------------

    /** @test */
    public function response_has_correct_structure(): void
    {
        Module::factory()->create([
            'setting'     => 'Listen',
            'category'    => 'module',
            'description' => 'Termin- und Eintragslisten',
            'options'     => ['active' => '1', 'rights' => [], 'nav' => ['link' => 'listen']],
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/modules');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['setting', 'category', 'description', 'options'],
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.setting', 'Listen')
            ->assertJsonPath('data.0.category', 'Module')          // kapitalisiert
            ->assertJsonPath('data.0.description', 'Termin- und Eintragslisten')
            ->assertJsonPath('data.0.options.active', true);

        // Interne Nav-Konfiguration darf nicht in der Antwort erscheinen
        $this->assertArrayNotHasKey('nav', $response->json('data.0.options'));
    }

    // -------------------------------------------------------------------------
    // active-Normalisierung
    // -------------------------------------------------------------------------

    /** @test */
    public function active_string_1_is_normalized_to_true(): void
    {
        Module::factory()->create([
            'setting'  => 'Aktiv-String',
            'category' => 'module',
            'options'  => ['active' => '1', 'rights' => []],
        ]);

        Sanctum::actingAs($this->user);

        $this->getJson('/api/modules')
            ->assertJsonPath('data.0.options.active', true);
    }

    /** @test */
    public function active_integer_1_is_normalized_to_true(): void
    {
        Module::factory()->create([
            'setting'  => 'Aktiv-Int',
            'category' => 'module',
            'options'  => ['active' => 1, 'rights' => []],
        ]);

        Sanctum::actingAs($this->user);

        $this->getJson('/api/modules')
            ->assertJsonPath('data.0.options.active', true);
    }

    /** @test */
    public function active_boolean_true_is_normalized_to_true(): void
    {
        Module::factory()->create([
            'setting'  => 'Aktiv-Bool',
            'category' => 'module',
            'options'  => ['active' => true, 'rights' => []],
        ]);

        Sanctum::actingAs($this->user);

        $this->getJson('/api/modules')
            ->assertJsonPath('data.0.options.active', true);
    }

    /** @test */
    public function active_string_0_is_normalized_to_false(): void
    {
        Module::factory()->create([
            'setting'  => 'Inaktiv-String',
            'category' => 'module',
            'options'  => ['active' => '0', 'rights' => []],
        ]);

        Sanctum::actingAs($this->user);

        $this->getJson('/api/modules')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.options.active', false);
    }

    /** @test */
    public function active_integer_0_is_normalized_to_false(): void
    {
        Module::factory()->create([
            'setting'  => 'Inaktiv-Int',
            'category' => 'module',
            'options'  => ['active' => 0, 'rights' => []],
        ]);

        Sanctum::actingAs($this->user);

        $this->getJson('/api/modules')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.options.active', false);
    }

    // -------------------------------------------------------------------------
    // Berechtigungsfilter
    // -------------------------------------------------------------------------

    /** @test */
    public function module_without_rights_is_visible_to_everyone(): void
    {
        Module::factory()->create([
            'setting'  => 'Öffentlich',
            'category' => 'module',
            'options'  => ['active' => '1', 'rights' => []],
        ]);

        // Benutzer ohne besondere Rechte
        $plainUser = User::factory()->create();
        Sanctum::actingAs($plainUser);

        $this->getJson('/api/modules')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.setting', 'Öffentlich');
    }

    /** @test */
    public function module_with_required_permission_is_hidden_when_user_lacks_it(): void
    {
        Permission::firstOrCreate(['name' => 'view vertretungsplan', 'guard_name' => 'web']);

        Module::factory()->create([
            'setting'  => 'Vertretungsplan',
            'category' => 'module',
            'options'  => ['active' => '1', 'rights' => ['view vertretungsplan']],
        ]);

        // Benutzer ohne die benötigte Berechtigung
        $userWithoutPermission = User::factory()->create();
        Sanctum::actingAs($userWithoutPermission);

        $this->getJson('/api/modules')
            ->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    /** @test */
    public function module_with_required_permission_is_visible_when_user_has_it(): void
    {
        Permission::firstOrCreate(['name' => 'view vertretungsplan', 'guard_name' => 'web']);

        Module::factory()->create([
            'setting'  => 'Vertretungsplan',
            'category' => 'module',
            'options'  => ['active' => '1', 'rights' => ['view vertretungsplan']],
        ]);

        $this->user->givePermissionTo('view vertretungsplan');
        Sanctum::actingAs($this->user);

        $this->getJson('/api/modules')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.setting', 'Vertretungsplan');
    }

    /** @test */
    public function user_with_one_of_multiple_required_permissions_can_see_module(): void
    {
        Permission::firstOrCreate(['name' => 'view groups',         'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view external offer', 'guard_name' => 'web']);

        Module::factory()->create([
            'setting'  => 'Gruppen',
            'category' => 'module',
            'options'  => ['active' => '1', 'rights' => ['view groups', 'view external offer']],
        ]);

        // Benutzer hat nur eine der geforderten Berechtigungen
        $this->user->givePermissionTo('view groups');
        Sanctum::actingAs($this->user);

        $this->getJson('/api/modules')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.setting', 'Gruppen');
    }

    // -------------------------------------------------------------------------
    // Mehrere Module kombiniert
    // -------------------------------------------------------------------------

    /** @test */
    public function returns_mix_of_accessible_and_inaccessible_modules_correctly(): void
    {
        Permission::firstOrCreate(['name' => 'view vertretungsplan', 'guard_name' => 'web']);

        // Für alle sichtbar, aktiv
        Module::factory()->create([
            'setting'  => 'Nachrichten',
            'category' => 'module',
            'options'  => ['active' => '1', 'rights' => []],
        ]);

        // Für alle sichtbar, inaktiv
        Module::factory()->create([
            'setting'  => 'Stundenplan',
            'category' => 'module',
            'options'  => ['active' => '0', 'rights' => []],
        ]);

        // Nur mit Berechtigung sichtbar – Benutzer hat sie NICHT
        Module::factory()->create([
            'setting'  => 'Vertretungsplan',
            'category' => 'module',
            'options'  => ['active' => '1', 'rights' => ['view vertretungsplan']],
        ]);

        // Andere Kategorie – niemals zurückgeben
        Module::factory()->create([
            'setting'  => 'Push to WordPress',
            'category' => 'setting',
            'options'  => ['active' => '1'],
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/modules');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');  // Nachrichten + Stundenplan

        $settings = collect($response->json('data'))->pluck('setting');
        $this->assertContains('Nachrichten', $settings);
        $this->assertContains('Stundenplan', $settings);
        $this->assertNotContains('Vertretungsplan', $settings);
        $this->assertNotContains('Push to WordPress', $settings);
    }
}



