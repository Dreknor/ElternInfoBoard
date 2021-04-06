<?php

namespace Tests\Feature;

use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BasicTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic test example.
     *
     * @return void
     */

    /**
     * @test
     */
    public function redirect_if_no_login()
    {
        $response = $this->get('/');

        $response->assertRedirect(url('login'));
    }

    /**
     * @test
     */
    public function redirect_if_login()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertViewIs('home');
    }
    
}
