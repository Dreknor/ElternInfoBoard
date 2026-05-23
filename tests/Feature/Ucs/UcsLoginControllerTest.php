<?php

namespace Tests\Feature\Ucs;

use App\Model\User;
use App\Services\Ucs\UcsSyncService;
use App\Settings\KeyCloakSetting;
use App\Settings\UcsSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Socialite\Facades\Socialite;
use Tests\TestCase;

/**
 * Feature-Tests für App\Http\Controllers\Auth\UcsLoginController
 *
 * Deckt alle 10 Gelingenskriterien aus docs/todos/06-oidc-login.md ab.
 *
 * Socialite wird per Mockery abgefangen, damit kein echter OIDC-Flow stattfindet.
 * UcsSyncService wird für JIT-Tests als Mock injiziert.
 */
class UcsLoginControllerTest extends TestCase
{
    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Minimal-Stub für UcsSetting – kann per $overrides überschrieben werden.
     */
    private function makeUcsSetting(array $overrides = []): UcsSetting
    {
        $stub = $this->createStub(UcsSetting::class);
        foreach (array_merge([
            'enabled'            => true,
            'on_login_fallback'  => true,
            'on_login_timeout'   => 5,
        ], $overrides) as $k => $v) {
            $stub->{$k} = $v;
        }
        return $stub;
    }

    /**
     * Minimal-Stub für KeyCloakSetting.
     */
    private function makeKcSetting(array $overrides = []): KeyCloakSetting
    {
        $stub = $this->createStub(KeyCloakSetting::class);
        foreach (array_merge([
            'enabled'    => true,
            'base_url'   => 'https://auth.example.de',
            'realm'      => 'ucs',
            'client_id'  => 'elterninfo',
        ], $overrides) as $k => $v) {
            $stub->{$k} = $v;
        }
        return $stub;
    }

    /**
     * Erstellt ein einfaches OIDC-User-Objekt, das die Controller-Zugriffsmuster
     * getId(), ->user['preferred_username'] und ->accessTokenResponseBody['id_token']
     * implementiert.
     */
    private function fakeOidcUser(
        string  $sub,
        ?string $preferredUsername = null,
        string  $idToken = 'test-id-token',
        ?string $email = null,
    ): object {
        return new class ($sub, $preferredUsername, $idToken, $email) {
            public array $user;
            public array $accessTokenResponseBody;

            public function __construct(
                private readonly string  $sub,
                ?string $pref,
                string  $idToken,
                ?string $email,
            ) {
                $this->user = array_filter([
                    'preferred_username' => $pref,
                    'email'              => $email,
                ], fn ($v) => $v !== null);
                $this->accessTokenResponseBody = ['id_token' => $idToken];
            }

            public function getId(): string { return $this->sub; }
        };
    }

    /**
     * Mockt Socialite so, dass driver('ucs')->user() den $oidcUser zurückgibt.
     */
    private function mockSocialiteWithUser(object $oidcUser): void
    {
        $provider = \Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
        $provider->shouldReceive('user')->andReturn($oidcUser);
        Socialite::shouldReceive('driver')->with('ucs')->andReturn($provider);
    }

    /**
     * Bindet Settings-Stubs in den Container.
     */
    private function bindSettings(
        ?UcsSetting      $ucs = null,
        ?KeyCloakSetting $kc  = null,
    ): void {
        $this->app->instance(UcsSetting::class,      $ucs  ?? $this->makeUcsSetting());
        $this->app->instance(KeyCloakSetting::class, $kc   ?? $this->makeKcSetting());
    }

    // =========================================================================
    // Kriterium 1: Primary-Match via ucs_uuid (kein API-Call)
    // =========================================================================

