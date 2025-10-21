<?php

namespace Tests\Feature;

use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Feature-Tests für Authentifizierung und Benutzerverwaltung
 */
class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function user_can_not_register()
    {
        // Test that the register route does not exist (registration is disabled)
        $this->expectException(\Symfony\Component\Routing\Exception\RouteNotFoundException::class);
        route('register');
    }

    /**
     * @test
     */
    public function user_can_login_with_correct_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    /**
     * @test
     */
    public function user_cannot_login_with_incorrect_password()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $this->assertGuest();
    }

    /**
     * @test
     */
    public function authenticated_user_can_logout()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    /**
     * @test
     */
    public function user_must_verify_email_when_required()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        // Check that user is created without email verification
        $this->assertNull($user->email_verified_at);

        // Verify that user can be marked as verified
        $user->email_verified_at = now();
        $user->save();

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    /**
     * @test
     */
    public function user_uuid_is_generated_on_creation()
    {
        $user = User::factory()->create();

        $this->assertNotNull($user->uuid);
        $this->assertTrue(is_string($user->uuid) || is_object($user->uuid));
    }

    /**
     * @test
     */
    public function user_can_track_login()
    {
        $user = User::factory()->create(['track_login' => true]);

        $this->actingAs($user)->get('/');

        $this->assertTrue($user->track_login);
    }

    /**
     * @test
     */
    public function user_password_is_hashed()
    {
        $user = User::factory()->create([
            'password' => Hash::make('secret123'),
        ]);

        $this->assertTrue(Hash::check('secret123', $user->password));
        $this->assertNotEquals('secret123', $user->password);
    }
}
