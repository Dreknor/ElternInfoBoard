<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\createDiscussionRequest;
use App\Model\User;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * @see \App\Http\Requests\createDiscussionRequest
 */
class createDiscussionRequestTest extends TestCase
{
    /** @var \App\Http\Requests\createDiscussionRequest */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new createDiscussionRequest();
    }

    /**
     * @test
     */
    public function authorize()
    {
        $user = User::factory()->create();
        Permission::findOrCreate('view elternrat');
        $user->givePermissionTo('view elternrat');
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
