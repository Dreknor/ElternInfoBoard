<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\CommentPostRequest;
use App\Model\User;
use Tests\TestCase;

/**
 * @see \App\Http\Requests\CommentPostRequest
 */
class CommentPostRequestTest extends TestCase
{
    /** @var \App\Http\Requests\CommentPostRequest */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new CommentPostRequest;
    }

    /**
     * @test
     */
    public function authorize(): void
    {
        // Test ohne authentifizierten Benutzer
        $actual = $this->subject->authorize();
        $this->assertFalse($actual);

        // Test mit authentifiziertem Benutzer
        $user = User::factory()->create();
        $this->actingAs($user);
        $actual = $this->subject->authorize();
        $this->assertTrue($actual);
    }

    /**
     * @test
     */
    public function rules(): void
    {
        $actual = $this->subject->rules();

        $this->assertValidationRules([
            'comment' => [
                'required',
                'string',
            ],
        ], $actual);
    }
}
