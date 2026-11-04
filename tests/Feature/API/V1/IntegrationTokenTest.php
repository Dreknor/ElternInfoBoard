<?php

namespace Tests\Feature\API\V1;

use App\Model\PersonalAccessToken;
use App\Model\User;
use App\Services\IntegrationTokens;

/**
 * Integrations-Tokens (Home Assistant u. ä.): eigene Laufzeit, nur lesen plus freigegebene Bereiche.
 */
class IntegrationTokenTest extends AppApiTestCase
{
    private function bearer(string $token): static
    {
        // Sanctum merkt sich den Nutzer je Guard – zwischen Anfragen mit wechselnden Tokens zurücksetzen.
        $this->app['auth']->forgetGuards();

        return $this->withHeader('Authorization', 'Bearer '.$token);
    }

    /** @test */
    public function integration_token_ignores_global_expiration_but_respects_its_own(): void
    {
        config(['sanctum.expiration' => 60 * 24 * 30]);
        $user = User::factory()->create(['changePassword' => false]);

        $unlimited = IntegrationTokens::create($user, 'Home Assistant', [], 'never')->plainTextToken;
        $shortLived = IntegrationTokens::create($user, 'Test', [], '90')->plainTextToken;
        $app = $user->createToken('iPhone')->plainTextToken;

        $this->travel(60)->days();
        $this->bearer($unlimited)->getJson('/api/v1/me')->assertOk();
        $this->bearer($shortLived)->getJson('/api/v1/me')->assertOk();
        $this->bearer($app)->getJson('/api/v1/me')->assertUnauthorized();

        $this->travel(31)->days();
        $this->bearer($shortLived)->getJson('/api/v1/me')->assertUnauthorized();
        $this->bearer($unlimited)->getJson('/api/v1/me')->assertOk();
    }

    /** @test */
    public function integration_token_can_read_but_not_write(): void
    {
        $user = $this->parentIn($this->group());
        $token = IntegrationTokens::create($user, 'Home Assistant', [], '365');

        $this->bearer($token->plainTextToken)->getJson('/api/v1/parent/dashboard')->assertOk();
        $this->bearer($token->plainTextToken)->getJson('/api/notifications')->assertOk();

        foreach ([
            ['post', '/api/v1/token/refresh'],
            ['post', '/api/v1/auth/web-link'],
            ['post', '/api/v1/notifications/read-all'],
            ['post', '/api/notification/readAll'],
            ['patch', '/api/v1/me'],
            ['delete', '/api/v1/me/tokens/'.$token->accessToken->id],
            ['post', '/api/v1/parent/krankmeldungen'],
        ] as [$method, $uri]) {
            $this->bearer($token->plainTextToken)->json($method, $uri)
                ->assertForbidden()
                ->assertJsonPath('code', 'integration_scope');
        }

        $this->assertDatabaseHas('personal_access_tokens', ['id' => $token->accessToken->id]);
    }

    /** @test */
    public function granted_scopes_allow_sick_note_and_marking_notifications_read(): void
    {
        $group = $this->group();
        $user = $this->parentIn($group);
        $child = $this->childOf($user, $group);
        $token = IntegrationTokens::create($user, 'Home Assistant', ['krankmeldung', 'notifications'], '365')->plainTextToken;

        $this->bearer($token)->postJson('/api/v1/parent/krankmeldungen', [
            'child_id' => $child->id,
            'start' => today()->toDateString(),
            'ende' => today()->toDateString(),
            'kommentar' => 'Fieber',
        ])->assertCreated();

        $this->bearer($token)->postJson('/api/v1/notifications/read-all')->assertOk();
        $this->bearer($token)->postJson('/api/v1/token/refresh')->assertForbidden();
    }

    /** @test */
    public function app_tokens_keep_full_access(): void
    {
        $user = $this->parentIn($this->group());
        $token = $user->createToken('iPhone')->plainTextToken;

        $this->bearer($token)->postJson('/api/v1/notifications/read-all')->assertOk();
    }

    /** @test */
    public function settings_page_creates_integration_token_with_lifetime_and_scopes(): void
    {
        $user = $this->parentIn($this->group());

        $this->actingAs($user)->post('/einstellungen/token', [
            'name' => 'Home Assistant',
            'lifetime' => 'never',
            'scopes' => ['krankmeldung'],
        ])->assertRedirect()->assertSessionHas('token');

        $token = PersonalAccessToken::sole();
        $this->assertTrue(IntegrationTokens::isIntegration($token));
        $this->assertSame(['krankmeldung'], IntegrationTokens::scopes($token));
        $this->assertNull($token->expires_at);

        $this->actingAs($user)->get('/einstellungen')->assertOk()
            ->assertSee('Home Assistant')->assertSee('Lesen, Krankmeldungen abgeben')->assertSee('unbegrenzt');

        $this->actingAs($user)->post('/einstellungen/token', ['name' => 'x', 'lifetime' => '9999'])
            ->assertSessionHasErrors('lifetime');
    }
}
