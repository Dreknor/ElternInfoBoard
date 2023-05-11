<?php

namespace Tests\Feature\Http\Controllers;

use Tests\TestCase;

/**
 * @see \App\Http\Controllers\PushController
 */
class PushControllerTest extends TestCase
{
    /**
     * @test
     */
    public function store_returns_an_ok_response()
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $response = $this->post('push', [
            // TODO: send request data
        ]);

        $response->assertOk();

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function store_validates_with_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\PushController::class,
            'store',
            \App\Http\Requests\StorePushRequest::class
        );
    }

    // test cases...
}