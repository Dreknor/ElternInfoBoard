<?php

namespace Tests\Feature\Security;

use App\Model\Child;
use App\Model\Group;
use App\Model\SecurityEvent;
use App\Model\User;
use App\Services\McpTokens;
use App\Services\Security\SecurityLog;
use App\Services\Students\StudentAccountService;
use App\Settings\SchuelerSetting;
use App\Support\StudentAccounts;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\ActivatesModules;
use Tests\TestCase;

/**
 * Ausgabe, Verlängerung und Widerruf von Tokens im Sicherheitsprotokoll – ohne Tokenwert.
 */
class TokenLifecycleLogTest extends TestCase
{
    use ActivatesModules;

    private function events(string $event)
    {
        return SecurityEvent::where('event', $event)->orderBy('id')->get();
    }

    private function assertNotLogged(string ...$values): void
    {
        $dump = SecurityEvent::all()->toJson();
        foreach ($values as $v) {
            $this->assertStringNotContainsString($v, $dump);
        }
    }

    private function parent(): User
    {
        return User::factory()->create(['email' => 'eltern@example.org', 'password' => Hash::make('Geheim12345'), 'changePassword' => false]);
    }

    public function test_app_login_refresh_logout(): void
    {
        $user = $this->parent();

        $token = $this->postJson('/api/v1/token', ['email' => 'eltern@example.org', 'password' => 'Geheim12345', 'device_name' => 'iPhone von Max'])
            ->assertOk()->json('data.token');

        $login = $this->events('auth.login')->sole();
        $this->assertSame($user->id, $login->user_id);
        $this->assertSame(['guard' => 'sanctum', 'method' => 'password', 'client' => 'app'], $login->context);

        $issued = $this->events('token.issued')->sole();
        $first = $user->tokens()->sole();
        $this->assertSame($first->id, $issued->context['token_id']);
        $this->assertSame('app', $issued->context['token_type']);
        $this->assertArrayHasKey('expires_at', $issued->context);

        $new = $this->postJson('/api/v1/token/refresh', [], ['Authorization' => 'Bearer '.$token])->assertOk()->json('data.token');
        $refreshed = $this->events('token.refreshed')->sole();
        $this->assertSame($first->id, $refreshed->context['previous_token_id']);
        $this->assertSame($user->tokens()->sole()->id, $refreshed->context['token_id']);

        $this->app['auth']->forgetGuards();
        $this->postJson('/api/v1/auth/web-link', ['path' => '/datenschutz'], ['Authorization' => 'Bearer '.$new])->assertOk();
        $this->assertSame($user->id, $this->events('auth.web_link.issued')->sole()->user_id);

        $this->app['auth']->forgetGuards();
        $this->postJson('/api/v1/token/logout', [], ['Authorization' => 'Bearer '.$new])->assertOk();
        $revoked = $this->events('token.revoked')->sole();
        $this->assertSame('logout', $revoked->context['reason']);
        $this->assertSame(0, $user->tokens()->count());

        $this->assertNotLogged($token, $new, explode('|', $new)[1], 'iPhone von Max', 'eltern@example.org', 'Geheim12345');
    }

    public function test_app_login_fehlschlag(): void
    {
        $user = $this->parent();

        $this->postJson('/api/v1/token', ['email' => 'eltern@example.org', 'password' => 'falsch', 'device_name' => 'X'])->assertStatus(422);
        $this->postJson('/api/v1/token', ['email' => 'wer@example.org', 'password' => 'falsch', 'device_name' => 'X'])->assertStatus(422);

        [$known, $unknown] = $this->events('auth.failed')->all();
        $this->assertSame($user->id, $known->user_id);
        $this->assertSame('invalid_credentials', $known->context['reason']);
        $this->assertNull($unknown->user_id);
        $this->assertSame(SecurityLog::identifierHash('wer@example.org'), $unknown->context['identifier_hash']);
        $this->assertNotLogged('wer@example.org', 'eltern@example.org');
    }

    public function test_code_tausch_und_magic_link_der_app(): void
    {
        Notification::fake();
        Permission::firstOrCreate(['name' => 'allow password-less-login', 'guard_name' => 'web']);
        $user = $this->parent();
        $user->givePermissionTo('allow password-less-login');

        $this->postJson('/api/v1/auth/magic-link', ['email' => 'eltern@example.org'])->assertOk();
        $this->assertSame('app', $this->events('auth.magic_link.sent')->sole()->context['channel']);

        $this->postJson('/api/v1/auth/exchange', ['code' => 'gibtsnicht', 'device_name' => 'X'])->assertStatus(422);
        $this->assertSame('invalid_code', $this->events('auth.failed')->sole()->context['reason']);

        Cache::put('app_login_code:abc', ['user_id' => $user->id, 'challenge' => null], now()->addMinute());
        $this->postJson('/api/v1/auth/exchange', ['code' => 'abc', 'device_name' => 'X'])->assertOk();
        $this->assertSame('app_code', $this->events('auth.login')->sole()->context['method']);
        $this->assertCount(1, $this->events('token.issued'));
    }

    public function test_qr_code_fuer_app_wird_protokolliert(): void
    {
        $user = $this->parent();

        $this->actingAs($user)->postJson('/einstellungen/app-verbinden')->assertOk();

        $e = $this->events('auth.app_code.issued')->sole();
        $this->assertSame($user->id, $e->user_id);
        $this->assertSame('qr', $e->context['channel']);
    }