    /**
     * @test
     * Gelingenskriterium 1: User mit ucs_uuid='abc', OIDC liefert sub='abc'
     * → korrekter User eingeloggt, kein UcsSyncService-Aufruf.
     */
    public function test_primary_uuid_match_logt_user_ein_ohne_api_call(): void
    {
        $user = User::factory()->create([
            'ucs_uuid'     => 'abc-uuid',
            'ucs_username' => 'elternteil1',
            'is_active'    => true,
        ]);

        $this->bindSettings();
        $this->mockSocialiteWithUser($this->fakeOidcUser('abc-uuid', 'elternteil1'));

        // UcsSyncService darf NICHT aufgerufen werden
        $svcMock = \Mockery::mock(UcsSyncService::class);
        $svcMock->shouldNotReceive('syncSingleParent');
        $this->app->instance(UcsSyncService::class, $svcMock);

        $response = $this->get('/auth/ucs/callback');

        $response->assertRedirect('/home');
        $this->assertAuthenticatedAs($user);
    }

    // =========================================================================
    // Kriterium 2: Secondary-Match via ucs_username + uuid-Backfill
    // =========================================================================

    /**
     * @test
     * Gelingenskriterium 2: User existiert nur mit ucs_username='foo',
     * OIDC liefert sub='abc', preferred_username='foo'
     * → User wird gefunden, ucs_uuid='abc' wird gesetzt.
     */
    public function test_secondary_username_match_setzt_uuid_backfill(): void
    {
        $user = User::factory()->create([
            'ucs_uuid'     => null,
            'ucs_username' => 'foo',
            'is_active'    => true,
        ]);

        $this->bindSettings();
        $this->mockSocialiteWithUser($this->fakeOidcUser('abc-new-uuid', 'foo'));

        $response = $this->get('/auth/ucs/callback');

        $response->assertRedirect('/home');
        $this->assertAuthenticatedAs($user);

        // ucs_uuid muss jetzt gesetzt sein
        $this->assertDatabaseHas('users', [
            'id'       => $user->id,
            'ucs_uuid' => 'abc-new-uuid',
        ]);
    }

    // =========================================================================
    // Kriterium 3: JIT-Sync legt neuen User an
    // =========================================================================

    /**
     * @test
     * Gelingenskriterium 3: Unbekannter User, UcsSyncService (gemockt) liefert
     * frisch angelegten User → Login erfolgreich.
     */
    public function test_jit_sync_legt_neuen_user_an_und_loggt_ein(): void
    {
        // Kein User mit der gesuchten UUID/Username vorhanden → Primary/Secondary-Match schlagen fehl.
        // Der JIT-Mock gibt diesen User zurück, als hätte er ihn gerade provisioniert.
        $newUser = User::factory()->create([
            'ucs_uuid'     => null,         // noch nicht gesetzt
            'ucs_username' => null,         // noch nicht gesetzt
            'is_active'    => true,
        ]);

        $this->bindSettings();
        $this->mockSocialiteWithUser(
            $this->fakeOidcUser('brand-new-uuid', 'new_parent')
        );

        $svcMock = \Mockery::mock(UcsSyncService::class);
        $svcMock->shouldReceive('syncSingleParent')
                ->with('new_parent')
                ->once()
                ->andReturn($newUser);
        $this->app->instance(UcsSyncService::class, $svcMock);

        $response = $this->get('/auth/ucs/callback');

        $response->assertRedirect('/home');
        $this->assertAuthenticatedAs($newUser);
    }

    // =========================================================================
    // Kriterium 4: JIT wirft Exception → Redirect auf Pending, kein 500
    // =========================================================================

    /**
     * @test
     * Gelingenskriterium 4: Unbekannter User, UcsSyncService wirft Exception
     * → Redirect auf auth.ucs.pending, kein 500.
     */
    public function test_jit_exception_redirected_zu_pending_kein_500(): void
    {
        $this->bindSettings();
        $this->mockSocialiteWithUser(
            $this->fakeOidcUser('unknown-uuid', 'unbekannt')
        );

        $svcMock = \Mockery::mock(UcsSyncService::class);
        $svcMock->shouldReceive('syncSingleParent')
                ->andThrow(new \RuntimeException('Timeout'));
        $this->app->instance(UcsSyncService::class, $svcMock);

        $response = $this->get('/auth/ucs/callback');

        $response->assertRedirect(route('auth.ucs.pending'));
        $this->assertGuest();
    }

