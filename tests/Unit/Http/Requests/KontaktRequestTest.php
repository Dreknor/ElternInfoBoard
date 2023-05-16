<?php

namespace Tests\Unit\Http\Requests;

use Tests\TestCase;

/**
 * @see \App\Http\Requests\KontaktRequest
 */
class KontaktRequestTest extends TestCase
{
    /** @var \App\Http\Requests\KontaktRequest */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new \App\Http\Requests\KontaktRequest();
    }

    /**
     * @test
     */
    public function authorize()
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $actual = $this->subject->authorize();

        $this->assertTrue($actual);
    }

    /**
     * @test
     */
    public function rules()
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $actual = $this->subject->rules();

        $this->assertValidationRules([
            'text' => [
                'required',
                'string',
            ],
            'betreff' => [
                'required',
                'string',
            ],
            'mitarbeiter' => [
                'present',
            ],
        ], $actual);
    }

    // test cases...
}
