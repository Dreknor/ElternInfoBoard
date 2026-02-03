<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\createUserRequest;
use App\Model\User;
use Spatie\Permission\Models\Permission;
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

        $this->subject = new createUserRequest;
    }

    /**
     * @test
     */
    public function authorize(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('edit user');
        $user->givePermissionTo('edit user');
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
