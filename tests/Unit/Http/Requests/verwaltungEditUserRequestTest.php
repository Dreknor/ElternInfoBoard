<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\verwaltungEditUserRequest;
use App\Model\User;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * @see \App\Http\Requests\verwaltungEditUserRequest
 */
class verwaltungEditUserRequestTest extends TestCase
{
    /** @var \App\Http\Requests\verwaltungEditUserRequest */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new verwaltungEditUserRequest;
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
    public function rules(): void
    {
        $user = User::factory()->create();
        $targetUser = User::factory()->create();
        $this->actingAs($user);

        // Mock the user parameter
        $this->subject->merge(['user' => $targetUser]);
        $this->subject->user = $targetUser;

        $actual = $this->subject->rules();

        $this->assertIsArray($actual);
        $this->assertArrayHasKey('name', $actual);
        // Validierungsregeln werden durch die Request-Klasse definiert
    }

    // test cases...
}
