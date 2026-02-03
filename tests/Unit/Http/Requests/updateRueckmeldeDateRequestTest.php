<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\updateRueckmeldeDateRequest;
use App\Model\User;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * @see \App\Http\Requests\updateRueckmeldeDateRequest
 */
class updateRueckmeldeDateRequestTest extends TestCase
{
    /** @var \App\Http\Requests\updateRueckmeldeDateRequest */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new updateRueckmeldeDateRequest;
    }

    /**
     * @test
     */
    public function authorize()
    {
        $user = User::factory()->create();
        Permission::findOrCreate('edit posts');
        $user->givePermissionTo('edit posts');
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
