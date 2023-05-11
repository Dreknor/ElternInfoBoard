<?php

namespace Tests\Feature\Http\Controllers\Auth;

use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Auth\ExpiredPasswordController
 */
class ExpiredPasswordControllerTest extends TestCase
{
    /**
     * @test
     */
    public function expired_returns_an_ok_response()
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $response = $this->get(route('password.expired'));

        $response->assertOk();
        $response->assertViewIs('auth.passwords.expired');

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function post_expired_returns_an_ok_response()
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $response = $this->post(route('password.post_expired'), [
            // TODO: send request data
        ]);

        $response->assertRedirect(withErrors(['current_password' => 'Current password is not correct']));

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function postexpired_validates_with_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\Auth\ExpiredPasswordController::class,
            'postExpired',
            \App\Http\Requests\PasswordExpiredRequest::class
        );
    }

    // test cases...
}