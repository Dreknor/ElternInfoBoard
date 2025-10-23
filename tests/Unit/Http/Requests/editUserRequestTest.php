<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\editUserRequest;
use App\Model\User;
use Tests\TestCase;

/**
 * @see \App\Http\Requests\editUserRequest
 */
class editUserRequestTest extends TestCase
{
    /** @var \App\Http\Requests\editUserRequest */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new editUserRequest();
    }

    /**
     * @test
     */
    public function authorize()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Set user resolver for the subject
        $this->subject->setUserResolver(function () use ($user) {
            return $user;
        });

        $actual = $this->subject->authorize();
        $this->assertTrue($actual);
    }

    /**
     * @test
     */
    public function rules()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $actual = $this->subject->rules();

        $this->assertIsArray($actual);
        $this->assertArrayHasKey('name', $actual);
        // Validierungsregeln werden durch die Request-Klasse definiert
    }

    // test cases...
}
