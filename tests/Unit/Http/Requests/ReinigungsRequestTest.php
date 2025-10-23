<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\ReinigungsRequest;
use App\Model\User;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * @see \App\Http\Requests\ReinigungsRequest
 */
class ReinigungsRequestTest extends TestCase
{
    /** @var \App\Http\Requests\ReinigungsRequest */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new ReinigungsRequest();
    }

    /**
     * @test
     */
    public function authorize()
    {
        $user = User::factory()->create();
        Permission::findOrCreate('edit reinigung');
        $user->givePermissionTo('edit reinigung');
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
