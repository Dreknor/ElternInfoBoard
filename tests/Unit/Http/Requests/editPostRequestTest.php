<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\editPostRequest;

use Tests\TestCase;

/**
 * @see \App\Http\Requests\editPostRequest
 */
class editPostRequestTest extends TestCase
{
    /** @var \App\Http\Requests\editPostRequest */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new editPostRequest();
    }

    /**
     * @test
     */
        /**
     * @test
     */
    public function authorize()
    {
        $actual = $this->subject->authorize();
        $this->assertTrue($actual);
    }

    /**
     * @test
     */
        /**
     * @test
     */
    public function rules()
    {
        $actual = $this->subject->rules();

        $this->assertIsArray($actual);
        // Validierungsregeln werden durch die Request-Klasse definiert
    }

    // test cases...
}
