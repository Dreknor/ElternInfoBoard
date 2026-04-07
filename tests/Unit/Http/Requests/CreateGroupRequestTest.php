<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\CreateGroupRequest;
use App\Model\User;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * @see \App\Http\Requests\CreateGroupRequest
 */
class CreateGroupRequestTest extends TestCase
{
    /** @var \App\Http\Requests\CreateGroupRequest */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new CreateGroupRequest;
    }

    /**
     * @test
     */
    public function authorize(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('edit groups');
        $user->givePermissionTo('edit groups');
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
