<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Http\Controllers\Auth\ExpiredPasswordController;
use App\Http\Requests\PasswordExpiredRequest;
use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Auth\ExpiredPasswordController
 */
class ExpiredPasswordControllerTest extends TestCase
{
    use RefreshDatabase;
    /**
     * @test
     */
    public function expired_returns_an_ok_response_if_changePassword_needed()
    {
        $user = User::factory()->make([
            'changePassword'=>1
        ]);

        $this->actingAs($user);

        $response = $this->get(route('password.expired'));

        $response->assertOk();
        $response->assertViewIs('auth.passwords.expired');

    }

    /**
     * @test
     */
    public function expired_returns_an_redirect_response_if_no_changePassword_needed()
    {
        $user = User::factory()->make([
            'changePassword'=>0
        ]);

        $this->actingAs($user);

        $response = $this->get(route('password.expired'));
        $response->assertRedirect(url('/'));
    }

    /**
     * @test
     */
    public function post_expired_returns_redirect_withError_response_if_Password_wrong()
    {
        $user = User::factory()->make([
            'changePassword'=>1
        ]);

        $this->actingAs($user);

        $response = $this->from(route('password.expired'))->post(route('password.post_expired'), [
            'current_password'=> null
        ]);

        $this->assertActionUsesFormRequest(ExpiredPasswordController::class, 'postExpired', PasswordExpiredRequest::class);
        $response->assertRedirect(route('password.expired'))->withErrors(['current_password' => 'Current password is not correct']);
    }


    /**
     * @test
     */
    public function post_expired_returns_redirect_withError_response_if_Passwords_not_equal()
    {
        $user = User::factory()->make([
            'changePassword'=>1,
            'password' => '12345678'
        ]);

        $this->actingAs($user);

        $response = $this->from(route('password.expired'))->post(route('password.post_expired'), [
            'current_password'=> '12345678',
            'password'=> '876543210',
            'password_confirmed'=> '87654321',

        ]);

        $response->assertRedirect(route('password.expired'))->withErrors(['password' => 'The password confirmation does not match.']);
        $this->assertActionUsesFormRequest(ExpiredPasswordController::class, 'postExpired', PasswordExpiredRequest::class);
    }


    /**
     * @test
     */
    public function post_expired_returns_change_password()
    {
        $user = User::factory()->create([
            'changePassword'=>1,
            'password'=>bcrypt('password')
        ]);

        $password = Str::random(8);

        $this->actingAs($user);

        $response = $this->from(route('password.expired'))->post(route('password.post_expired'), [
            'current_password'=> 'password',
            'password'=> $password,
            'password_confirmation'=> $password,
        ]);


        $user->refresh();

        $this->assertFalse(Hash::check('password', $user->password));
        $this->assertTrue(Hash::check($password, $user->password));
        $this->assertTrue($user->changePassword == 0);
        $response->assertRedirect(url('home'))->with(['status' => 'Password changed successfully']);
        $this->assertActionUsesFormRequest(ExpiredPasswordController::class, 'postExpired', PasswordExpiredRequest::class);

    }
}
