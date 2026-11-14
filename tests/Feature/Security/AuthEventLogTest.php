<?php

namespace Tests\Feature\Security;

use App\Model\SecurityEvent;
use App\Model\User;
use App\Services\Security\SecurityLog;
use Grosv\LaravelPasswordlessLogin\LoginUrl;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Anmeldeereignisse im Sicherheitsprotokoll (zentral über AuthEventSubscriber).
 */
class AuthEventLogTest extends TestCase
{
    private function events(string $event)
    {
        return SecurityEvent::where('event', $event)->orderBy('id')->get();
    }

    /** Keine Kennung, kein Passwort im Klartext in irgendeinem Eintrag. */
    private function assertNoSecretsLogged(string ...$values): void
    {
        $dump = SecurityEvent::all()->toJson();
        foreach ($values as $v) {
            $this->assertStringNotContainsString($v, $dump);
        }
    }

    public function test_passwort_login_wird_einmal_protokolliert(): void
    {
        $user = User::factory()->create(['email' => 'eltern@example.org', 'password' => Hash::make('Geheim12345')]);

        $this->post('/login', ['email' => 'eltern@example.org', 'password' => 'Geheim12345'])->assertRedirect();

        $this->assertAuthenticatedAs($user);
        $logins = $this->events('auth.login');
        $this->assertCount(1, $logins);
        $this->assertSame($user->id, $logins[0]->user_id);
        $this->assertNull($logins[0]->actor_id);
        $this->assertSame(['guard' => 'web', 'method' => 'password', 'remember' => false], $logins[0]->context);
        $this->assertNoSecretsLogged('eltern@example.org', 'Geheim12345');
    }

    public function test_fehlgeschlagener_login_bekanntes_konto_mit_user_id(): void
    {
        $user = User::factory()->create(['email' => 'eltern@example.org', 'password' => Hash::make('Geheim12345')]);

        $this->post('/login', ['email' => 'eltern@example.org', 'password' => 'falsch-falsch']);

        $this->assertGuest();
        $e = $this->events('auth.failed')->sole();
        $this->assertSame($user->id, $e->user_id);
        $this->assertArrayNotHasKey('identifier_hash', $e->context);
        $this->assertNoSecretsLogged('eltern@example.org', 'falsch-falsch');
    }

    public function test_fehlgeschlagener_login_unbekanntes_konto_nur_mit_hash(): void
    {
        $this->post('/login', ['email' => 'Niemand@Example.org', 'password' => 'irgendwas123']);

        $e = $this->events('auth.failed')->sole();
        $this->assertNull($e->user_id);
        $this->assertSame(SecurityLog::identifierHash('niemand@example.org'), $e->context['identifier_hash']);
        $this->assertNoSecretsLogged('niemand', 'Niemand', 'irgendwas123');
    }

    public function test_lockout_wird_protokolliert(): void
    {
        $user = User::factory()->create(['email' => 'eltern@example.org']);

        event(new Lockout(request()->merge(['email' => 'eltern@example.org'])));

        $this->assertSame($user->id, $this->events('auth.lockout')->sole()->user_id);
    }

    public function test_logout_wird_protokolliert(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout');

        $e = $this->events('auth.logout')->sole();
        $this->assertSame($user->id, $e->user_id);
        $this->assertSame('web', $e->context['guard']);
    }

    public function test_remember_login_wird_als_remember_erkannt(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Geheim12345')]);

        $this->post('/login', ['email' => $user->email, 'password' => 'Geheim12345', 'remember' => 'on']);
        $this->assertTrue($this->events('auth.login')->sole()->context['remember']);

        // Neue Sitzung, nur mit Recaller-Cookie
        $recaller = Auth::guard('web')->getRecallerName();
        $cookie = $user->id.'|'.$user->fresh()->getRememberToken().'|'.$user->fresh()->getAuthPassword();
        Auth::forgetGuards();
        $this->flushSession();

        $this->withCookie($recaller, $cookie)->get('/datenschutz');
        Auth::guard('web')->user();

        $methods = $this->events('auth.login')->pluck('context.method')->all();
        $this->assertContains('remember', $methods);
    }

