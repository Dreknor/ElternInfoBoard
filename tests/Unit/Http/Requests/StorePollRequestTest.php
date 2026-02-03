<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\StorePollRequest;
use App\Model\User;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * @see \App\Http\Requests\StorePollRequest
 */
class StorePollRequestTest extends TestCase
{
    /** @var \App\Http\Requests\StorePollRequest */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new StorePollRequest;
    }

    /**
     * @test
     */
    public function authorize()
    {
        $user = User::factory()->create();
        Permission::findOrCreate('create polls');
        $user->givePermissionTo('create polls');
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
