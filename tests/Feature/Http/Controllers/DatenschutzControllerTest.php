<?php

namespace Tests\Feature\Http\Controllers;

use Tests\TestCase;

/**
 * @see \App\Http\Controllers\DatenschutzController
 */
class DatenschutzControllerTest extends TestCase
{
    /**
     * @test
     */
    public function show_returns_an_ok_response()
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $response = $this->get('datenschutz');

        $response->assertOk();
        $response->assertViewIs('datenschutz.show');
        $response->assertViewHas('user');

        // TODO: perform additional assertions
    }

    // test cases...
}
