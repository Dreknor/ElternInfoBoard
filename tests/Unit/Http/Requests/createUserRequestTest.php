<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\createUserRequest;

use Tests\TestCase;

/**
 * @see \App\Http\Requests\createUserRequest
 */
class createUserRequestTest extends TestCase
{
    /** @var \App\Http\Requests\createUserRequest */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new createUserRequest();
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
