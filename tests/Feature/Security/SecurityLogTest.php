<?php

namespace Tests\Feature\Security;

use App\Model\SecurityEvent;
use App\Model\User;
use App\Services\Security\SecurityLog;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class SecurityLogTest extends TestCase
{
    public function test_record_schreibt_eintrag(): void
    {
        $user = User::factory()->create();

        SecurityLog::record('auth.login', $user->id, ['method' => 'password']);

        $e = SecurityEvent::firstOrFail();
        $this->assertSame('auth.login', $e->event);
        $this->assertSame($user->id, $e->user_id);
        $this->assertNull($e->actor_id);
        $this->assertSame(['method' => 'password'], $e->context);
        $this->assertNotNull($e->created_at);
    }

    public function test_kontext_wird_bereinigt(): void
    {
        SecurityLog::record('x.test', null, [
            'Password' => 'a', 'new_passwort' => 'b', 'API_TOKEN' => 'c', 'secret' => 'd',
            'invite_code' => 'e', 'appKey' => 'f', 'Signature' => 'g',
            'ok' => 'wert', 'lang' => str_repeat('a', 400),
            'objekt' => new \stdClass, 'liste' => ['a', 1, ['tief'], 'token' => 'x'],
        ]);

        $c = SecurityEvent::firstOrFail()->context;
        $this->assertSame(['ok', 'lang', 'liste'], array_keys($c));
        $this->assertSame(255, mb_strlen($c['lang']));
        $this->assertSame(['a', 1], $c['liste']);
    }

    public function test_maximal_20_schluessel(): void
    {
        $ctx = [];
        for ($i = 0; $i < 30; $i++) {
            $ctx["k$i"] = $i;
        }
        SecurityLog::record('x.test', null, $ctx);

        $this->assertCount(20, SecurityEvent::firstOrFail()->context);
    }

    public function test_deaktiviert_schreibt_nichts(): void
    {
        config(['security_log.enabled' => false]);

        SecurityLog::record('auth.login');

        $this->assertSame(0, SecurityEvent::count());
    }

    public function test_actor_id_bei_impersonation_und_abweichendem_akteur(): void
    {
        $admin = User::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user);
        SecurityLog::record('a.b');
        $this->assertNull(SecurityEvent::latest('id')->first()->actor_id);

        SecurityLog::record('a.c', $admin->id);
        $this->assertSame($user->id, SecurityEvent::latest('id')->first()->actor_id);

        session(['ownID' => Crypt::encryptString($admin->id)]);
        SecurityLog::record('a.d');
        $e = SecurityEvent::latest('id')->first();
        $this->assertSame($user->id, $e->user_id);
        $this->assertSame($admin->id, $e->actor_id);

        session(['ownID' => (string) $admin->id]);
        SecurityLog::record('a.e');
        $this->assertSame($admin->id, SecurityEvent::latest('id')->first()->actor_id);
    }

    public function test_exception_wird_nicht_nach_aussen_geworfen(): void
    {
        \Schema::drop('security_events');

        SecurityLog::record('auth.login');

        $this->assertTrue(true);
    }

    public function test_identifier_hash_ist_stabil_und_ohne_klartext(): void
    {
        $h = SecurityLog::identifierHash('  Max.Muster@Example.org ');

        $this->assertSame($h, SecurityLog::identifierHash('max.muster@example.org'));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{16}$/', $h);
        $this->assertNotSame($h, SecurityLog::identifierHash('andere@example.org'));
        $this->assertStringNotContainsString('muster', $h);
    }

    public function test_cleanup_loescht_und_anonymisiert(): void
    {
        config(['security_log.retention_days' => 90, 'security_log.anonymize_ip_after_days' => 30]);

        $mk = fn (int $days, string $ip) => SecurityEvent::create([
            'event' => 'x.test', 'ip_address' => $ip, 'user_agent' => 'Mozilla', 'created_at' => now()->subDays($days),
        ]);
        $alt = $mk(100, '1.2.3.4');
        $v4 = $mk(40, '192.168.10.77');
        $v6 = $mk(40, '2001:db8:abcd:1234:5678:9abc:def0:1234');
        $neu = $mk(5, '10.0.0.5');

        $this->artisan('security-log:cleanup')->assertExitCode(0);

        $this->assertModelMissing($alt);
        $this->assertSame('192.168.10.0', $v4->fresh()->ip_address);
        $this->assertNull($v4->fresh()->user_agent);
        $this->assertSame('2001:db8:abcd::', $v6->fresh()->ip_address);
        $this->assertNull($v6->fresh()->user_agent);
        $this->assertSame('10.0.0.5', $neu->fresh()->ip_address);
        $this->assertSame('Mozilla', $neu->fresh()->user_agent);

        // idempotent
        $this->artisan('security-log:cleanup')->assertExitCode(0);
        $this->assertSame('192.168.10.0', $v4->fresh()->ip_address);
    }
}
