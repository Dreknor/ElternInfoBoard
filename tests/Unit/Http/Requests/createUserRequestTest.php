<?php

namespace Tests\Unit\Http\Requests;

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

        $this->subject = new \App\Http\Requests\createUserRequest();
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
            'name' => [
                'required',
                'string',
                'unique:users',
            ],
            'email' => [
                'required',
                'email',
                'unique:users',
            ],
            'password' => [
                'required',
                'confirmed',
            ],
        ], $actual);
    }

    // test cases...
}