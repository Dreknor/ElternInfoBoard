<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\ImportLosungenRequest;
use App\Model\User;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * @see \App\Http\Requests\ImportLosungenRequest
 */
class ImportLosungenRequestTest extends TestCase
{
    /** @var \App\Http\Requests\ImportLosungenRequest */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new ImportLosungenRequest();
    }

    /**
     * @test
     */
    public function authorize()
    {
        $user = User::factory()->create();
        Permission::findOrCreate('edit settings');
        $user->givePermissionTo('edit settings');
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
