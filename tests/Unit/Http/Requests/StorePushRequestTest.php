<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\StorePushRequest;
use Tests\TestCase;

/**
 * @see \App\Http\Requests\StorePushRequest
 */
class StorePushRequestTest extends TestCase
{
    /** @var \App\Http\Requests\StorePushRequest */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new StorePushRequest;
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
