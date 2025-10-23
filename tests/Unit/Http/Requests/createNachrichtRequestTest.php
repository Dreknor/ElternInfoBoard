<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\createNachrichtRequest;
use App\Model\User;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * @see \App\Http\Requests\createNachrichtRequest
 */
class createNachrichtRequestTest extends TestCase
{
    /** @var \App\Http\Requests\createNachrichtRequest */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new createNachrichtRequest();
    }

    /**
     * @test
     */
    public function authorize()
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
    public function rules()
    {
        $actual = $this->subject->rules();

        $this->assertIsArray($actual);
        // Validierungsregeln werden durch die Request-Klasse definiert
    }

    // test cases...
}
