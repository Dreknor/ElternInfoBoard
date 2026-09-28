<?php

namespace Tests\Feature\API\V1;

use App\Model\Krankmeldungen;
use App\Model\ReadReceipts;
use App\Model\UserDevice;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

class AuthAndSecurityTest extends AppApiTestCase
{
    /** @test */
    public function instance_info_is_public_and_contains_theme_and_auth(): void
    {
        $this->getJson('/api/v1/instance')
            ->assertOk()
            ->assertJsonStructure(['data' => ['name', 'logo_url', 'icon_url', 'theme' => ['id', 'colors' => ['primary', 'background']], 'auth' => ['password', 'sso', 'magic_link']]]);
    }

    /** @test */
    public function login_returns_token_and_password_flag(): void
    {
        $user = $this->parentIn($this->group(), ['password' => Hash::make('Geheim12345'), 'changePassword' => true]);

        $this->postJson('/api/v1/token', ['email' => $user->email, 'password' => 'Geheim12345', 'device_name' => 'Test'])
            ->assertOk()
            ->assertJsonPath('data.must_change_password', true)
            ->assertJsonStructure(['data' => ['token', 'expires_at', 'user' => ['id', 'name']]]);

        $this->postJson('/api/v1/token', ['email' => $user->email, 'password' => 'falsch', 'device_name' => 'Test'])
            ->assertStatus(422);
    }

    /** @test */
    public function forced_password_change_blocks_everything_but_profile(): void
    {
        $user = $this->parentIn($this->group(), ['changePassword' => true]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/posts')->assertStatus(403)->assertJsonPath('code', 'password_change_required');
        $this->getJson('/api/v1/bootstrap')->assertOk();
    }

    /** @test */
    public function read_receipt_requires_access_to_post(): void
    {
        $foreign = $this->postIn($this->group('Klasse 4b'), ['read_receipt' => true]);
        $user = $this->parentIn($this->group());
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/posts/{$foreign->id}/read")->assertStatus(403);
        $this->postJson("/api/posts/{$foreign->id}/read")->assertStatus(403);
        $this->assertSame(0, ReadReceipts::count());
    }

    /** @test */
    public function sick_note_only_for_own_children_and_linked_to_child(): void
    {
        $group = $this->group();
        $user = $this->parentIn($group);
        $other = $this->parentIn($group);
        $own = $this->childOf($user, $group);
        $foreign = $this->childOf($other, $group);
        Sanctum::actingAs($user);

        $body = ['start' => today()->toDateString(), 'ende' => today()->toDateString(), 'kommentar' => 'Fieber'];

        $this->postJson('/api/v1/parent/krankmeldungen', $body + ['child_id' => $foreign->id])->assertStatus(403);
        $this->postJson('/api/krankmeldung', ['child_id' => $foreign->id, 'start' => today()->format('d.m.Y'), 'ende' => today()->format('d.m.Y'), 'kommentar' => 'x'])
            ->assertStatus(403);

        $this->postJson('/api/v1/parent/krankmeldungen', $body + ['child_id' => $own->id])->assertCreated();
        $this->assertSame($own->id, (int) Krankmeldungen::sole()->child_id);
    }

    /** @test */
    public function repeated_request_with_same_idempotency_key_is_executed_once(): void
    {
        $group = $this->group();
        $user = $this->parentIn($group);
        $child = $this->childOf($user, $group);
        Sanctum::actingAs($user);
        $body = ['child_id' => $child->id, 'start' => today()->toDateString(), 'ende' => today()->toDateString()];

        $first = $this->postJson('/api/v1/parent/krankmeldungen', $body, ['Idempotency-Key' => 'abc-123']);
        $second = $this->postJson('/api/v1/parent/krankmeldungen', $body, ['Idempotency-Key' => 'abc-123']);

        $first->assertCreated();
        $second->assertCreated()->assertHeader('Idempotent-Replayed', 'true');
        $this->assertSame(1, Krankmeldungen::count());
    }

    /** @test */
    public function devices_can_be_registered_and_removed(): void
    {
        $user = $this->parentIn($this->group());
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/devices', ['token' => 'tok-1', 'provider' => 'fcm', 'platform' => 'android'])->assertCreated();
        $this->assertSame($user->id, UserDevice::sole()->user_id);

        $this->deleteJson('/api/v1/devices/tok-1')->assertOk();
        $this->assertSame(0, UserDevice::count());
    }

    /** @test */
    public function qr_code_login_code_can_be_exchanged_once(): void
    {
        $user = $this->parentIn($this->group());

        $response = $this->actingAs($user)->postJson(route('app.connect.qr'))->assertOk()->assertJsonStructure(['svg', 'expires_at']);
        $this->assertStringContainsString('<svg', $response->json('svg'));

        // Code aus dem Cache holen (steht im QR-Code)
        $code = cache()->get("app_qr_code_of:{$user->id}");
        $this->assertNotNull($code);

        auth()->forgetGuards();
        $this->postJson('/api/v1/auth/exchange', ['code' => $code, 'device_name' => 'Test'])
            ->assertOk()->assertJsonPath('data.user.id', $user->id);
        $this->postJson('/api/v1/auth/exchange', ['code' => $code, 'device_name' => 'Test'])->assertStatus(422);
    }

    /** @test */
    public function get_responses_support_etag(): void
    {
        $user = $this->parentIn($this->group());
        Sanctum::actingAs($user);

        $etag = $this->getJson('/api/v1/termine')->assertOk()->headers->get('ETag');
        $this->assertNotEmpty($etag);
        $this->getJson('/api/v1/termine', ['If-None-Match' => $etag])->assertStatus(304);
    }
}
