<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\createAbfrageRequest;
use App\Model\User;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * @see \App\Http\Requests\createAbfrageRequest
 */
class createAbfrageRequestTest extends TestCase
{
    /** @var \App\Http\Requests\createAbfrageRequest */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new createAbfrageRequest;
    }

    /**
     * @test
     */
    public function authorize(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('create posts');
        $user->givePermissionTo('create posts');
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
