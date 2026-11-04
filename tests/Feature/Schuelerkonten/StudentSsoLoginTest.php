<?php

namespace Tests\Feature\Schuelerkonten;

use App\Model\Child;
use App\Model\Group;
use App\Model\User;
use App\Services\Auth\SsoStudentDetector;
use App\Services\Students\StudentAccountService;
use App\Services\Students\StudentSsoLogin;
use App\Settings\SchuelerSetting;
use App\Support\StudentAccounts;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\Concerns\ActivatesModules;
use Tests\TestCase;

/**
 * SSO-Anmeldung der Schüler über UCS (Pakete S-08/S-09).
 */
class StudentSsoLoginTest extends TestCase
{
    use ActivatesModules;

    protected function setUp(): void
    {
        parent::setUp();

        $ucs = $this->createStub(\App\Settings\UcsSetting::class);
        $ucs->enabled = true;
        $ucs->on_login_fallback = false;
        $ucs->on_login_timeout = 5;
        $this->app->instance(\App\Settings\UcsSetting::class, $ucs);

        (new \App\Settings\KeycloakSetting)->fill([
            'enabled' => true, 'client_id' => 'eib', 'client_secret' => 's', 'realm' => 'schule',
            'redirect_uri' => null, 'base_url' => 'https://auth.example.de', 'maildomain' => '*',
            'student_roles' => null, 'auto_create_users' => false,
        ])->save();
    }

    private function enableModule(): void
    {
        config(['modules.unlocked' => [StudentAccounts::MODULE]]);
        $this->activateModule(StudentAccounts::MODULE);
        StudentAccounts::flush();
    }

    private function mockUcs(string $username, array $roles = ['student:school:ESZ']): void
    {
        $user = (new SocialiteUser)
            ->setRaw(['preferred_username' => $username, 'ucsschoolRole' => $roles])
            ->map(['id' => 'sub-'.$username, 'email' => null, 'name' => 'Kind']);
        $user->setToken('x.e30.y');

        $provider = \Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
        $provider->shouldReceive('scopes')->andReturnSelf();
        $provider->shouldReceive('redirectUrl')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($user);
        Socialite::shouldReceive('driver')->with('ucs')->andReturn($provider);
    }

    /** @test */
    public function sso_login_is_rejected_while_module_is_off(): void
    {
        Child::factory()->create(['ucs_username' => 'kind.eins']);
        $this->mockUcs('kind.eins');

        $this->get(route('auth.ucs.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('Meldung', SsoStudentDetector::MESSAGE);
        $this->assertGuest();
        $this->assertSame(0, User::students()->count());
    }

    /** @test */
    public function existing_student_account_logs_in_via_sso(): void
    {
        $this->enableModule();
        $child = Child::factory()->create(['ucs_username' => 'kind.eins']);
        [$student] = app(StudentAccountService::class)->createForChild($child);
        $this->mockUcs('kind.eins');

        $this->get(route('auth.ucs.callback'))->assertRedirect(route('student.home'));
        $this->assertAuthenticatedAs($student);
        $this->assertSame('sub-kind.eins', $student->fresh()->ucs_oidc_sub);
    }

    /** @test */
    public function child_username_counts_as_student_even_without_role_claim(): void
    {
        $this->enableModule();
        Child::factory()->create(['ucs_username' => 'kind.eins']);
        $this->mockUcs('kind.eins', []);

        $this->get(route('auth.ucs.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('Meldung', StudentSsoLogin::MESSAGE_NO_ACCOUNT);
        $this->assertGuest();
        $this->assertSame(0, User::withoutStudents()->where('ucs_username', 'kind.eins')->count(), 'kein Elternkonto für ein Kind');
    }

    /** @test */
    public function jit_creates_accounts_only_for_released_groups(): void
    {
        $this->enableModule();
        $released = Group::factory()->create();
        $other = Group::factory()->create();
        Child::factory()->create(['ucs_username' => 'kind.eins', 'class_id' => $released->id]);
        Child::factory()->create(['ucs_username' => 'kind.zwei', 'class_id' => $other->id]);

        $s = app(SchuelerSetting::class);
        $s->jit_anlage = true;
        $s->freigegebene_gruppen = [$released->id];
        $s->save();

        $login = app(StudentSsoLogin::class);
        [$a] = $login->resolve('sub-1', 'kind.eins');
        [$b, $message] = $login->resolve('sub-2', 'kind.zwei');

        $this->assertNotNull($a);
        $this->assertTrue($a->isStudent());
        $this->assertNull($b);
        $this->assertSame(StudentSsoLogin::MESSAGE_NO_ACCOUNT, $message);
    }

    /** @test */
    public function locked_student_cannot_log_in_via_sso(): void
    {
        $this->enableModule();
        $child = Child::factory()->create(['ucs_username' => 'kind.eins']);
        [$student] = app(StudentAccountService::class)->createForChild($child);
        app(StudentAccountService::class)->deactivate($student);

        [$user, $message] = app(StudentSsoLogin::class)->resolve('sub-x', 'kind.eins');
        $this->assertNull($user);
        $this->assertSame(StudentSsoLogin::MESSAGE_INACTIVE, $message);
    }

    /** @test */
    public function sso_login_can_be_switched_off(): void
    {
        $this->enableModule();
        $child = Child::factory()->create(['ucs_username' => 'kind.eins']);
        app(StudentAccountService::class)->createForChild($child);
        $s = app(SchuelerSetting::class);
        $s->login_sso = false;
        $s->save();

        [$user] = app(StudentSsoLogin::class)->resolve('sub-x', 'kind.eins');
        $this->assertNull($user);
    }
}