    public function test_passwort_reset_widerruft_tokens_und_protokolliert(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Altes-Passwort1')]);
        $user->createToken('App');
        $user->createToken('Tablet');
        $oldRemember = $user->fresh()->getRememberToken();
        $token = Password::broker()->createToken($user);

        $this->post('/password/reset', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'Neues-Passwort-123',
            'password_confirmation' => 'Neues-Passwort-123',
        ])->assertRedirect();

        $this->assertSame(0, $user->tokens()->count());
        $this->assertNotSame($oldRemember, $user->fresh()->getRememberToken());

        $reset = $this->events('auth.password_reset')->sole();
        $this->assertSame($user->id, $reset->user_id);
        $this->assertSame(2, $reset->context['revoked_token_count']);
        $this->assertSame('password_changed', $this->events('token.revoked')->sole()->context['reason']);
        $this->assertSame('password_reset', $this->events('auth.login')->sole()->context['method']);
    }

    public function test_reset_link_versand_wird_protokolliert(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/password/email', ['email' => $user->email]);

        $this->assertSame($user->id, $this->events('auth.password_reset_link_sent')->sole()->user_id);
        $this->assertNoSecretsLogged($user->email);
    }

    public function test_magic_link_versand_benutzung_und_ungueltig(): void
    {
        Notification::fake();
        Permission::firstOrCreate(['name' => 'allow password-less-login', 'guard_name' => 'web']);
        $user = User::factory()->create(['email' => 'magic@example.org']);
        $user->givePermissionTo('allow password-less-login');

        $this->post('/login', ['email' => 'magic@example.org', 'submit' => 'password-less'])->assertRedirect();
        $this->assertSame($user->id, $this->events('auth.magic_link.sent')->sole()->user_id);

        $this->post('/login', ['email' => 'unbekannt@example.org', 'submit' => 'password-less']);
        $rejected = $this->events('auth.magic_link.rejected')->sole();
        $this->assertNull($rejected->user_id);
        $this->assertSame(SecurityLog::identifierHash('unbekannt@example.org'), $rejected->context['identifier_hash']);

        $url = (new LoginUrl($user))->generate();
        $this->get($url)->assertRedirect();
        $this->assertAuthenticatedAs($user);
        $this->assertSame($user->id, $this->events('auth.magic_link.used')->sole()->user_id);
        $this->assertSame('passwordless', $this->events('auth.login')->sole()->context['method']);

        // Zweite Benutzung desselben Links ist ungültig
        Auth::logout();
        $this->flushSession();
        $this->get($url);
        $this->assertSame($user->id, $this->events('auth.magic_link.invalid')->sole()->user_id);
        $this->assertNoSecretsLogged('magic@example.org', 'unbekannt@example.org');
    }

    public function test_impersonation_start_und_stop_statt_login(): void
    {
        Permission::firstOrCreate(['name' => 'loginAsUser', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'edit user', 'guard_name' => 'web']);
        $admin = User::factory()->create(['changePassword' => false]);
        $admin->givePermissionTo('loginAsUser', 'edit user');
        $target = User::factory()->create(['changePassword' => false]);

        $this->actingAs($admin)->post('/showUser/'.$target->id)->assertRedirect();
        $this->assertAuthenticatedAs($target);

        $start = $this->events('impersonation.start')->sole();
        $this->assertSame($target->id, $start->user_id);
        $this->assertSame($admin->id, $start->actor_id);

        $this->get('/logoutAsUser')->assertRedirect();
        $this->assertAuthenticatedAs($admin);

        $stop = $this->events('impersonation.stop')->sole();
        $this->assertSame($target->id, $stop->user_id);
        $this->assertSame($admin->id, $stop->actor_id);

        $this->assertCount(0, $this->events('auth.login'));
    }
}
