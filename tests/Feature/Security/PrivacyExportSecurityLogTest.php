<?php

namespace Tests\Feature\Security;

use App\Model\SecurityEvent;
use App\Model\User;
use Tests\TestCase;

class PrivacyExportSecurityLogTest extends TestCase
{
    private function user(): User
    {
        return User::factory()->create(['password_changed_at' => now(), 'changePassword' => false]);
    }

    public function test_selbstauskunft_json_enthaelt_nur_eigene_eintraege_und_wird_protokolliert(): void
    {
        $user = $this->user();
        $other = $this->user();
        $admin = $this->user();

        SecurityEvent::create(['event' => 'auth.login', 'user_id' => $user->id, 'ip_address' => '10.0.0.1', 'user_agent' => 'TestAgent', 'context' => ['method' => 'password'], 'created_at' => now()]);
        SecurityEvent::create(['event' => 'rbac.role_assigned', 'user_id' => $user->id, 'actor_id' => $admin->id, 'created_at' => now()]);
        SecurityEvent::create(['event' => 'auth.failed', 'user_id' => $other->id, 'ip_address' => '203.0.113.77', 'created_at' => now()]);

        $response = $this->actingAs($user)->get('/datenschutz/export/json');
        $response->assertOk();
        $json = $response->streamedContent();
        $data = json_decode($json, true);

        $entries = $data['sicherheitsprotokoll']['eintraege'];
        $this->assertCount(2, $entries);
        $this->assertEqualsCanonicalizing(['auth.login', 'rbac.role_assigned'], array_column($entries, 'ereignis'));
        $this->assertStringNotContainsString('203.0.113.77', $json);
        $this->assertStringNotContainsString($admin->name, $json);

        $byEvent = array_column($entries, null, 'ereignis');
        $this->assertFalse($byEvent['auth.login']['durch_andere_person_ausgeloest']);
        $this->assertTrue($byEvent['rbac.role_assigned']['durch_andere_person_ausgeloest']);
        $this->assertSame('10.0.0.1', $byEvent['auth.login']['ip_adresse']);

        $export = SecurityEvent::where('event', 'privacy.export')->where('user_id', $user->id)->first();
        $this->assertNotNull($export);
        $this->assertSame(['format' => 'json'], $export->context);
    }

    public function test_selbstauskunft_pdf_wird_protokolliert(): void
    {
        $user = $this->user();
        SecurityEvent::create(['event' => 'auth.login', 'user_id' => $user->id, 'created_at' => now()]);

        $this->actingAs($user)->get('/datenschutz/export/pdf')->assertOk();

        $export = SecurityEvent::where('event', 'privacy.export')->where('user_id', $user->id)->first();
        $this->assertNotNull($export);
        $this->assertSame(['format' => 'pdf'], $export->context);
    }

    public function test_auskunftsseite_nennt_das_sicherheitsprotokoll(): void
    {
        $this->withoutVite();

        $this->actingAs($this->user())->get('/datenschutz')
            ->assertOk()
            ->assertSee('Sicherheitsprotokoll')
            ->assertDontSee('IP-Adressen werden von dieser Anwendung nicht gespeichert');
    }
}
