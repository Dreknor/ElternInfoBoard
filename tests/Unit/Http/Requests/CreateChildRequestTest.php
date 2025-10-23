<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\CreateChildRequest;

use App\Model\User;

use Tests\TestCase;

/**
 * @see \App\Http\Requests\CreateChildRequest
 */
class CreateChildRequestTest extends TestCase
{
    /** @var \App\Http\Requests\CreateChildRequest */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new CreateChildRequest();
    }

    /**
     * @test
     */
        /**
     * @test
     */
    public function authorize()
    {
        // Test ohne authentifizierten Benutzer
        $actual = $this->subject->authorize();
        $this->assertFalse($actual);

        // Test mit authentifiziertem Benutzer
        $user = \App\Model\User::factory()->create();
        $this->actingAs($user);
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
