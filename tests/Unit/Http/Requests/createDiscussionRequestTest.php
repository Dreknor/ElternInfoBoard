<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\createDiscussionRequest;
use App\Model\User;
use Faker\Provider\Text;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Tests\FormRequestTestCase;
use Tests\TestCase;

/**
 * @see \App\Http\Requests\createDiscussionRequest
 */
class createDiscussionRequestTest extends FormRequestTestCase
{
    use RefreshDatabase;

    private $subject;
    private $rules;
    private $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new createDiscussionRequest();
        $this->rules = $this->subject->rules();

    }

    /**
     * @test
     */
    public function not_authorize_without_login()
    {

        $actual = $this->subject->authorize();
        $this->assertFalse($actual);

    }
    /**
     * @test
     */
    public function not_authorize_without_permission()
    {
        $user = User::factory()->make();
        $this->actingAs($user);
        $actual = $this->subject->authorize();
        $this->assertFalse($actual);
    }

    /**
     * @test
     */
    public function authorize()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view elternrat');
        $this->actingAs($user);

        $actual = $this->subject->authorize();
        $this->assertTrue($actual);

    }

    /**
     * @test
     */
    public function rules()
    {

        $actual = $this->subject->rules();

        $this->assertValidationRules([
            'header' => [
                'required',
                'string',
            ],
            'text' => [
                'required',
            ],
            'sticky' => [
                'required',
                'boolean',
            ],
        ], $actual);
    }


    /**
     * @test
     */
    public function header_is_requried()
    {
        $string = Str::random();
        $this->assertFalse($this->validateField('header', '', $this->rules));
        $this->assertFalse($this->validateField('header', [$string], $this->rules));
        $this->assertTrue($this->validateField('header', $string, $this->rules));
    }

    /**
     * @test
     */
    public function text_is_requried()
    {
        $text = Str::random(500);
        $this->assertFalse($this->validateField('text', '', $this->rules));
        $this->assertFalse($this->validateField('text', [$text], $this->rules));
        $this->assertTrue($this->validateField('text', $text, $this->rules));
    }

    /**
     * @test
     */
    public function sticky_is_requried()
    {
        $this->assertFalse($this->validateField('sticky', '', $this->rules));
        $this->assertFalse($this->validateField('sticky', [1,0], $this->rules));
        $this->assertFalse($this->validateField('sticky', rand(-5,-1), $this->rules));
        $this->assertFalse($this->validateField('sticky', rand(2,1000), $this->rules));
        $this->assertFalse($this->validateField('sticky', 0.1, $this->rules));
        $this->assertFalse($this->validateField('sticky', 0.8, $this->rules));


        $this->assertTrue($this->validateField('sticky', 1, $this->rules));
        $this->assertTrue($this->validateField('sticky', 0, $this->rules));
        $this->assertTrue($this->validateField('sticky', true, $this->rules));
        $this->assertTrue($this->validateField('sticky', false, $this->rules));
    }
}