    // =========================================================================
    // Kriterium 5: Negativ-Cache verhindert zweiten JIT-Aufruf
    // =========================================================================

    /**
     * @test
     * Gelingenskriterium 5: Zweiter Login-Versuch mit demselben unbekannten
     * Username innerhalb 60 s ruft NICHT erneut syncSingleParent auf.
     */
    public function test_negativ_cache_verhindert_zweiten_jit_aufruf(): void
    {
        $this->bindSettings();
        $this->mockSocialiteWithUser(
            $this->fakeOidcUser('unknown-uuid', 'uncached_user')
        );

        // Cache-Eintrag für diesen Username setzen (simuliert ersten fehlgeschlagenen Versuch)
        $cacheKey = 'ucs.jit.miss:' . sha1(strtolower('uncached_user'));
        Cache::put($cacheKey, true, now()->addSeconds(60));

        $svcMock = \Mockery::mock(UcsSyncService::class);
        $svcMock->shouldNotReceive('syncSingleParent'); // darf NICHT aufgerufen werden
        $this->app->instance(UcsSyncService::class, $svcMock);

        $response = $this->get('/auth/ucs/callback');

        $response->assertRedirect(route('auth.ucs.pending'));
    }

    // =========================================================================
    // Kriterium 6: is_active=false blockt mit HTTP 403
    // =========================================================================

    /**
     * @test
     * Gelingenskriterium 6: Provisionierter aber deaktivierter User → HTTP 403.
     */
    public function test_deaktivierter_user_erhaelt_403(): void
    {
        $user = User::factory()->create([
            'ucs_uuid'  => 'deact-uuid',
            'is_active' => false,
        ]);

        $this->bindSettings();
        $this->mockSocialiteWithUser($this->fakeOidcUser('deact-uuid', 'deact_user'));

        $response = $this->get('/auth/ucs/callback');

        $response->assertStatus(403);
        $this->assertGuest();
    }

    // =========================================================================
    // Kriterium 7: Single-Logout-URL korrekt zusammengesetzt
    // =========================================================================

    /**
     * @test
     * Gelingenskriterium 7: Single-Logout-URL enthält id_token_hint
     * und post_logout_redirect_uri.
     */
    public function test_single_logout_url_enthaelt_id_token_hint_und_redirect_uri(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user);

        // id_token in Session ablegen (simuliert vorherigen OIDC-Login)
        $this->withSession(['ucs_id_token' => 'my-id-token-value']);

        $kc = $this->makeKcSetting([
            'base_url' => 'https://auth.schule.de',
            'realm'    => 'testschule',
        ]);
        $this->bindSettings(kc: $kc);

        $response = $this->post('/auth/ucs/logout');

        $response->assertRedirect();

