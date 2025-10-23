<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\CreateListeRequest;
use App\Model\User;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * @see \App\Http\Requests\CreateListeRequest
 */
class CreateListeRequestTest extends TestCase
{
    /** @var \App\Http\Requests\CreateListeRequest */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new CreateListeRequest();
    }

    /**
     * @test
     */
    public function authorize()
    {
        $user = User::factory()->create();
        Permission::findOrCreate('create terminliste');
        $user->givePermissionTo('create terminliste');
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
