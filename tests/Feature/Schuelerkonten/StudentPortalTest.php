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
use Spatie\Permission\Models\Permission;
use Tests\Concerns\ActivatesModules;
use Tests\TestCase;

/**
 * Schülerbereich, Anmeldung und Abschottung (Pakete S-03, S-05, S-06).
 */
class StudentPortalTest extends TestCase
{
    use ActivatesModules;

    private Group $class;

    private Child $child;

    private User $student;

    protected function enableModule(): void
    {
        config(['modules.unlocked' => [StudentAccounts::MODULE]]);
        $this->activateModule(StudentAccounts::MODULE);
        StudentAccounts::flush();
    }

    private function setUpStudent(): void
    {
        $this->enableModule();
        $this->class = Group::factory()->create(['name' => '4a']);
        $this->child = Child::factory()->create(['first_name' => 'Jörg', 'last_name' => 'Groß-Müller', 'class_id' => $this->class->id]);
        [$this->student] = app(StudentAccountService::class)->createForChild($this->child);
        $this->student->forceFill(['password' => Hash::make('geheim-12345')])->save();
    }

    private function enableUsernameLogin(): void
    {
        $s = app(SchuelerSetting::class);
        $s->login_benutzername = true;
        $s->save();
    }

    /** @test */
    public function username_follows_the_concept(): void
    {
        $this->setUpStudent();
        $this->assertSame('joerg.gross-mueller', $this->student->username);

        $other = Child::factory()->create(['first_name' => 'Jörg', 'last_name' => 'Groß-Müller']);
        [$second] = app(StudentAccountService::class)->createForChild($other);
        $this->assertSame('joerg.gross-mueller2', $second->username);
    }

    /** @test */
    public function student_routes_do_not_exist_while_module_is_off(): void
    {
        $this->get('/schueler/anmelden')->assertNotFound();
        $this->get('/verwaltung/schuelerkonten')->assertNotFound();
        $this->get('/login')->assertDontSee('Schülerkonto')->assertDontSee('schueler/anmelden');
    }

    /** @test */
    public function username_login_is_off_by_default_and_works_when_enabled(): void
    {
        $this->setUpStudent();
        $this->get('/schueler/anmelden')->assertNotFound();

        $this->enableUsernameLogin();
        $this->get('/login')->assertSee(route('student.login'), false);
        $this->get('/schueler/anmelden')->assertOk();

        $this->post('/schueler/anmelden', ['username' => 'joerg.gross-mueller', 'password' => 'falsch'])
            ->assertSessionHasErrors('username');
        $this->assertGuest();

        $this->post('/schueler/anmelden', ['username' => 'Joerg.Gross-Mueller', 'password' => 'geheim-12345'])
            ->assertRedirect(route('student.home'));
        $this->assertAuthenticatedAs($this->student);
    }

    /** @test */
    public function students_cannot_use_the_parent_login(): void
    {
        $this->setUpStudent();
        $this->student->forceFill(['email' => 'x@example.org'])->save();

        $this->post('/login', ['email' => 'joerg.gross-mueller', 'password' => 'geheim-12345']);
        $this->assertGuest();
    }