    public function test_alter_api_login(): void
    {
        $user = $this->parent();

        $this->postJson('/api/token/create', ['email' => 'eltern@example.org', 'password' => 'falsch', 'device_name' => 'X'])->assertStatus(422);
        $token = $this->postJson('/api/token/create', ['email' => 'eltern@example.org', 'password' => 'Geheim12345', 'device_name' => 'X'])
            ->assertOk()->json('token');

        $this->assertSame($user->id, $this->events('auth.failed')->sole()->user_id);
        $this->assertSame('legacy_api', $this->events('auth.login')->sole()->context['client']);
        $this->assertSame('app', $this->events('token.issued')->sole()->context['token_type']);

        $this->postJson('/api/token/logout', [], ['Authorization' => 'Bearer '.$token])->assertOk();
        $this->assertSame('logout', $this->events('token.revoked')->sole()->context['reason']);
    }

    public function test_integrations_token_ausgabe_und_widerruf(): void
    {
        $user = $this->parent();

        $this->actingAs($user)->post('/einstellungen/token', ['name' => 'Home Assistant', 'lifetime' => 'never', 'scopes' => ['krankmeldung']])
            ->assertRedirect();

        $issued = $this->events('token.issued')->sole();
        $this->assertSame('integration', $issued->context['token_type']);
        $this->assertNull($issued->context['expires_at']);
        $this->assertSame(['integration:krankmeldung'], $issued->context['scopes']);

        $id = $user->tokens()->sole()->id;
        $this->actingAs($user)->delete('/einstellungen/token/'.$id)->assertRedirect();
        $revoked = $this->events('token.revoked')->sole();
        $this->assertSame([$id], $revoked->context['token_ids']);
        $this->assertSame('user_revoked', $revoked->context['reason']);
        $this->assertNotLogged('Home Assistant');
    }

    public function test_geraet_abmelden_ueber_api(): void
    {
        $user = $this->parent();
        $current = $user->createToken('App')->plainTextToken;
        $other = $user->createToken('Tablet')->accessToken;

        $this->deleteJson('/api/v1/me/tokens/'.$other->id, [], ['Authorization' => 'Bearer '.$current])->assertOk();

        $this->assertSame([$other->id], $this->events('token.revoked')->sole()->context['token_ids']);
    }

    public function test_mcp_token(): void
    {
        $user = $this->parent();

        McpTokens::create($user);

        $this->assertSame('mcp', $this->events('token.issued')->sole()->context['token_type']);
    }

    private function student(): User
    {
        config(['modules.unlocked' => [StudentAccounts::MODULE]]);
        $this->activateModule(StudentAccounts::MODULE);
        StudentAccounts::flush();
        $child = Child::factory()->create(['class_id' => Group::factory()->create()->id]);
        [$student] = app(StudentAccountService::class)->createForChild($child, ['username' => 'kind.test']);
        $student->forceFill(['password' => Hash::make('geheim-12345')])->save();
        $s = app(SchuelerSetting::class);
        $s->login_benutzername = true;
        $s->save();

        return $student;
    }

    public function test_schuelerkonto_app_login_erfolg_und_fehlschlag(): void
    {
        $student = $this->student();

        $this->postJson('/api/v1/student/token', ['username' => 'kind.test', 'password' => 'falsch', 'device_name' => 'T'])->assertStatus(422);
        $this->postJson('/api/v1/student/token', ['username' => 'kind.nix', 'password' => 'falsch', 'device_name' => 'T'])->assertStatus(422);
        $token = $this->postJson('/api/v1/student/token', ['username' => 'kind.test', 'password' => 'geheim-12345', 'device_name' => 'T'])
            ->assertOk()->json('data.token');

        [$known, $unknown] = $this->events('auth.failed')->all();
        $this->assertSame($student->id, $known->user_id);
        $this->assertNull($unknown->user_id);
        $this->assertSame(SecurityLog::identifierHash('kind.nix'), $unknown->context['identifier_hash']);
        $this->assertSame('student_password', $this->events('auth.login')->sole()->context['method']);
        $this->assertSame('student', $this->events('token.issued')->sole()->context['token_type']);

        $this->postJson('/api/v1/student/token/refresh', [], ['Authorization' => 'Bearer '.$token])->assertOk();
        $this->assertCount(1, $this->events('token.refreshed'));
        $this->assertNotLogged('kind.nix', 'kind.test');
    }

    public function test_schuelerkonto_web_login_erfolg_und_fehlschlag(): void
    {
        $student = $this->student();

        $this->post('/schueler/anmelden', ['username' => 'kind.test', 'password' => 'falsch']);
        $this->post('/schueler/anmelden', ['username' => 'kind.test', 'password' => 'geheim-12345'])->assertRedirect(route('student.home'));

        $this->assertSame($student->id, $this->events('auth.failed')->sole()->user_id);
        $login = $this->events('auth.login')->sole();
        $this->assertSame($student->id, $login->user_id);
        $this->assertSame('student_password', $login->context['method']);
    }

    public function test_sperre_eines_schuelerkontos_widerruft_tokens(): void
    {
        $student = $this->student();
        $student->createToken('T', [StudentAccounts::TOKEN_ABILITY]);

        app(StudentAccountService::class)->deactivate($student);

        $this->assertSame('account_deactivated', $this->events('token.revoked')->sole()->context['reason']);
        $this->assertSame(0, $student->tokens()->count());
    }
}
