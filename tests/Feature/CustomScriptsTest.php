<?php

namespace Tests\Feature;

use App\Model\CustomScript;
use App\Model\User;
use App\Services\CustomScripts\CustomScriptRenderer;
use App\Settings\CustomScriptSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomScriptsTest extends TestCase
{
    private const SNIPPET = '<script src="https://chatbot.example/widget.js" data-id="abc" defer></script>';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->app->forgetScopedInstances();
        config(['custom_scripts.enabled' => false]);
    }

    private function admin(): User
    {
        Permission::firstOrCreate(['name' => 'edit settings', 'guard_name' => 'web']);
        $user = User::factory()->create(['changePassword' => false]);
        $user->givePermissionTo('edit settings');

        return $user;
    }

    private function script(array $attributes = [], array $roles = []): CustomScript
    {
        $script = CustomScript::create(array_merge([
            'name' => 'Test',
            'active' => true,
            'snippet' => self::SNIPPET,
            'position' => 'body',
            'page_mode' => CustomScript::PAGE_ALL,
            'for_guests' => false,
            'for_users' => true,
            'for_students' => false,
            'restrict_roles' => false,
            'use_on_chat' => false,
            'sort_order' => 0,
        ], $attributes));

        if ($roles !== []) {
            $script->roles()->sync(collect($roles)->map->id->all());
        }

        $this->app->forgetScopedInstances();

        return $script;
    }

    private function role(string $name): Role
    {
        return Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    private function renderer(): CustomScriptRenderer
    {
        $this->app->forgetScopedInstances();

        return app(CustomScriptRenderer::class);
    }

    /** Gültige Formulardaten für ein Skript. */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Chatbot',
            'description' => 'Anbieter X',
            'active' => '1',
            'snippet' => self::SNIPPET,
            'position' => 'body',
            'page_mode' => 'all',
            'page_patterns' => '',
            'for_guests' => '0',
            'for_users' => '1',
            'for_students' => '0',
            'restrict_roles' => '0',
            'use_on_chat' => '0',
            'sort_order' => '10',
        ], $overrides);
    }

    // ----- Freischaltung und Berechtigung --------------------------------------------------

    public function test_settings_pages_are_hidden_when_instance_not_unlocked(): void
    {
        $admin = $this->admin();
        $script = $this->script();

        $this->actingAs($admin)->get('/settings/custom-scripts')->assertNotFound();
        $this->actingAs($admin)->get('/settings/custom-scripts/create')->assertNotFound();
        $this->actingAs($admin)->post('/settings/custom-scripts', $this->payload())->assertNotFound();
        $this->actingAs($admin)->delete("/settings/custom-scripts/{$script->id}")->assertNotFound();

        $this->assertSame(1, CustomScript::count());
    }

    public function test_nothing_is_rendered_when_instance_not_unlocked(): void
    {
        $this->script(['for_guests' => true]);
        $this->actingAs(User::factory()->create());

        $this->assertSame('', $this->renderer()->render('body'));
    }

    public function test_login_page_has_no_trace_when_instance_not_unlocked(): void
    {
        $this->script(['for_guests' => true]);

        $this->get('/login')->assertOk()
            ->assertDontSee('chatbot.example', false)
            ->assertDontSee('custom-script', false);
    }

    public function test_users_without_permission_cannot_edit(): void
    {
        config(['custom_scripts.enabled' => true]);
        $user = User::factory()->create(['changePassword' => false]);
        $script = $this->script();

        $this->actingAs($user)->get('/settings/custom-scripts')->assertForbidden();
        $this->actingAs($user)->post('/settings/custom-scripts', $this->payload())->assertForbidden();
        $this->actingAs($user)->patch("/settings/custom-scripts/{$script->id}/toggle")->assertForbidden();
        $this->actingAs($user)->delete("/settings/custom-scripts/{$script->id}")->assertForbidden();
    }

    public function test_settings_index_links_page_only_when_unlocked(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/settings')->assertOk()->assertDontSee('Eigene Skripte');

        config(['custom_scripts.enabled' => true]);
        $this->actingAs($admin)->get('/settings')->assertOk()->assertSee('Eigene Skripte');
    }

    // ----- Verwaltung ---------------------------------------------------------------------

    public function test_admin_can_create_edit_toggle_and_delete_scripts(): void
    {
        config(['custom_scripts.enabled' => true]);
        $admin = $this->admin();
        $role = $this->role('Mitarbeiter');

        $this->actingAs($admin)->get('/settings/custom-scripts')->assertOk()->assertSee('Eigene Skripte');
        $this->actingAs($admin)->get('/settings/custom-scripts/create')->assertOk()->assertSee('Mitarbeiter');

        $this->actingAs($admin)->post('/settings/custom-scripts', $this->payload([
            'position' => 'head',
            'page_mode' => 'only',
            'page_patterns' => "  /Settings/Custom-Scripts* \n\n care/anwesenheit* ",
            'restrict_roles' => '1',
            'roles' => [$role->id],
        ]))->assertRedirect(route('settings.custom-scripts.index'))->assertSessionHas('type', 'success');

        $script = CustomScript::sole();
        $this->assertSame('Chatbot', $script->name);
        $this->assertTrue($script->active);
        $this->assertSame('head', $script->position);
        $this->assertSame("Settings/Custom-Scripts*\ncare/anwesenheit*", $script->page_patterns);
        $this->assertTrue($script->restrict_roles);
        $this->assertSame([$role->id], $script->roles->pluck('id')->all());
        $this->assertSame($admin->id, $script->updated_by);

        // Admin hat die Rolle noch nicht: nicht ausgegeben
        $html = $this->actingAs($admin)->get('/settings/custom-scripts')->getContent();
        $this->assertStringNotContainsString(self::SNIPPET, $html);

        // Mit Rolle: im Kopf der passenden Seite ausgegeben
        $admin->assignRole($role);
        $this->app->forgetScopedInstances();
        $html = $this->actingAs($admin->fresh())->get('/settings/custom-scripts')->assertOk()->getContent();
        $this->assertStringContainsString(self::SNIPPET, $html);
        $this->assertLessThan(strpos($html, '</head>'), strpos($html, self::SNIPPET));

        // Bearbeiten: Rollenbegrenzung aus → Rollen entfernt
        $this->actingAs($admin)->get("/settings/custom-scripts/{$script->id}/edit")->assertOk()->assertSee('Chatbot');
        $this->actingAs($admin)->put("/settings/custom-scripts/{$script->id}", $this->payload([
            'name' => 'Chatbot 2', 'restrict_roles' => '0', 'roles' => [$role->id],
        ]))->assertRedirect(route('settings.custom-scripts.index'));
        $script->refresh();
        $this->assertSame('Chatbot 2', $script->name);
        $this->assertFalse($script->restrict_roles);
        $this->assertCount(0, $script->roles);

        // Schnell deaktivieren
        $this->actingAs($admin)->patch("/settings/custom-scripts/{$script->id}/toggle")->assertRedirect();
        $this->assertFalse($script->fresh()->active);

        // Löschen
        $this->actingAs($admin)->delete("/settings/custom-scripts/{$script->id}")->assertRedirect();
        $this->assertSame(0, CustomScript::count());
    }

    public function test_validation_rejects_bad_input(): void
    {
        config(['custom_scripts.enabled' => true, 'custom_scripts.max_length' => 100]);
        $admin = $this->admin();

        $cases = [
            'snippet' => [['snippet' => '</body><script>x()</script>'], ['snippet' => str_repeat('a', 101)], ['snippet' => '']],
            'name' => [['name' => '']],
            'position' => [['position' => 'footer']],
            'page_mode' => [['page_mode' => 'sometimes']],
            'page_patterns' => [['page_mode' => 'only', 'page_patterns' => "  \n "], ['page_mode' => 'except']],
            'for_users' => [['for_users' => '0', 'for_guests' => '0', 'for_students' => '0']],
            'roles' => [['restrict_roles' => '1'], ['restrict_roles' => '1', 'roles' => []]],
            'roles.0' => [['restrict_roles' => '1', 'roles' => [999999]]],
        ];

        foreach ($cases as $field => $variants) {
            foreach ($variants as $overrides) {
                $this->actingAs($admin)->post('/settings/custom-scripts', $this->payload($overrides))
                    ->assertSessionHasErrors($field);
            }
        }

        $this->assertSame(0, CustomScript::count());
    }

    public function test_saving_clears_disabled_reason(): void
    {
        config(['custom_scripts.enabled' => true]);
        $admin = $this->admin();
        $script = $this->script(['active' => false, 'disabled_reason' => 'Automatisch deaktiviert']);

        $this->actingAs($admin)->get('/settings/custom-scripts')->assertSee('Automatisch deaktiviert');

        $this->actingAs($admin)->put("/settings/custom-scripts/{$script->id}", $this->payload())->assertSessionHasNoErrors();
        $this->assertNull($script->fresh()->disabled_reason);
    }

    // ----- Ausgabe: Ausgabeort, Reihenfolge, Seiten ---------------------------------------

    public function test_renders_only_active_scripts_at_their_position_in_order(): void
    {
        config(['custom_scripts.enabled' => true]);
        $this->script(['name' => 'B', 'snippet' => '<script>/*B*/</script>', 'sort_order' => 20]);
        $this->script(['name' => 'A', 'snippet' => '<script>/*A*/</script>', 'sort_order' => 10]);
        $this->script(['name' => 'C', 'snippet' => '<script>/*C*/</script>', 'sort_order' => 10]);
        $this->script(['name' => 'Kopf', 'snippet' => '<script>/*Kopf*/</script>', 'position' => 'head']);
        $this->script(['name' => 'Aus', 'snippet' => '<script>/*Aus*/</script>', 'active' => false]);

        $this->actingAs(User::factory()->create());
        $body = $this->renderer()->render('body');

        $this->assertStringNotContainsString('Kopf', $body);
        $this->assertStringNotContainsString('Aus', $body);
        $this->assertTrue(strpos($body, '/*A*/') < strpos($body, '/*C*/') && strpos($body, '/*C*/') < strpos($body, '/*B*/'));
        $this->assertStringContainsString('/*Kopf*/', $this->renderer()->render('head'));
    }

    public function test_page_patterns_match_paths(): void
    {
        $only = new CustomScript(['page_mode' => 'only', 'page_patterns' => "care/anwesenheit*\nLOGIN\n/"]);
        $this->assertTrue($only->matchesPath('care/anwesenheit'));
        $this->assertTrue($only->matchesPath('/care/anwesenheit/expected'));
        $this->assertTrue($only->matchesPath('login'));
        $this->assertTrue($only->matchesPath('/'));
        $this->assertFalse($only->matchesPath('nachrichten'));
        $this->assertFalse($only->matchesPath('home/login'));

        $except = new CustomScript(['page_mode' => 'except', 'page_patterns' => 'care/*']);
        $this->assertFalse($except->matchesPath('care/anwesenheit'));
        $this->assertTrue($except->matchesPath('home'));

        $all = new CustomScript(['page_mode' => 'all', 'page_patterns' => 'egal']);
        $this->assertTrue($all->matchesPath('irgendwo'));

        // Begrenzung ohne Muster: nirgends
        $this->assertFalse((new CustomScript(['page_mode' => 'only', 'page_patterns' => '']))->matchesPath('home'));
    }

    public function test_page_rule_is_applied_to_real_pages(): void
    {
        config(['custom_scripts.enabled' => true]);
        $this->script(['for_guests' => true, 'page_mode' => 'only', 'page_patterns' => 'password/*']);

        $this->get('/login')->assertOk()->assertDontSee(self::SNIPPET, false);

        CustomScript::query()->update(['page_patterns' => 'login']);
        $this->app->forgetScopedInstances();
        $this->get('/login?foo=bar')->assertOk()->assertSee(self::SNIPPET, false);

        CustomScript::query()->update(['page_mode' => 'except']);
        $this->app->forgetScopedInstances();
        $this->get('/login')->assertOk()->assertDontSee(self::SNIPPET, false);
    }

    // ----- Ausgabe: Zielgruppe ------------------------------------------------------------

    public function test_audience_by_account_type(): void
    {
        config(['custom_scripts.enabled' => true]);
        $user = User::factory()->create();
        $student = User::factory()->student()->create();

        $this->script(['name' => 'Konten', 'snippet' => '<script>/*konten*/</script>']);
        $this->script(['name' => 'Gäste', 'snippet' => '<script>/*gaeste*/</script>', 'for_users' => false, 'for_guests' => true]);
        $this->script(['name' => 'Schüler', 'snippet' => '<script>/*schueler*/</script>', 'for_users' => false, 'for_students' => true]);

        $guest = $this->renderer()->render('body');
        $this->assertStringContainsString('gaeste', $guest);
        $this->assertStringNotContainsString('konten', $guest);
        $this->assertStringNotContainsString('schueler', $guest);

        $this->actingAs($user);
        $forUser = $this->renderer()->render('body');
        $this->assertStringContainsString('konten', $forUser);
        $this->assertStringNotContainsString('gaeste', $forUser);
        $this->assertStringNotContainsString('schueler', $forUser);

        $this->actingAs($student);
        $forStudent = $this->renderer()->render('body');
        $this->assertStringContainsString('schueler', $forStudent);
        $this->assertStringNotContainsString('konten', $forStudent);
        $this->assertStringNotContainsString('gaeste', $forStudent);
    }

    public function test_role_restriction_applies_only_to_logged_in_accounts(): void
    {
        config(['custom_scripts.enabled' => true]);
        $hort = $this->role('Hort');
        $eltern = $this->role('Eltern');

        $staff = User::factory()->create();
        $staff->assignRole($hort);
        $parent = User::factory()->create();
        $parent->assignRole($eltern);
        $student = User::factory()->student()->create();

        $this->script(['restrict_roles' => true, 'for_guests' => true, 'for_students' => true], [$hort]);

        $this->actingAs($staff);
        $this->assertStringContainsString(self::SNIPPET, $this->renderer()->render('body'));

        $this->actingAs($parent);
        $this->assertSame('', $this->renderer()->render('body'));

        // Gäste und Schülerkonten: nur die Kontoart zählt
        $this->actingAs($student);
        $this->assertStringContainsString(self::SNIPPET, $this->renderer()->render('body'));
        auth()->logout();
        $this->assertStringContainsString(self::SNIPPET, $this->renderer()->render('body'));
    }

    public function test_role_restriction_without_remaining_roles_matches_nobody(): void
    {
        config(['custom_scripts.enabled' => true]);
        $role = $this->role('Hort');
        $user = User::factory()->create();
        $user->assignRole($role);

        // z. B. Rolle direkt in der Datenbank gelöscht (ohne Ereignis)
        $this->script(['restrict_roles' => true]);

        $this->actingAs($user);
        $this->assertSame('', $this->renderer()->render('body'));
    }

    // ----- Rolle gelöscht -----------------------------------------------------------------

    public function test_deleting_last_role_deactivates_script(): void
    {
        $hort = $this->role('Hort');
        $eltern = $this->role('Eltern');

        $onlyHort = $this->script(['name' => 'Nur Hort', 'restrict_roles' => true], [$hort]);
        $both = $this->script(['name' => 'Beide', 'restrict_roles' => true], [$hort, $eltern]);
        $unrestricted = $this->script(['name' => 'Alle']);

        $hort->delete();

        $onlyHort->refresh();
        $this->assertFalse($onlyHort->active);
        $this->assertStringContainsString('Rolle „Hort“ wurde gelöscht', $onlyHort->disabled_reason);
        $this->assertCount(0, $onlyHort->roles);

        $both->refresh();
        $this->assertTrue($both->active);
        $this->assertNull($both->disabled_reason);
        $this->assertSame([$eltern->id], $both->roles->pluck('id')->all());

        $this->assertTrue($unrestricted->fresh()->active);
    }

    public function test_reactivating_restricted_script_without_roles_is_refused(): void
    {
        config(['custom_scripts.enabled' => true]);
        $admin = $this->admin();
        $script = $this->script(['active' => false, 'restrict_roles' => true, 'disabled_reason' => 'weg']);

        $this->actingAs($admin)->patch("/settings/custom-scripts/{$script->id}/toggle")
            ->assertRedirect()->assertSessionHas('type', 'danger');
        $this->assertFalse($script->fresh()->active);

        $this->actingAs($admin)->put("/settings/custom-scripts/{$script->id}", $this->payload(['restrict_roles' => '1']))
            ->assertSessionHasErrors('roles');
        $this->assertFalse($script->fresh()->active);

        // Ohne Begrenzung lässt es sich wieder aktivieren
        $script->update(['restrict_roles' => false]);
        $this->actingAs($admin)->patch("/settings/custom-scripts/{$script->id}/toggle")->assertSessionHas('type', 'success');
        $this->assertTrue($script->fresh()->active);
        $this->assertNull($script->fresh()->disabled_reason);
    }

    // ----- Chat-Seite und App -------------------------------------------------------------

    public function test_chat_page_renders_chat_scripts_for_matching_audience(): void
    {
        $user = User::factory()->create(['changePassword' => false]);
        $this->script(['snippet' => '<script>/*chat*/</script>', 'use_on_chat' => true, 'position' => 'head',
            'page_mode' => 'only', 'page_patterns' => 'care/*']);
        $this->script(['snippet' => '<script>/*kein-chat*/</script>']);

        // Nicht freigeschaltet
        $this->actingAs($user)->get('/chat')->assertNotFound();

        // Freigeschaltet: Seitenregel spielt keine Rolle, nur Chat-Skripte
        config(['custom_scripts.enabled' => true]);
        $this->app->forgetScopedInstances();
        $html = $this->actingAs($user)->get('/chat')->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, '/*chat*/'));
        $this->assertStringNotContainsString('kein-chat', $html);

        // Zielgruppe passt nicht → 404
        CustomScript::query()->update(['restrict_roles' => true]);
        $this->app->forgetScopedInstances();
        $this->actingAs($user)->get('/chat')->assertNotFound();
    }

    public function test_chat_page_requires_login(): void
    {
        config(['custom_scripts.enabled' => true]);
        $this->script(['use_on_chat' => true, 'for_guests' => true]);

        $this->get('/chat')->assertRedirect();
    }

    public function test_bootstrap_offers_chat_only_when_a_chat_script_applies(): void
    {
        $user = User::factory()->create(['changePassword' => false]);
        Sanctum::actingAs($user);

        $setting = app(CustomScriptSetting::class);
        $setting->app_enabled = true;
        $setting->app_label = 'Hilfe-Chat';
        $setting->save();

        $chat = $this->script(['use_on_chat' => true]);

        // Nicht freigeschaltet
        $this->getJson('/api/v1/bootstrap')->assertOk()->assertJsonPath('data.chat', null);

        config(['custom_scripts.enabled' => true]);
        $this->app->forgetScopedInstances();
        $this->getJson('/api/v1/bootstrap')->assertOk()
            ->assertJsonPath('data.chat.label', 'Hilfe-Chat')
            ->assertJsonPath('data.chat.web_path', '/chat');

        // Kein Chat-Skript für diesen Nutzer (Rolle fehlt)
        $chat->update(['restrict_roles' => true]);
        $chat->roles()->sync([$this->role('Hort')->id]);
        $this->app->forgetScopedInstances();
        $this->getJson('/api/v1/bootstrap')->assertOk()->assertJsonPath('data.chat', null);

        // App-Button ausgeschaltet
        $chat->update(['restrict_roles' => false]);
        $setting->app_enabled = false;
        $setting->save();
        $this->app->forgetScopedInstances();
        $this->getJson('/api/v1/bootstrap')->assertOk()->assertJsonPath('data.chat', null);
    }

    public function test_app_chat_path_is_accepted_by_web_link(): void
    {
        Sanctum::actingAs(User::factory()->create(['changePassword' => false]));

        $this->postJson('/api/v1/auth/web-link', ['path' => CustomScriptRenderer::CHAT_PATH])
            ->assertOk()->assertJsonStructure(['data' => ['url']]);
    }

    public function test_app_settings_are_saved_and_validated(): void
    {
        config(['custom_scripts.enabled' => true]);
        $admin = $this->admin();

        $this->actingAs($admin)->put('/settings/custom-scripts/app', ['app_enabled' => '1', 'app_label' => ''])
            ->assertSessionHasNoErrors()->assertRedirect(route('settings.custom-scripts.index'));

        $this->app->forgetScopedInstances();
        $setting = app(CustomScriptSetting::class);
        $this->assertTrue($setting->app_enabled);
        $this->assertSame('Chatbot', $setting->app_label);

        $this->actingAs($admin)->put('/settings/custom-scripts/app', ['app_enabled' => '1', 'app_label' => str_repeat('x', 31)])
            ->assertSessionHasErrors('app_label');
    }

    // ----- Migration ----------------------------------------------------------------------

    public function test_migration_takes_over_legacy_snippet(): void
    {
        Schema::drop('custom_script_role');
        Schema::drop('custom_scripts');

        $legacy = [
            'active' => true, 'snippet' => '  '.self::SNIPPET.'  ', 'position' => 'head',
            'show_for_guests' => true, 'updated_by' => null, 'updated_at' => null,
        ];
        foreach ($legacy as $name => $value) {
            DB::table('settings')->updateOrInsert(
                ['group' => 'custom_scripts', 'name' => $name],
                ['locked' => false, 'payload' => json_encode($value)]
            );
        }

        $migration = require database_path('migrations/2026_11_23_000001_create_custom_scripts_table.php');
        $migration->up();

        $script = CustomScript::sole();
        $this->assertSame('Übernommenes Script', $script->name);
        $this->assertSame(self::SNIPPET, $script->snippet);
        $this->assertTrue($script->active);
        $this->assertSame('head', $script->position);
        $this->assertSame(CustomScript::PAGE_ALL, $script->page_mode);
        $this->assertTrue($script->for_guests);
        $this->assertTrue($script->for_users);
        $this->assertFalse($script->for_students);
        $this->assertFalse($script->restrict_roles);
        $this->assertTrue($script->use_on_chat);

        $this->assertEqualsCanonicalizing(
            ['app_enabled', 'app_label'],
            DB::table('settings')->where('group', 'custom_scripts')->pluck('name')->all()
        );
    }

    public function test_migration_without_legacy_snippet_creates_nothing(): void
    {
        $this->assertSame(0, CustomScript::count());
        $this->assertEqualsCanonicalizing(
            ['app_enabled', 'app_label'],
            DB::table('settings')->where('group', 'custom_scripts')->pluck('name')->all()
        );
    }
}