        $location = $response->headers->get('Location');
        $this->assertStringContainsString('id_token_hint=my-id-token-value', urldecode($location ?? ''));
        $this->assertStringContainsString('post_logout_redirect_uri=', urldecode($location ?? ''));
        $this->assertStringContainsString('/realms/testschule/protocol/openid-connect/logout', urldecode($location ?? ''));
    }

    // =========================================================================
    // Kriterium 8: RateLimit – 31. Request → HTTP 429
    // =========================================================================

    /**
     * @test
     * Gelingenskriterium 8: 31. Request innerhalb 60 s gegen /auth/ucs/callback
     * von derselben IP → HTTP 429.
     */
    public function test_rate_limit_blockiert_31ten_request(): void
    {
        $this->bindSettings();

        // Socialite wirft Fehler (wird vom Controller gefangen → 302)
        Socialite::shouldReceive('driver->user')
                 ->andThrow(new \Exception('no code'));

        // Erste 30 Requests: sollen durchkommen (< Limit)
        for ($i = 0; $i < 30; $i++) {
            $this->get('/auth/ucs/callback');
        }

        // 31. Request muss 429 liefern
        $response = $this->get('/auth/ucs/callback');
        $response->assertStatus(429);
    }

    // =========================================================================
    // Kriterium 9: Feature-Flag aus → JIT wird übersprungen
    // =========================================================================

    /**
     * @test
     * Gelingenskriterium 9: on_login_fallback=false – unbekannter User landet
     * sofort auf Pending, JIT wird NICHT aufgerufen.
     */
    public function test_jit_wird_uebersprungen_wenn_feature_flag_aus(): void
    {
        $this->bindSettings(ucs: $this->makeUcsSetting(['on_login_fallback' => false]));
        $this->mockSocialiteWithUser(
            $this->fakeOidcUser('unk-uuid', 'unbekannt_flag_aus')
        );

        $svcMock = \Mockery::mock(UcsSyncService::class);
        $svcMock->shouldNotReceive('syncSingleParent');
        $this->app->instance(UcsSyncService::class, $svcMock);

        $response = $this->get('/auth/ucs/callback');

        $response->assertRedirect(route('auth.ucs.pending'));
    }

    // =========================================================================
    // Kriterium 10: E-Mail-Match passiert NIE (Regression)
    // =========================================================================

    /**
     * @test
     * Gelingenskriterium 10: OIDC-Payload enthält bekannte lokale E-Mail,
     * aber unbekannte UUID/Username → kein Login, Redirect auf Pending.
     */
    public function test_email_match_findet_niemals_statt(): void
    {
        // User existiert nur per E-Mail, KEINE ucs_uuid / ucs_username
        User::factory()->create([
            'email'        => 'bekannt@schule.de',
            'ucs_uuid'     => null,
            'ucs_username' => null,
            'is_active'    => true,
        ]);

        $this->bindSettings(ucs: $this->makeUcsSetting(['on_login_fallback' => false]));
        // OIDC-Payload enthält die bekannte E-Mail, aber UUID/Username sind unbekannt
        $this->mockSocialiteWithUser(
            $this->fakeOidcUser('no-such-uuid', 'no_such_user', email: 'bekannt@schule.de')
        );

        $response = $this->get('/auth/ucs/callback');

        $response->assertRedirect(route('auth.ucs.pending'));
        $this->assertGuest();
    }

    // =========================================================================
    // Zusatz: pending()-View gibt HTTP 200 zurück
    // =========================================================================

    /**
     * @test
     * Pending-Seite ist erreichbar und gibt HTTP 200 zurück.
     */
    public function test_pending_seite_gibt_200_zurueck(): void
    {
        $response = $this->get('/auth/ucs/pending');
        $response->assertStatus(200);
    }

    // =========================================================================
    // Zusatz: redirect() → 302 (oder 200 wenn disabled)
    // =========================================================================

    /**
     * @test
     * redirect() leitet zum IdP weiter (Socialite gemockt).
     */
    public function test_redirect_startet_oidc_flow(): void
    {
        $this->bindSettings();

        $provider = \Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
        $provider->shouldReceive('redirect')->andReturn(
            redirect()->away('https://auth.example.de/realms/ucs/protocol/openid-connect/auth?client_id=test')
        );
        Socialite::shouldReceive('driver')->with('ucs')->andReturn($provider);

        $response = $this->get('/auth/ucs/redirect');

        $response->assertRedirect();
        $this->assertStringContainsString('auth.example.de', $response->headers->get('Location') ?? '');
    }

    /**
     * @test
     * Wenn UCS nicht aktiviert ist, leitet redirect() zur Login-Seite weiter.
     */
    public function test_redirect_leitet_zu_login_wenn_ucs_deaktiviert(): void
    {
        $this->bindSettings(ucs: $this->makeUcsSetting(['enabled' => false]));

        $response = $this->get('/auth/ucs/redirect');

        $response->assertRedirect(route('login'));
    }
}

