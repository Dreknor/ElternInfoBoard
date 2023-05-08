<?php

namespace Tests\Feature\Http\Controllers;

use Tests\TestCase;

/**
 * @see \App\Http\Controllers\TerminController
 */
class TerminControllerTest extends TestCase
{
    /**
     * @test
     */
    public function create_returns_an_ok_response()
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $response = $this->get(route('termin.create'));

        $response->assertRedirect(url('home'));

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function destroy_returns_an_ok_response()
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $response = $this->delete(route('termin.destroy', ['termin' => $termin]));

        $response->assertRedirect(back());
        $this->assertModelMissing($termin);

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function store_returns_an_ok_response()
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $response = $this->post(route('termin.store'), [
            // TODO: send request data
        ]);

        $response->assertRedirect(back());

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function store_validates_with_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\TerminController::class,
            'store',
            \App\Http\Requests\CreateTerminRequest::class
        );
    }

    // test cases...
}
