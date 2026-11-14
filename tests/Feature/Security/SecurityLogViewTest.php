<?php

namespace Tests\Feature\Security;

use App\Model\SecurityEvent;
use App\Model\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SecurityLogViewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function user(bool $withPermission): User
    {
        Permission::findOrCreate('see security log', 'web');
        $user = User::factory()->create(['password_changed_at' => now(), 'changePassword' => false]);
        if ($withPermission) {
            $user->givePermissionTo('see security log');
        }

        return $user;
    }

    private function event(string $event, array $attrs = []): SecurityEvent
    {
        return SecurityEvent::create($attrs + [
            'event' => $event,
            'created_at' => now(),
        ]);
    }

    public function test_ohne_recht_403_und_gast_wird_umgeleitet(): void
    {
        $this->actingAs($this->user(false))->get('/security-log')->assertForbidden();
        auth()->logout();
        $this->get('/security-log')->assertRedirect();
    }

    public function test_mit_recht_erreichbar_und_einsicht_wird_hoechstens_stuendlich_protokolliert(): void
    {
        Cache::flush();
        $user = $this->user(true);

        $this->actingAs($user)->get('/security-log')->assertOk()->assertSee('Zweckbindung');
        $this->actingAs($user)->get('/security-log')->assertOk();

        $this->assertSame(1, SecurityEvent::where('event', 'security_log.viewed')->where('user_id', $user->id)->count());
    }

    public function test_filter_funktionieren(): void
    {
        $user = $this->user(true);
        $a = User::factory()->create(['name' => 'Anna Beispiel']);
        $b = User::factory()->create(['name' => 'Bernd Beispiel']);

        $this->event('auth.login', ['user_id' => $a->id, 'ip_address' => '10.1.2.3', 'created_at' => now()->subDays(10)]);
        $this->event('auth.failed', ['user_id' => $b->id, 'ip_address' => '192.168.0.9']);
        $this->event('export.download', ['user_id' => $b->id, 'ip_address' => '192.168.0.9']);

        $ids = fn ($response) => $response->viewData('events')->pluck('event')->sort()->values()->all();

        $r = $this->actingAs($user)->get('/security-log?event=auth');
        $this->assertSame(['auth.failed', 'auth.login'], $ids($r));

        $r = $this->actingAs($user)->get('/security-log?event=auth.login');
        $this->assertSame(['auth.login'], $ids($r));

        $r = $this->actingAs($user)->get('/security-log?user_id='.$b->id);
        $this->assertSame(['auth.failed', 'export.download'], $ids($r));

        $r = $this->actingAs($user)->get('/security-log?ip=10.1');
        $this->assertSame(['auth.login'], $ids($r));

        $r = $this->actingAs($user)->get('/security-log?from='.now()->subDays(2)->toDateString().'&event=auth');
        $this->assertSame(['auth.failed'], $ids($r));

        $r = $this->actingAs($user)->get('/security-log?to='.now()->subDays(5)->toDateString().'&event=auth');
        $this->assertSame(['auth.login'], $ids($r));
    }

    public function test_geloeschtes_konto_zeigt_nur_die_id_und_kontext_wird_escaped(): void
    {
        $user = $this->user(true);
        $this->event('auth.failed', [
            'user_id' => 987654,
            'actor_id' => 987655,
            'context' => ['grund' => '<script>alert(1)</script>'],
            'user_agent' => '<b>UA</b>',
        ]);

        $this->actingAs($user)->get('/security-log')
            ->assertOk()
            ->assertSee('gelöschtes Konto')
            ->assertSee('ID 987654')
            ->assertSee('ID 987655')
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<b>UA</b>', false);
    }

    public function test_unbekannte_ereignisse_werden_roh_angezeigt_und_bekannte_uebersetzt(): void
    {
        $user = $this->user(true);
        $this->event('foo.bar');
        $this->event('auth.lockout');

        $this->actingAs($user)->get('/security-log')
            ->assertOk()
            ->assertSee('foo.bar')
            ->assertSee('Anmeldung gesperrt');
    }

    public function test_keine_lösch_oder_exportroute(): void
    {
        foreach (Route::getRoutes() as $route) {
            if (! str_contains($route->uri(), 'security-log')) {
                continue;
            }
            $this->assertSame(['GET', 'HEAD'], $route->methods(), $route->uri());
            $this->assertStringNotContainsString('export', $route->uri());
        }

        $user = $this->user(true);
        $this->actingAs($user)->delete('/security-log')->assertStatus(405);
        $this->actingAs($user)->post('/security-log')->assertStatus(405);
    }

    public function test_menueeintrag_nur_fuer_berechtigte(): void
    {
        $this->artisan('migrate');
        $this->assertDatabaseHas('settings_modules', ['setting' => 'Sicherheitsprotokoll']);

        $this->actingAs($this->user(true))->get('/security-log')->assertSee('href="'.url('security-log').'"', false);
        $this->actingAs($this->user(false))->get('/')->assertDontSee('href="'.url('security-log').'"', false);
    }
}
