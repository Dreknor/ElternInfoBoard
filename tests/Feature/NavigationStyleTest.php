<?php

namespace Tests\Feature;

use App\Model\Module;
use App\Model\User;
use App\Model\UserAppSettings;
use App\Services\NavigationService;
use App\Settings\GeneralSetting;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class NavigationStyleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->forgetScopedInstances();
        $this->app->forgetInstance(NavigationService::class);
        Cache::forget('modules');
    }

    private function configure(string $style = 'classic', bool $allowUser = true): void
    {
        $g = app(GeneralSetting::class);
        $g->nav_style = $style;
        $g->allow_user_nav_style = $allowUser;
        $g->save();
        $this->app->forgetScopedInstances();
        $this->app->forgetInstance(NavigationService::class);
    }

    private function makeUser(): User
    {
        return User::factory()->create(['changePassword' => false]);
    }

    private function module(string $name, array $options): Module
    {
        return Module::create([
            'setting' => $name,
            'category' => 'module',
            'description' => $name,
            'options' => array_merge(['active' => '1', 'rights' => []], $options),
        ]);
    }

    public function test_admin_can_set_nav_style(): void
    {
        Permission::firstOrCreate(['name' => 'edit settings', 'guard_name' => 'web']);
        $admin = $this->makeUser();
        $admin->givePermissionTo('edit settings');

        $this->actingAs($admin)->put('/settings/design', [
            'default_theme' => 'default',
            'nav_style' => 'compact',
            // allow_user_nav_style nicht gesendet → false
        ])->assertRedirect()->assertSessionHas('type', 'success');

        $this->app->forgetScopedInstances();
        $settings = app(GeneralSetting::class);
        $this->assertSame('compact', $settings->nav_style);
        $this->assertFalse($settings->allow_user_nav_style);
    }

    public function test_admin_cannot_set_unknown_nav_style(): void
    {
        Permission::firstOrCreate(['name' => 'edit settings', 'guard_name' => 'web']);
        $admin = $this->makeUser();
        $admin->givePermissionTo('edit settings');

        $this->actingAs($admin)->put('/settings/design', [
            'default_theme' => 'default',
            'nav_style' => 'mega',
        ])->assertSessionHasErrors('nav_style');
    }

    public function test_user_can_choose_and_reset_nav_style(): void
    {
        $this->configure('classic', true);
        $user = $this->makeUser();
        UserAppSettings::create(['user_id' => $user->id, 'settings' => ['theme' => 'dark']]);

        $this->actingAs($user)->put('/einstellungen/navigation', ['nav_style' => 'compact'])->assertRedirect();
        $settings = UserAppSettings::where('user_id', $user->id)->first();
        $this->assertSame('compact', data_get($settings->settings, 'nav_style'));
        $this->assertSame('dark', data_get($settings->settings, 'theme'));
        $this->assertSame('compact', app(NavigationService::class)->resolveStyle($user));

        $this->actingAs($user)->put('/einstellungen/navigation', ['nav_style' => ''])->assertRedirect();
        $this->assertArrayNotHasKey('nav_style', $settings->fresh()->settings);
        $this->assertSame('classic', app(NavigationService::class)->resolveStyle($user));
    }

    public function test_user_choice_is_ignored_when_disallowed(): void
    {
        $this->configure('classic', false);
        $user = $this->makeUser();
        UserAppSettings::create(['user_id' => $user->id, 'settings' => ['nav_style' => 'compact']]);

        $this->assertSame('classic', app(NavigationService::class)->resolveStyle($user));

        $response = $this->actingAs($user)->put('/einstellungen/navigation', ['nav_style' => 'compact']);
        $this->assertContains($response->status(), [302, 403]);
        $this->assertNotEquals(200, $response->status());
    }

    public function test_invalid_user_nav_style_is_rejected(): void
    {
        $this->configure('classic', true);

        $this->actingAs($this->makeUser())
            ->put('/einstellungen/navigation', ['nav_style' => 'mega'])
            ->assertSessionHasErrors('nav_style');
    }

    public function test_build_groups_items_and_marks_most_specific_active(): void
    {
        $user = $this->makeUser();
        Permission::firstOrCreate(['name' => 'create termine', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'secret', 'guard_name' => 'web']);
        $user->givePermissionTo('create termine');

        $modules = collect([
            $this->module('Termine', [
                'nav' => ['name' => 'Termine', 'link' => 'termine', 'icon' => 'far fa-calendar-alt'],
                'adm-nav' => ['name' => 'neuer Termin', 'link' => 'termine/create', 'icon' => 'fa fa-plus', 'adm-rights' => ['create termine']],
            ]),
            $this->module('Stundenplan', ['nav' => ['name' => 'Stundenplan', 'link' => 'stundenplan', 'icon' => 'fas fa-table']]),
            $this->module('Vertretungsplan', ['nav' => ['name' => 'Vertretungsplan', 'link' => 'vertretungsplan', 'icon' => 'fas fa-columns']]),
            $this->module('Elternrat', ['nav' => ['name' => 'Elternrat', 'link' => 'elternrat', 'icon' => 'fas fa-users']]),
            $this->module('Unbekannt', ['nav' => ['name' => 'Extern', 'link' => 'https://example.org', 'icon' => 'fas fa-link']]),
            $this->module('Geheim', ['rights' => ['secret'], 'nav' => ['name' => 'Geheim', 'link' => 'geheim', 'icon' => 'fas fa-lock']]),
        ]);

        $nav = app(NavigationService::class)->build($user, $modules, collect(), 'termine/create');

        // 'Termine' ist laut config 'top', Einzel-Gruppen (Elternrat, Weitere) werden aufgelöst
        $topNames = array_column($nav['top'], 'name');
        $this->assertContains('Termine', $topNames);
        $this->assertContains('Elternrat', $topNames);
        $this->assertContains('Extern', $topNames);
        $this->assertNotContains('Geheim', $topNames);

        $this->assertCount(1, $nav['groups']);
        $this->assertSame('planung', $nav['groups'][0]['key']);
        $this->assertSame(['Stundenplan', 'Vertretungsplan'], array_column($nav['groups'][0]['items'], 'name'));

        // Nur der spezifischste Link ist aktiv
        $termine = collect($nav['top'])->firstWhere('name', 'Termine');
        $this->assertFalse($termine['active']);
        $this->assertSame('neuer Termin', $nav['admin_top'][0]['name']);
        $this->assertTrue($nav['admin_top'][0]['active']);

        $extern = collect($nav['top'])->firstWhere('name', 'Extern');
        $this->assertTrue($extern['external']);
    }

    public function test_module_names_with_dots_are_mapped_to_their_group(): void
    {
        $user = $this->makeUser();
        Permission::firstOrCreate(['name' => 'manage diseases', 'guard_name' => 'web']);
        $user->givePermissionTo('manage diseases');

        $modules = collect([
            $this->module('meldepfl. Erkrankungen', [
                'adm-nav' => ['name' => 'neue Erkrankung', 'link' => 'diseases/create', 'icon' => 'fas fa-virus', 'adm-rights' => ['manage diseases']],
            ]),
            $this->module('Krankmeldung', [
                'adm-nav' => ['name' => 'Krankmeldung Express', 'link' => 'krankmeldung/express', 'icon' => 'fas fa-medkit', 'adm-rights' => ['manage diseases']],
            ]),
        ]);

        $nav = app(NavigationService::class)->build($user, $modules, collect(), 'dashboard');

        $this->assertSame([], $nav['admin_top']);
        $this->assertSame('adm-inhalte', $nav['admin'][0]['key']);
        $this->assertSame(['neue Erkrankung', 'Krankmeldung Express'], array_column($nav['admin'][0]['items'], 'name'));
    }

    public function test_compact_sidebar_is_rendered_for_user_choice(): void
    {
        $this->configure('classic', true);
        $user = $this->makeUser();
        $this->module('Stundenplan', ['nav' => ['name' => 'Stundenplan', 'link' => 'stundenplan', 'icon' => 'fas fa-table']]);
        $this->module('Vertretungsplan', ['nav' => ['name' => 'Vertretungsplan', 'link' => 'vertretungsplan', 'icon' => 'fas fa-columns']]);

        $this->actingAs($user)->get('/einstellungen')
            ->assertOk()
            ->assertDontSee('sidebar-compact-filter', false);

        UserAppSettings::create(['user_id' => $user->id, 'settings' => ['nav_style' => 'compact']]);
        $this->app->forgetInstance(NavigationService::class);

        $this->actingAs($user)->get('/einstellungen')
            ->assertOk()
            ->assertSee('sidebar-compact-filter', false)
            ->assertSee('Termine &amp; Pläne', false)
            ->assertSee('Vertretungsplan');
    }
}
