<?php

namespace Tests\Unit\Http\Requests;

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

        $this->subject = new \App\Http\Requests\editPostRequest();
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
            'header' => [
                'required',
            ],
            'news' => [
            ],
            'gruppen' => [
                'required',
            ],
            'archiv_ab' => [
                'required',
                'date',
            ],
            'password' => [
                'required_with:urgent',
            ],
            'type' => [
                'required',
            ],
            'reactable' => [
                'nullable',
                'boolean',
            ],
            'released' => [
                'nullable',
                'boolean',
            ],
        ], $actual);
    }

    // test cases...
}