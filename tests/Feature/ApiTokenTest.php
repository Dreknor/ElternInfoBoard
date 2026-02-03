<?php

namespace Tests\Feature;

use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-Tests für API-Token-Management
 */
class ApiTokenTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function user_can_access_api_with_valid_token()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/me');

        $response->assertOk();
        $response->assertJsonFragment([
            'email' => $user->email,
        ]);
    }

    /**
     * @test
     */
    public function api_request_without_token_fails()
    {
        $response = $this->getJson('/api/me');

        $response->assertUnauthorized();
    }

    /**
     * @test
     */
    public function user_can_revoke_token()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $token->accessToken->delete();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
    }

    /**
     * @test
     */
    public function user_can_have_multiple_tokens()
    {
        $user = User::factory()->create();

        $token1 = $user->createToken('token-1');
        $token2 = $user->createToken('token-2');
        $token3 = $user->createToken('token-3');

        $this->assertCount(3, $user->tokens);
    }
}
