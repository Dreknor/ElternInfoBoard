<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\UpdatePollRequest;

use Tests\TestCase;

/**
 * @see \App\Http\Requests\UpdatePollRequest
 */
class UpdatePollRequestTest extends TestCase
{
    /** @var \App\Http\Requests\UpdatePollRequest */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new UpdatePollRequest();
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
