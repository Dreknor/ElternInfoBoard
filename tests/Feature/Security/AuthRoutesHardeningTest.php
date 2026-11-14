<?php

namespace Tests\Feature\Security;

use App\Model\User;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Route;
use OwenIt\Auditing\Models\Audit;
use Tests\TestCase;

/**
 * Login-Drossel, Routenreihenfolge, Audit-Zuordnung bei Bearer-Tokens und Proxy-Konfiguration.
 */
class AuthRoutesHardeningTest extends TestCase
{
    protected function tearDown(): void
    {
        TrustProxies::flushState();
        parent::tearDown();
    }

    public function test_post_login_ist_gedrosselt(): void
    {
        $route = Route::getRoutes()->match(request()->create('/login', 'POST'));

        $this->assertContains('throttle:login', $route->gatherMiddleware());
        $this->assertSame('App\Http\Controllers\Auth\LoginController@login', $route->getActionName());
        $this->assertSame('/login', parse_url(route('login'), PHP_URL_PATH));
    }

    public function test_login_drossel_greift_nach_zehn_versuchen(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post('/login', ['email' => 'x@example.org', 'password' => 'falsch'])->assertStatus(302);
        }

        $this->post('/login', ['email' => 'x@example.org', 'password' => 'falsch'])->assertStatus(429);
    }

    public function test_logs_cleanup_ist_erreichbar(): void
    {
        $route = Route::getRoutes()->match(request()->create('/logs/cleanup', 'DELETE'));
        $this->assertSame('App\Http\Controllers\LogController@cleanup', $route->getActionName());

        $route = Route::getRoutes()->match(request()->create('/logs/5', 'DELETE'));
        $this->assertSame('App\Http\Controllers\LogController@destroy', $route->getActionName());
    }

    public function test_audit_erhaelt_user_id_bei_sanctum_bearer_request(): void
    {
        config(['audit.console' => true]);
        $user = User::factory()->create(['changePassword' => false]);
        $token = $user->createToken('App')->plainTextToken;

        $this->patchJson('/api/v1/me', ['phone' => '0351 123456'], ['Authorization' => 'Bearer '.$token])->assertOk();

        $audit = Audit::where('auditable_type', User::class)->where('auditable_id', $user->id)->where('event', 'updated')->latest('id')->firstOrFail();
        $this->assertSame($user->id, (int) $audit->user_id);
    }

    public function test_ohne_trusted_proxies_zaehlt_die_direkte_ip(): void
    {
        $this->assertSame([], config('trustedproxy.proxies'));

        $this->get('/login', ['X-Forwarded-For' => '203.0.113.9'])->assertOk();
        $this->assertSame('127.0.0.1', request()->ip());
    }

    public function test_trusted_proxies_liefert_echte_client_ip(): void
    {
        TrustProxies::at(['127.0.0.1']);

        $this->get('/login', ['X-Forwarded-For' => '203.0.113.9'])->assertOk();
        $this->assertSame('203.0.113.9', request()->ip());
    }
}
