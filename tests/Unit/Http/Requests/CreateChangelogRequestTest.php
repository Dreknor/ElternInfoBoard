<?php

namespace Tests\Unit\Http\Requests;

use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\FormRequestTestCase;
use Tests\TestCase;

/**
 * @see \App\Http\Requests\CreateChangelogRequest
 */
class CreateChangelogRequestTest extends FormRequestTestCase
{
    use RefreshDatabase;

    private $subject;
    private $rules;
    private $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new \App\Http\Requests\CreateChangelogRequest();
        $this->rules = $this->subject->rules();

    }

    /**
     * @test
     */
    public function no_access_without_permission()
    {

        //no Permission
        $user = User::factory()->create();
        $this->actingAs($user);
        $actual = $this->subject->authorize();

        $this->assertFalse($actual);

    }

    /**
     * @test
     */
    public function access_with_permission()
    {
        //no Permission
        $user = User::factory()->create();
        $user->givePermissionTo('add changelog');

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
                'string',
            ],
            'changeSettings' => [
                'required',
                'min:0',
                'max:1',
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
        $string = Str::random();
        $this->assertFalse($this->validateField('text', '', $this->rules));
        $this->assertFalse($this->validateField('text', [$string], $this->rules));
        $this->assertTrue($this->validateField('text', $string, $this->rules));
    }

    /**
     * @test
     */
    public function changeSettings_is_requried()
    {
        $this->assertFalse($this->validateField('changeSettings', '', $this->rules));
        $this->assertFalse($this->validateField('changeSettings', [1,0], $this->rules));
        $this->assertFalse($this->validateField('changeSettings', rand(-5,-1), $this->rules));
        $this->assertFalse($this->validateField('changeSettings', rand(2,100), $this->rules));
        $this->assertFalse($this->validateField('changeSettings', 0.1, $this->rules));
        $this->assertFalse($this->validateField('changeSettings', 0.8, $this->rules));


        $this->assertTrue($this->validateField('changeSettings', 1, $this->rules));
        $this->assertTrue($this->validateField('changeSettings', 0, $this->rules));
    }
}
