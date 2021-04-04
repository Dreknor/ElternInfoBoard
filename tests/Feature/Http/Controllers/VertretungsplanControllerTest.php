<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\VertretungsplanController
 */
class VertretungsplanControllerTest extends TestCase
{
    /**
     * @test
     */
    public function index_returns_an_ok_response()
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $response = $this->get('vertretungsplan');

        $response->assertOk();
        $response->assertViewIs('vertretungsplan.index');
        $response->assertViewHas('gruppen');

        // TODO: perform additional assertions
    }

    // test cases...
}
