<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\ImageController
 */
class ImageControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function get_image_returns_an_ok_response()
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $response = $this->get('image/{media_id}');

        $response->assertOk();

        // TODO: perform additional assertions
    }

    // test cases...
}
