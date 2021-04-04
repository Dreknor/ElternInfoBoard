<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\UserController
 */
class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function create_returns_an_ok_response()
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $response = $this->get(route('users.create'));

        $response->assertOk();
        $response->assertViewIs('user.create');
        $response->assertViewHas('gruppen');

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function destroy_returns_an_ok_response()
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $response = $this->delete('users/{id}');

        $response->assertOk();
        $this->assertDeleted($$id);

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function index_returns_an_ok_response()
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $response = $this->get(route('users.index'));

        $response->assertOk();
        $response->assertViewIs('user.index');
        $response->assertViewHas('users');

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function login_as_user_returns_an_ok_response()
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $response = $this->get('showUser/{id}');

        $response->assertRedirect(back());

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function show_returns_an_ok_response()
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $response = $this->get(route('users.show', ['user' => $user]));

        $response->assertOk();
        $response->assertViewIs('user.show');
        $response->assertViewHas('user');
        $response->assertViewHas('gruppen');
        $response->assertViewHas('permissions');
        $response->assertViewHas('roles');

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function store_returns_an_ok_response()
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $response = $this->post(route('users.store'), [
            // TODO: send request data
        ]);

        $response->assertRedirect(url("users/{$user->id}"));

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function update_returns_an_ok_response()
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $response = $this->put(route('users.update', ['user' => $user]), [
            // TODO: send request data
        ]);

        $response->assertRedirect(back());

        // TODO: perform additional assertions
    }

    // test cases...
}