    /** @test */
    public function inactive_student_cannot_log_in(): void
    {
        $this->setUpStudent();
        $this->enableUsernameLogin();
        app(StudentAccountService::class)->deactivate($this->student);

        $this->post('/schueler/anmelden', ['username' => 'joerg.gross-mueller', 'password' => 'geheim-12345'])
            ->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    /** @test */
    public function student_pages_render(): void
    {
        $this->setUpStudent();
        $this->actingAs($this->student);

        foreach (['student.home', 'student.posts', 'student.termine', 'student.vertretungsplan', 'student.stundenplan', 'student.ags', 'student.profile'] as $route) {
            $this->get(route($route))->assertOk();
        }
    }

    /** @test */
    public function student_is_redirected_from_the_parent_start_page(): void
    {
        $this->setUpStudent();
        $this->actingAs($this->student)->get('/')->assertRedirect(route('student.home'));
        $this->actingAs($this->student)->get('/home')->assertRedirect(route('student.home'));
    }

    /** @test */
    public function student_cannot_reach_any_parent_or_admin_route(): void
    {
        $this->setUpStudent();
        $this->actingAs($this->student);

        foreach (['/einstellungen', '/users', '/verwaltung/schuelerkonten', '/termine', '/messenger', '/kinder', '/api/v1/posts'] as $uri) {
            $status = $this->get($uri)->status();
            $this->assertContains($status, [404, 401, 403], "{$uri} liefert {$status}");
        }
    }

    /** @test */
    public function students_see_only_posts_for_students_of_their_class(): void
    {
        $this->setUpStudent();
        $author = User::factory()->create();
        $otherClass = Group::factory()->create();

        $parents = Post::factory()->create(['header' => 'NurEltern', 'author' => $author->id, 'released' => 1, 'external' => 0, 'zielgruppe' => 'eltern']);
        $both = Post::factory()->create(['header' => 'FuerAlle', 'author' => $author->id, 'released' => 1, 'external' => 0, 'zielgruppe' => 'eltern_schueler']);
        $students = Post::factory()->create(['header' => 'NurSchueler', 'author' => $author->id, 'released' => 1, 'external' => 0, 'zielgruppe' => 'schueler']);
        $foreign = Post::factory()->create(['header' => 'AndereKlasse', 'author' => $author->id, 'released' => 1, 'external' => 0, 'zielgruppe' => 'schueler']);
        $draft = Post::factory()->create(['header' => 'Entwurf', 'author' => $author->id, 'released' => 0, 'external' => 0, 'zielgruppe' => 'schueler']);

        foreach ([$parents, $both, $students, $draft] as $p) {
            $p->groups()->attach($this->class->id);
        }
        $foreign->groups()->attach($otherClass->id);

        $this->actingAs($this->student)->get(route('student.posts'))
            ->assertSee('FuerAlle')->assertSee('NurSchueler')
            ->assertDontSee('NurEltern')->assertDontSee('AndereKlasse')->assertDontSee('Entwurf');

        $this->get(route('student.posts.show', $students->id))->assertOk();
        $this->get(route('student.posts.show', $parents->id))->assertNotFound();
        $this->get(route('student.posts.show', $foreign->id))->assertNotFound();
    }

    /** @test */
    public function parents_never_see_student_only_posts(): void
    {
        $this->setUpStudent();
        $author = User::factory()->create();
        $parent = User::factory()->create();
        $parent->groups()->attach($this->class->id);

        $post = Post::factory()->create(['header' => 'NurSchueler', 'author' => $author->id, 'released' => 1, 'external' => 0, 'zielgruppe' => 'schueler']);
        $post->groups()->attach($this->class->id);

        $this->assertFalse(Post::query()->whereKey($post->id)->exists());
        $this->assertTrue(Post::withStudentAudience()->whereKey($post->id)->exists());

        $this->actingAs($parent)->get('/nachrichten/'.$post->id)->assertNotFound();
    }

    /** @test */
    public function one_time_code_sets_a_password(): void
    {
        $this->setUpStudent();
        $this->enableUsernameLogin();
        $code = app(StudentAccountService::class)->issueCode($this->student);

        $this->post('/schueler/code', [
            'username' => 'joerg.gross-mueller',
            'code' => 'falsch-00',
            'password' => 'neues-passwort-1',
            'password_confirmation' => 'neues-passwort-1',
        ])->assertSessionHasErrors();

        $this->post('/schueler/code', [
            'username' => 'joerg.gross-mueller',
            'code' => strtolower($code),
            'password' => 'neues-passwort-1',
            'password_confirmation' => 'neues-passwort-1',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('neues-passwort-1', $this->student->fresh()->password));
        $this->assertNull($this->student->fresh()->student_code_hash, 'Code ist nur einmal gültig');
    }

    /** @test */
    public function students_get_no_rights_even_if_assigned(): void
    {
        $this->setUpStudent();
        Permission::findOrCreate('edit user', 'web');

        $this->expectException(\LogicException::class);
        $this->student->givePermissionTo('edit user');
    }

    /** @test */
    public function student_session_ends_when_module_is_switched_off(): void
    {
        $this->setUpStudent();
        $this->actingAs($this->student)->get(route('student.home'))->assertOk();

        \App\Model\Module::where('setting', StudentAccounts::MODULE)->first()
            ->forceFill(['options' => ['active' => '0', 'rights' => []]])->save();

        $this->get('/schueler')->assertRedirect();
        $this->assertGuest();
    }

    /** @test */
    public function admin_can_manage_accounts(): void
    {
        $this->setUpStudent();
        Permission::findOrCreate('edit user', 'web');
        $admin = User::factory()->create();
        $admin->givePermissionTo('edit user');

        $this->actingAs($admin)->get(route('schuelerkonten.index'))->assertOk()->assertSee('joerg.gross-mueller');

        $this->post(route('schuelerkonten.deactivate', $this->student->id))->assertRedirect();
        $this->assertFalse($this->student->fresh()->is_active);

        $this->post(route('schuelerkonten.activate', $this->student->id))->assertRedirect();
        $this->assertTrue($this->student->fresh()->is_active);

        $this->post(route('schuelerkonten.codes'), ['student_ids' => [$this->student->id]])
            ->assertOk()->assertHeader('content-type', 'application/pdf');

        $this->delete(route('schuelerkonten.destroy', $this->student->id))->assertRedirect();
        $this->assertNull(User::withStudents()->find($this->student->id));
        $this->assertNull($this->child->fresh()->user_id);
    }

    /** @test */
    public function admin_cannot_manage_parents_through_student_routes(): void
    {
        $this->setUpStudent();
        Permission::findOrCreate('edit user', 'web');
        $admin = User::factory()->create();
        $admin->givePermissionTo('edit user');
        $parent = User::factory()->create();

        $this->actingAs($admin)->delete(route('schuelerkonten.destroy', $parent->id))->assertNotFound();
        $this->assertNotNull($parent->fresh());
    }
}
