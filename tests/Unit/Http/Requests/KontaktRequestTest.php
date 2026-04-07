<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\KontaktRequest;
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

        $this->subject = new KontaktRequest;
    }

    /**
     * @test
     */
    /**
     * @test
     */
    public function authorize(): void
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
    public function rules(): void
    {
        $actual = $this->subject->rules();

        $this->assertIsArray($actual);
        // Validierungsregeln werden durch die Request-Klasse definiert
    }

    // test cases...
}
