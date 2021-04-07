<?php

namespace Tests\Unit\Http\Requests;

use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\FormRequestTestCase;
use Tests\TestCase;

/**
 * @see \App\Http\Requests\CommentPostRequest
 */
class CommentPostRequestTest extends FormRequestTestCase
{
    use RefreshDatabase;
    /** @var \App\Http\Requests\CommentPostRequest */
    private $subject;
    private $rules;
    private $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new \App\Http\Requests\CommentPostRequest();
        $this->rules = $this->subject->rules();

    }

    /**
     * @test
     */
    public function authorize()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $actual = $this->subject->authorize();

        $this->assertTrue($actual);
    }

    /**
     * @test
     */
    public function do_not_authorize_if_not_logged_in()
    {
        $actual = $this->subject->authorize();
        $this->assertFalse($actual);
    }

    /**
     * @test
     */
    public function rules()
    {
        $actual = $this->rules;

        $this->assertValidationRules([
            'comment' => [
                'required',
                'string',
            ],
        ], $actual);
    }

    /**
     * @test
     */
    public function comment_is_requried()
    {

        $comment = "";

        $this->assertFalse($this->validateField('comment', '', $this->rules));
        $this->assertFalse($this->validateField('comment', ['Hallo'], $this->rules));
        $this->assertTrue($this->validateField('comment', 'Hallo Welt', $this->rules));
    }
}
