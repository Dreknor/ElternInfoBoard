<?php

namespace Tests\Feature\Auth;

use App\Model\User;
use App\Services\Auth\SsoStudentDetector;
use App\Settings\KeycloakSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

/**
 * S-02: Schülerinnen und Schüler (Rolle im SSO-Token) werden nie als normale
 * Konten angemeldet oder angelegt (docs/konzept-schuelerkonten.md §6.1).
 */
class SsoStudentLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->keycloakSettings('schueler, Student', true);
        config([
            'services.keycloak.enabled' => true,
            'services.keycloak.mail_domain' => '*',
        ]);
    }

    private function keycloakSettings(?string $studentRoles, bool $autoCreate): void
    {
        (new KeycloakSetting)->fill([
            'enabled' => true,
            'client_id' => 'elterninfo',
            'client_secret' => 'secret',
            'realm' => 'schule',
            'redirect_uri' => null,
            'base_url' => 'https://auth.example.de',
            'maildomain' => '*',
            'student_roles' => $studentRoles,
            'auto_create_users' => $autoCreate,
        ])->save();
    }

    private static function jwt(array $claims): string
    {
        $part = fn (array $data) => rtrim(strtr(base64_encode(json_encode($data)), '+/', '-_'), '=');

        return $part(['alg' => 'none']).'.'.$part($claims).'.sig';
    }

    private function ssoUser(string $email, array $raw = [], ?string $accessToken = null): SocialiteUser
    {
        $user = (new SocialiteUser)
            ->setRaw(array_merge(['email' => $email, 'given_name' => 'Max', 'family_name' => 'Muster'], $raw))
            ->map(['id' => 'sub-'.$email, 'email' => $email, 'name' => 'Max Muster']);

        return $user->setToken($accessToken ?? self::jwt([]));
    }

    private function mockKeycloak(SocialiteUser $user): void
    {
        $provider = \Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('redirectUrl')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($user);
        Socialite::shouldReceive('driver')->with('keycloak')->andReturn($provider);
    }

    private function mockUcs(SocialiteUser $user): void
    {
        $provider = \Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
        $provider->shouldReceive('scopes')->andReturnSelf();
        $provider->shouldReceive('redirectUrl')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($user);
        Socialite::shouldReceive('driver')->with('ucs')->andReturn($provider);
    }

    /** @test */
    public function detector_reads_realm_and_client_roles_from_userinfo_and_tokens(): void
    {
        $detector = app(SsoStudentDetector::class);

        $this->assertTrue($detector->isStudent($this->ssoUser('a@schule.de', ['realm_access' => ['roles' => ['offline_access', 'Schueler']]])));
        $this->assertTrue($detector->isStudent($this->ssoUser('b@schule.de', [], self::jwt(['resource_access' => ['elterninfo' => ['roles' => ['student']]]]))));
        $this->assertFalse($detector->isStudent($this->ssoUser('c@schule.de', ['realm_access' => ['roles' => ['eltern']]])));
    }

    /** @test */
    public function detector_recognises_the_ucsschool_role_claim_without_configuration(): void
    {
        $this->keycloakSettings(null, true);
        $detector = app(SsoStudentDetector::class);

        $this->assertTrue($detector->isStudent($this->ssoUser('a@schule.de', ['ucsschoolRole' => ['student:school:ESZ']])));
        $this->assertTrue($detector->isStudent($this->ssoUser('b@schule.de', [], self::jwt(['ucsschoolRole' => 'student:school:ESZ']))));
        $this->assertFalse($detector->isStudent($this->ssoUser('c@schule.de', ['ucsschoolRole' => ['legal_guardian:school:ESZ']])));
        $this->assertFalse($detector->isStudent($this->ssoUser('d@schule.de', ['ucsschoolRole' => ['teacher:school:ESZ', 'staff:school:ESZ']])));
    }

    /** @test */
    public function ucs_login_rejects_students_by_ucsschool_role(): void
    {
        $ucs = $this->createStub(\App\Settings\UcsSetting::class);
        $ucs->enabled = true;
        $ucs->on_login_fallback = true;
        $ucs->on_login_timeout = 5;
        $this->app->instance(\App\Settings\UcsSetting::class, $ucs);
        $this->mockUcs($this->ssoUser('kind@schule.de', ['ucsschoolRole' => ['student:school:ESZ'], 'preferred_username' => 'max.m']));

        $response = $this->get(route('auth.ucs.callback'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('Meldung', SsoStudentDetector::MESSAGE);
        $this->assertGuest();
    }

    /** @test */
    public function detector_is_off_without_configured_roles(): void
    {
        $this->keycloakSettings(null, true);

        $user = $this->ssoUser('a@schule.de', ['realm_access' => ['roles' => ['schueler']]]);

        $this->assertFalse(app(SsoStudentDetector::class)->isStudent($user));
    }

    /** @test */
    public function web_login_rejects_students_and_creates_no_account(): void
    {
        $this->mockKeycloak($this->ssoUser('kind@schule.de', ['realm_access' => ['roles' => ['schueler']]]));

        $response = $this->get('login/keycloak/callback');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('Meldung', SsoStudentDetector::MESSAGE);
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'kind@schule.de']);
    }

    /** @test */
    public function web_login_does_not_log_students_into_an_existing_account(): void
    {
        User::factory()->create(['email' => 'kind@schule.de', 'changePassword' => false]);
        $this->mockKeycloak($this->ssoUser('kind@schule.de', [], self::jwt(['realm_access' => ['roles' => ['student']]])));

        $this->get('login/keycloak/callback')->assertRedirect(route('login'));

        $this->assertGuest();
    }

    /** @test */
    public function web_login_still_creates_accounts_for_non_students(): void
    {
        $this->mockKeycloak($this->ssoUser('eltern@schule.de', ['realm_access' => ['roles' => ['eltern']]]));

        $this->get('login/keycloak/callback')->assertRedirect('/home');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'eltern@schule.de']);
    }

    /** @test */
    public function web_login_creates_no_account_when_auto_create_is_off(): void
    {
        $this->keycloakSettings('schueler', false);
        $this->mockKeycloak($this->ssoUser('neu@schule.de'));

        $this->get('login/keycloak/callback')->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'neu@schule.de']);
    }

    /** @test */
    public function app_sso_rejects_students(): void
    {
        User::factory()->create(['email' => 'kind@schule.de']);
        Cache::put('app_sso_state:abc', 'challenge', now()->addMinutes(5));
        $this->mockKeycloak($this->ssoUser('kind@schule.de', ['realm_access' => ['roles' => ['schueler']]]));

        $response = $this->get(route('api.v1.auth.sso.callback', ['state' => 'abc']));

        $response->assertRedirect();
        $this->assertStringContainsString('error=student', $response->headers->get('Location'));
    }
}
