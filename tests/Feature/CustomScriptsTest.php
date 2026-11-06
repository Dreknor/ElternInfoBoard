<?php

namespace Tests\Feature;

use App\Model\User;
use App\Services\CustomScripts\CustomScriptRenderer;
use App\Settings\CustomScriptSetting;
use Spatie\Permission\Models\Permission;
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

    private function storeSnippet(array $overrides = []): void
    {
        $setting = app(CustomScriptSetting::class);
        foreach (array_merge([
            'active' => true,
            'snippet' => self::SNIPPET,
            'position' => 'body',
            'show_for_guests' => false,
        ], $overrides) as $key => $value) {
            $setting->{$key} = $value;
        }
        $setting->save();
        $this->app->forgetScopedInstances();
    }

    private function renderer(): CustomScriptRenderer
    {
        return app(CustomScriptRenderer::class);
    }

    public function test_settings_page_is_hidden_when_instance_not_unlocked(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/settings/custom-scripts')->assertNotFound();
        $this->actingAs($admin)->put('/settings/custom-scripts', [
            'active' => 1, 'snippet' => self::SNIPPET, 'position' => 'body',
        ])->assertNotFound();

        $this->app->forgetScopedInstances();
        $this->assertSame('', app(CustomScriptSetting::class)->snippet);
    }

    public function test_nothing_is_rendered_when_instance_not_unlocked(): void
    {
        $this->storeSnippet();
        $this->actingAs(User::factory()->create());

        $this->assertSame('', $this->renderer()->render('body'));
    }

    public function test_users_without_permission_cannot_edit(): void
    {
        config(['custom_scripts.enabled' => true]);
        $user = User::factory()->create(['changePassword' => false]);

        $this->actingAs($user)->get('/settings/custom-scripts')->assertForbidden();
        $this->actingAs($user)->put('/settings/custom-scripts', [
            'active' => 1, 'snippet' => self::SNIPPET, 'position' => 'body',
        ])->assertForbidden();
    }

    public function test_admin_can_save_snippet(): void
    {
        config(['custom_scripts.enabled' => true]);
        $admin = $this->admin();

        $this->actingAs($admin)->get('/settings/custom-scripts')->assertOk()->assertSee('Eigene Skripte');

        $this->actingAs($admin)->put('/settings/custom-scripts', [
            'active' => '1',
            'snippet' => self::SNIPPET,
            'position' => 'head',
            'show_for_guests' => '0',
        ])->assertRedirect(route('settings.custom-scripts.edit'))->assertSessionHas('type', 'success');

        $this->app->forgetScopedInstances();
        $setting = app(CustomScriptSetting::class);
        $this->assertTrue($setting->active);
        $this->assertSame(self::SNIPPET, $setting->snippet);
        $this->assertSame('head', $setting->position);
        $this->assertFalse($setting->show_for_guests);
        $this->assertSame($admin->id, $setting->updated_by);
        $this->assertNotNull($setting->updated_at);

        // Im App-Layout für angemeldete Nutzer ausgegeben (hier im <head>)
        $html = $this->actingAs($admin)->get('/settings/custom-scripts')->assertOk()->getContent();
        $this->assertStringContainsString(self::SNIPPET, $html);
        $this->assertLessThan(strpos($html, '</head>'), strpos($html, self::SNIPPET));
    }

    public function test_validation_rejects_bad_input(): void
    {
        config(['custom_scripts.enabled' => true, 'custom_scripts.max_length' => 100]);
        $admin = $this->admin();

        $this->actingAs($admin)->put('/settings/custom-scripts', [
            'active' => '1', 'snippet' => '</body><script>x()</script>', 'position' => 'body',
        ])->assertSessionHasErrors('snippet');

        $this->actingAs($admin)->put('/settings/custom-scripts', [
            'active' => '1', 'snippet' => str_repeat('a', 101), 'position' => 'body',
        ])->assertSessionHasErrors('snippet');

        $this->actingAs($admin)->put('/settings/custom-scripts', [
            'active' => '1', 'snippet' => '', 'position' => 'body',
        ])->assertSessionHasErrors('snippet');

        $this->actingAs($admin)->put('/settings/custom-scripts', [
            'active' => '1', 'snippet' => self::SNIPPET, 'position' => 'footer',
        ])->assertSessionHasErrors('position');

        $this->app->forgetScopedInstances();
        $this->assertSame('', app(CustomScriptSetting::class)->snippet);
    }

    public function test_renders_only_at_configured_position_for_logged_in_users(): void
    {
        config(['custom_scripts.enabled' => true]);
        $this->storeSnippet(['position' => 'body']);

        // Gast: nichts
        $this->assertSame('', $this->renderer()->render('body'));

        $this->actingAs(User::factory()->create());
        $this->assertStringContainsString(self::SNIPPET, $this->renderer()->render('body'));
        $this->assertSame('', $this->renderer()->render('head'));
    }

    public function test_inactive_snippet_is_not_rendered(): void
    {
        config(['custom_scripts.enabled' => true]);
        $this->storeSnippet(['active' => false]);
        $this->actingAs(User::factory()->create());

        $this->assertSame('', $this->renderer()->render('body'));
    }

    public function test_guest_pages_only_when_allowed(): void
    {
        config(['custom_scripts.enabled' => true]);

        $this->storeSnippet(['show_for_guests' => false]);
        $this->get('/login')->assertOk()->assertDontSee('chatbot.example', false);

        $this->storeSnippet(['show_for_guests' => true]);
        $this->get('/login')->assertOk()->assertSee(self::SNIPPET, false);
    }

    public function test_login_page_has_no_trace_when_instance_not_unlocked(): void
    {
        $this->storeSnippet(['show_for_guests' => true]);

        $this->get('/login')->assertOk()
            ->assertDontSee('chatbot.example', false)
            ->assertDontSee('custom-script', false);
    }

    public function test_settings_index_links_page_only_when_unlocked(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/settings')->assertOk()->assertDontSee('Eigene Skripte');

        config(['custom_scripts.enabled' => true]);
        $this->actingAs($admin)->get('/settings')->assertOk()->assertSee('Eigene Skripte');
    }
}
