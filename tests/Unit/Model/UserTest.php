<?php

namespace Tests\Unit\Model;

use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Unit-Tests für das User Model
 */
class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function user_can_be_created_with_factory()
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(User::class, $user);
        $this->assertNotNull($user->id);
        $this->assertNotNull($user->email);
    }

    /**
     * @test
     */
    public function user_password_is_hashed()
    {
        $user = User::factory()->create([
            'password' => Hash::make('test-password'),
        ]);

        $this->assertNotEquals('test-password', $user->password);
        $this->assertTrue(Hash::check('test-password', $user->password));
    }

    /**
     * @test
     */
    public function user_has_required_attributes()
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertNotNull($user->password);
    }

    /**
     * @test
     */
    public function user_can_have_track_login_enabled()
    {
        $user = User::factory()->create([
            'track_login' => true,
        ]);

        $this->assertTrue($user->track_login);
    }

    /**
     * @test
     */
    public function user_can_have_send_copy_enabled()
    {
        $user = User::factory()->create([
            'sendCopy' => true,
        ]);

        $this->assertTrue($user->sendCopy);
    }

    /**
     * @test
     */
    public function user_can_have_change_password_flag()
    {
        $user = User::factory()->create([
            'changePassword' => true,
        ]);

        $this->assertTrue($user->changePassword);
    }

    /**
     * @test
     */
    public function user_has_last_online_tracking()
    {
        $user = User::factory()->create([
            'last_online_at' => now(),
        ]);

        $this->assertNotNull($user->last_online_at);
        $this->assertInstanceOf(\DateTime::class, $user->last_online_at);
    }

    /**
     * @test
     */
    public function user_email_must_be_unique()
    {
        $email = 'unique@example.com';

        User::factory()->create(['email' => $email]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        User::factory()->create(['email' => $email]);
    }

    /**
     * @test
     */
    public function user_can_have_benachrichtigung_setting()
    {
        $user = User::factory()->create([
            'benachrichtigung' => 'email',
        ]);

        $this->assertEquals('email', $user->benachrichtigung);
    }
}
