<?php

namespace Tests\Unit\Http\Requests;

use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * @see \App\Http\Requests\CommentPostRequest
 */
class CommentPostRequestTest extends TestCase
{
    use RefreshDatabase;
    /** @var \App\Http\Requests\CommentPostRequest */
    private $subject;
    private $rules;
    private $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = $this->app['validator'];
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
        $actual = $this->subject->rules();

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

        $this->assertFalse($this->validateField('comment', ''));
        $this->assertFalse($this->validateField('comment', ['Hallo']));
        $this->assertTrue($this->validateField('comment', 'Hallo Welt'));
    }


    protected function getFieldValidator($field, $value)
    {
        return $this->validator->make(
            [$field => $value],
            [$field => $this->rules[$field]]
        );
    }

    protected function validateField($field, $value)
    {
        return $this->getFieldValidator($field, $value)->passes();
    }
}
