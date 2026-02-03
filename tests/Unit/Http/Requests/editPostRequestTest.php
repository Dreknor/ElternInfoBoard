<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\editPostRequest;
use App\Model\Post;
use App\Model\User;
use Tests\TestCase;

/**
 * @see \App\Http\Requests\editPostRequest
 */
class editPostRequestTest extends TestCase
{
    /** @var \App\Http\Requests\editPostRequest */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new editPostRequest;
    }

    /**
     * @test
     */
    public function authorize()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['author' => $user->id]);

        $this->actingAs($user);

        // Erstelle einen partiellen Mock für die authorize-Methode
        $request = $this->getMockBuilder(editPostRequest::class)
            ->onlyMethods(['route'])
            ->getMock();

        $request->expects($this->any())
            ->method('route')
            ->with($this->equalTo('posts'))
            ->willReturn($post);

        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $actual = $request->authorize();
        $this->assertTrue($actual);
    }

    /**
     * @test
     */
    public function rules()
    {
        $actual = $this->subject->rules();

        $this->assertIsArray($actual);
        // Validierungsregeln werden durch die Request-Klasse definiert
    }
}
