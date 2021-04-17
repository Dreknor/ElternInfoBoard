<?php

namespace Tests\Unit\Http\Requests;

use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Tests\FormRequestTestCase;
use Tests\TestCase;

/**
 * @see \App\Http\Requests\CreateGroupRequest
 */
class CreateGroupRequestTest extends FormRequestTestCase
{
    use RefreshDatabase;

    /** @var \App\Http\Requests\CreateGroupRequest */
    private $subject;
    private $rules;
    private $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new \App\Http\Requests\CreateGroupRequest();
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
        $user->givePermissionTo('view groups');
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
            'name' => [
                'required',
                'string',
            ],
            'bereich' => [
                'nullable',
                'alpha_dash',
            ],
            'protected' => [
                'sometimes',
                'boolean'
            ],
        ], $actual);
    }


    /**
     * @test
     */
    public function name_is_requried()
    {
        $string = Str::random();
        $this->assertFalse($this->validateField('name', '', $this->rules));
        $this->assertFalse($this->validateField('name', [$string], $this->rules));
        $this->assertTrue($this->validateField('name', $string, $this->rules));
    }

    /**
     * @test
     */
    public function Bereich_is_requried()
    {
        $text = Str::random(50);
        $this->assertTrue($this->validateField('bereich', '', $this->rules));
        $this->assertFalse($this->validateField('bereich', [$text], $this->rules));
        $this->assertFalse($this->validateField('bereich', 'Test\Group', $this->rules));
        $this->assertTrue($this->validateField('bereich', $text, $this->rules));

    }

    /**
     * @test
     */
    public function protected_is_valid()
    {
        $this->assertTrue($this->validateField('protected', '', $this->rules));
        $this->assertFalse($this->validateField('protected', [1,0], $this->rules));
        $this->assertFalse($this->validateField('protected', rand(-5,-1), $this->rules));
        $this->assertFalse($this->validateField('protected', rand(2,1000), $this->rules));
        $this->assertFalse($this->validateField('protected', 0.1, $this->rules));
        $this->assertFalse($this->validateField('protected', 0.8, $this->rules));


        $this->assertTrue($this->validateField('protected', 1, $this->rules));
        $this->assertTrue($this->validateField('protected', 0, $this->rules));
        $this->assertTrue($this->validateField('protected', true, $this->rules));
        $this->assertTrue($this->validateField('protected', false, $this->rules));
    }
}
