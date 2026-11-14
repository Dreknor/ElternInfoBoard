<?php

namespace Tests\Feature\Schuelerkonten;

use App\Model\Child;
use App\Model\Group;
use App\Model\Post;
use App\Model\User;
use App\Services\Students\StudentAccountService;
use App\Settings\SchuelerSetting;
use App\Support\StudentAccounts;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\ActivatesModules;
use Tests\TestCase;

/**
 * App-API der Schülerkonten (Paket S-12).
 */
class StudentApiTest extends TestCase
{
    use ActivatesModules;

    private User $student;

    private Group $class;

    private function setUpStudent(bool $usernameLogin = true): void
    {
        config(['modules.unlocked' => [StudentAccounts::MODULE]]);
        $this->activateModule(StudentAccounts::MODULE);
        $this->class = Group::factory()->create();
        $child = Child::factory()->create(['class_id' => $this->class->id]);
        [$this->student] = app(StudentAccountService::class)->createForChild($child, ['username' => 'kind.api']);
        $this->student->forceFill(['password' => Hash::make('geheim-12345')])->save();

        $s = app(SchuelerSetting::class);
        $s->login_benutzername = $usernameLogin;
        $s->save();
    }

    private function token(): string
    {
        return $this->postJson('/api/v1/student/token', ['username' => 'kind.api', 'password' => 'geheim-12345', 'device_name' => 'Tablet'])
            ->assertOk()->json('data.token');
    }

    /** @test */
    public function api_does_not_exist_while_module_is_off(): void
    {
        $this->postJson('/api/v1/student/token', ['username' => 'x', 'password' => 'y', 'device_name' => 'z'])->assertNotFound();
    }

    /** @test */
    public function token_login_requires_username_login_setting(): void
    {
        $this->setUpStudent(false);
        $this->postJson('/api/v1/student/token', ['username' => 'kind.api', 'password' => 'geheim-12345', 'device_name' => 'T'])->assertNotFound();
    }

    /** @test */
    public function student_token_has_limited_ability_and_expires(): void
    {
        $this->setUpStudent();
        $this->token();

        $token = $this->student->tokens()->first();
        $this->assertSame([StudentAccounts::TOKEN_ABILITY], $token->abilities);
        $this->assertTrue($token->expires_at->between(now()->addDays(29), now()->addDays(31)));
    }

    /** @test */
    public function student_token_reaches_student_endpoints_only(): void
    {
        $this->setUpStudent();
        $token = $this->token();
        $post = Post::factory()->create(['header' => 'Hallo Klasse', 'released' => 1, 'external' => 0, 'zielgruppe' => 'schueler']);
        $post->groups()->attach($this->class->id);

        $auth = ['Authorization' => 'Bearer '.$token];
        $this->getJson('/api/v1/student/bootstrap', $auth)->assertOk()->assertJsonPath('data.account_type', 'student');
        $this->getJson('/api/v1/student/posts', $auth)->assertOk()->assertJsonPath('data.0.titel', 'Hallo Klasse');
        $this->getJson('/api/v1/student/posts/'.$post->id, $auth)->assertOk();
        foreach (['termine', 'vertretungsplan', 'stundenplan', 'ags'] as $path) {
            $this->getJson('/api/v1/student/'.$path, $auth)->assertOk();
        }

        foreach (['/api/v1/bootstrap', '/api/v1/me', '/api/v1/posts', '/api/v1/parent/dashboard', '/api/user/settings'] as $uri) {
            $this->assertContains($this->getJson($uri, $auth)->status(), [401, 403, 404], $uri);
        }
    }

    /** @test */
    public function parent_tokens_cannot_use_student_endpoints(): void
    {
        $this->setUpStudent();
        $parent = User::factory()->create();
        $token = $parent->createToken('App')->plainTextToken;

        $this->getJson('/api/v1/student/bootstrap', ['Authorization' => 'Bearer '.$token])->assertNotFound();
    }

    /** @test */
    public function locking_the_account_invalidates_tokens(): void
    {
        $this->setUpStudent();
        $token = $this->token();
        app(StudentAccountService::class)->deactivate($this->student);

        $this->getJson('/api/v1/student/bootstrap', ['Authorization' => 'Bearer '.$token])->assertUnauthorized();
    }

    /** @test */
    public function code_can_be_redeemed_in_the_app(): void
    {
        $this->setUpStudent();
        $code = app(StudentAccountService::class)->issueCode($this->student);

        $this->postJson('/api/v1/student/code', ['username' => 'kind.api', 'code' => 'FALSCH00', 'password' => 'neu-12345678', 'password_confirmation' => 'neu-12345678', 'device_name' => 'T'])
            ->assertStatus(422);

        $this->postJson('/api/v1/student/code', ['username' => 'kind.api', 'code' => $code, 'password' => 'neu-12345678', 'password_confirmation' => 'neu-12345678', 'device_name' => 'T'])
            ->assertOk()->assertJsonPath('data.account_type', 'student');
        $this->assertTrue(Hash::check('neu-12345678', $this->student->fresh()->password));
    }

    /** @test */
    public function instance_reports_student_login_only_when_enabled(): void
    {
        $this->getJson('/api/v1/instance')->assertOk()->assertJsonMissingPath('data.auth.student');

        $this->setUpStudent();
        \Illuminate\Support\Facades\Cache::flush();
        $this->getJson('/api/v1/instance')->assertOk()->assertJsonPath('data.auth.student.password', true);
    }

    /** @test */
    public function sso_exchange_issues_a_student_token(): void
    {
        $this->setUpStudent();
        $code = 'abc'.str_repeat('x', 45);
        \Illuminate\Support\Facades\Cache::put("app_login_code:{$code}", ['user_id' => $this->student->id, 'challenge' => null, 'student' => true], now()->addMinutes(5));

        $token = $this->postJson('/api/v1/auth/exchange', ['code' => $code, 'device_name' => 'T'])
            ->assertOk()->assertJsonPath('data.account_type', 'student')->json('data.token');

        $this->getJson('/api/v1/student/bootstrap', ['Authorization' => 'Bearer '.$token])->assertOk();
        $this->assertSame([StudentAccounts::TOKEN_ABILITY], $this->student->tokens()->first()->abilities);
    }

    /** @test */
    public function student_token_can_be_refreshed(): void
    {
        $this->setUpStudent();
        $token = $this->token();
        $new = $this->postJson('/api/v1/student/token/refresh', [], ['Authorization' => 'Bearer '.$token])->assertOk()->json('data.token');

        $this->assertNotSame($token, $new);
        $this->assertSame(1, $this->student->tokens()->count());
    }
}
