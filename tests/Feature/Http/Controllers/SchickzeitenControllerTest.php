<?php

namespace Tests\Feature\Http\Controllers;

use Tests\TestCase;

/**
 * @see \App\Http\Controllers\SchickzeitenController
 */
class SchickzeitenControllerTest extends TestCase
{
    /**
     * @test
     */
    public function create_child_returns_an_ok_response()
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $response = $this->post('schickzeiten/child/create', [
            // TODO: send request data
        ]);

        $response->assertRedirect(back());

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function createchild_validates_with_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\SchickzeitenController::class,
            'createChild',
            \App\Http\Requests\CreateChildRequest::class
        );
    }

    /**
     * @test
     */
    public function create_child_verwaltung_returns_an_ok_response()
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $response = $this->post('verwaltung/schickzeiten/child/create', [
            // TODO: send request data
        ]);

        $response->assertRedirect(back());

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function createchildverwaltung_validates_with_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\SchickzeitenController::class,
            'createChildVerwaltung',
            \App\Http\Requests\CreateChildRequest::class
        );
    }

    /**
     * @test
     */
    public function destroy_returns_an_ok_response()
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $response = $this->delete('schickzeiten/{day}/{child}');

        $response->assertRedirect(back());
        $this->assertModelMissing($child);

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function destroy_verwaltung_returns_an_ok_response()
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $response = $this->delete('verwaltung/schickzeiten/{day}/{child}/{parent}');

        $response->assertRedirect(back());

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function download_returns_an_ok_response()
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $response = $this->get('schickzeiten/download');

        $response->assertOk();

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function edit_returns_an_ok_response()
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $response = $this->get('schickzeiten/edit/{day}/{child}');

        $response->assertOk();
        $response->assertViewIs('schickzeiten.edit');
        $response->assertViewHas('child');
        $response->assertViewHas('day');
        $response->assertViewHas('day_number');
        $response->assertViewHas('schickzeit');
        $response->assertViewHas('schickzeit_spaet');

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function edit_verwaltung_returns_an_ok_response()
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $response = $this->get('verwaltung/schickzeiten/edit/{day}/{child}/{parent}');

        $response->assertOk();
        $response->assertViewIs('schickzeiten.edit_verwaltung');
        $response->assertViewHas('child');
        $response->assertViewHas('parent');
        $response->assertViewHas('day');
        $response->assertViewHas('day_number');
        $response->assertViewHas('schickzeit');
        $response->assertViewHas('schickzeit_spaet');

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function index_returns_an_ok_response()
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $response = $this->get('schickzeiten');

        $response->assertOk();
        $response->assertViewIs('schickzeiten.index');
        $response->assertViewHas('schickzeiten');
        $response->assertViewHas('childs');
        $response->assertViewHas('weekdays');

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function index_verwaltung_returns_an_ok_response()
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $response = $this->get('verwaltung/schickzeiten');

        $response->assertOk();
        $response->assertViewIs('schickzeiten.index_verwaltung');
        $response->assertViewHas('schickzeiten');
        $response->assertViewHas('childs');
        $response->assertViewHas('weekdays');
        $response->assertViewHas('parents');

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function store_returns_an_ok_response()
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $response = $this->post('schickzeiten', [
            // TODO: send request data
        ]);

        $response->assertRedirect(url('schickzeiten'));

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function store_validates_with_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\SchickzeitenController::class,
            'store',
            \App\Http\Requests\SchickzeitRequest::class
        );
    }

    /**
     * @test
     */
    public function store_verwaltung_returns_an_ok_response()
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $response = $this->post('verwaltung/schickzeiten/{parent}', [
            // TODO: send request data
        ]);

        $response->assertRedirect(url('verwaltung/schickzeiten'));

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function storeverwaltung_validates_with_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\SchickzeitenController::class,
            'storeVerwaltung',
            \App\Http\Requests\SchickzeitRequest::class
        );
    }

    // test cases...
}
