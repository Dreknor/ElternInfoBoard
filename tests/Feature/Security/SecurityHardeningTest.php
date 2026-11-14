<?php

namespace Tests\Feature\Security;

use App\Model\Comment;
use App\Model\Discussion;
use App\Model\Group;
use App\Model\Post;
use App\Model\Rueckmeldungen;
use App\Model\User;
use App\Model\VertretungsplanAbsence;
use App\Model\VertretungsplanWeek;
use App\Support\SafeHtml;
use App\Support\UploadGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Regressionstests zum Sicherheitsaudit: jede Prüfung entspricht einem vorher offenen Angriffsweg.
 */
class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app[PermissionRegistrar::class]->forgetCachedPermissions();
        foreach (['edit posts', 'manage rueckmeldungen', 'view elternrat', 'delete elternrat file', 'create posts'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }

    private function parent(): User
    {
        return User::factory()->create(['password_changed_at' => now(), 'changePassword' => false]);
    }

    private function postFor(User $author, ?Group $group = null): Post
    {
        $post = Post::factory()->create(['author' => $author->id, 'released' => 1, 'external' => 0, 'archiv_ab' => now()->addWeek()]);
        if ($group) {
            $post->groups()->attach($group);
        }

        return $post;
    }

    // ── Vertretungsplan-/Stundenplan-API ─────────────────────────────────────

    /** @test */
    public function vertretungsplan_week_and_absence_endpoints_require_the_api_key(): void
    {
        config(['app.api_key' => 'geheim']);
        $week = VertretungsplanWeek::create(['week' => now()->startOfWeek()->toDateString(), 'type' => 'A']);
        $absence = VertretungsplanAbsence::create(['absence_id' => 7, 'name' => 'Frau X', 'start_date' => now(), 'end_date' => now()]);

        $this->deleteJson('/api/week/'.$week->id)->assertForbidden();
        $this->deleteJson('/api/absences/7')->assertForbidden();
        $this->postJson('/api/absences', ['id' => 8, 'name' => 'Herr Y', 'start_date' => '2026-10-26', 'end_date' => '2026-10-27'])->assertForbidden();

        $this->assertModelExists($week);
        $this->assertModelExists($absence);
        $this->assertDatabaseMissing('vertretungsplan_absences', ['absence_id' => 8]);

        $this->deleteJson('/api/absences/7', [], ['X-API-Key' => 'geheim'])->assertOk();
        $this->assertModelMissing($absence);
    }

    /** @test */
    public function stundenplan_status_api_requires_the_api_key(): void
    {
        config(['app.api_key' => 'geheim']);

        $this->getJson('/api/stundenplan/status')->assertForbidden();
    }

    // ── Tokens deaktivierter Konten ──────────────────────────────────────────

    /** @test */
    public function deactivated_users_lose_api_access_and_get_no_new_token(): void
    {
        $user = $this->parent();
        $token = $user->createToken('App')->plainTextToken;
        $this->withToken($token)->getJson('/api/v1/me')->assertOk();

        $user->forceFill(['is_active' => false])->save();
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/v1/me')->assertUnauthorized();

        $this->postJson('/api/token/create', ['email' => $user->email, 'password' => 'password', 'device_name' => 'x'])
            ->assertStatus(422);
    }

    /** @test */
    public function web_link_rejects_protocol_relative_targets(): void
    {
        $token = $this->parent()->createToken('App')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/auth/web-link', ['path' => '//evil.example/x'])->assertStatus(422);
        $this->withToken($token)->postJson('/api/v1/auth/web-link', ['path' => '/datenschutz'])->assertOk();
    }

    // ── Uploads ──────────────────────────────────────────────────────────────

    /** @test */
    public function image_upload_to_a_post_needs_an_open_image_feedback_on_a_visible_post(): void
    {
        Storage::fake('public');
        Storage::fake('media');
        Mail::fake();
        $group = Group::factory()->create();
        $author = $this->parent();
        $post = $this->postFor($author, $group);
        Rueckmeldungen::factory()->create(['post_id' => $post->id, 'type' => 'bild', 'ende' => now()->addWeek()]);

        $stranger = $this->parent();
        $this->actingAs($stranger)->post('/rueckmeldung/'.$post->id.'/saveFile', [
            'name' => 'x', 'files' => UploadedFile::fake()->image('foto.jpg'),
        ]);
        $this->assertSame(0, $post->fresh()->getMedia('images')->count());

        $member = $this->parent();
        $member->groups()->attach($group);
        $this->actingAs($member)->post('/rueckmeldung/'.$post->id.'/saveFile', [
            'name' => 'x', 'files' => UploadedFile::fake()->createWithContent('seite.html', '<script>alert(1)</script>'),
        ])->assertSessionHasErrors('files');
        $this->assertSame(0, $post->fresh()->getMedia('images')->count());

        $this->actingAs($member)->post('/rueckmeldung/'.$post->id.'/saveFile', [
            'name' => 'x', 'files' => UploadedFile::fake()->image('foto.jpg'),
        ]);
        $this->assertSame(1, $post->fresh()->getMedia('images')->count());
    }

    /** @test */
    public function upload_guard_blocks_active_and_executable_file_types(): void
    {
        foreach (['seite.html', 'bild.svg', 'shell.php', 'shell.php.jpg', 'x.PHTML', '.htaccess', 'daten.xml', 'skript.js'] as $name) {
            $this->assertTrue(UploadGuard::isBlocked($name), $name);
        }
        $this->assertTrue(UploadGuard::isBlocked('harmlos.txt', 'text/html'));

        foreach (['brief.pdf', 'foto.JPG', 'liste.xlsx', 'Elternbrief v1.2.docx', 'notiz.txt'] as $name) {
            $this->assertFalse(UploadGuard::isBlocked($name, 'application/pdf'), $name);
        }
    }

    /** @test */
    public function media_library_refuses_blocked_files_on_every_upload_path(): void
    {
        Storage::fake('public');
        Storage::fake('media');
        $post = $this->postFor($this->parent());

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $post->addMedia(UploadedFile::fake()->createWithContent('bild.svg', '<svg onload="alert(1)"/>'))->toMediaCollection('images');
    }

    // ── Stored XSS ───────────────────────────────────────────────────────────

    /** @test */
    public function safe_html_removes_scripts_but_keeps_editor_formatting(): void
    {
        $clean = SafeHtml::clean('<p style="color:red"><strong>Hallo</strong> <a href="https://example.org" target="_blank">Link</a></p>'
            .'<img src="x" onerror="alert(1)"><script>alert(2)</script><a href="javascript:alert(3)">x</a>'
            .'<iframe src="https://www.youtube.com/embed/abc"></iframe><iframe src="https://evil.example/"></iframe>');

        $this->assertStringContainsString('<strong>Hallo</strong>', $clean);
        $this->assertStringContainsString('style="color:', $clean);
        $this->assertStringContainsString('href="https://example.org"', $clean);
        $this->assertStringContainsString('youtube.com/embed/abc', $clean);
        $this->assertStringNotContainsString('onerror', $clean);
        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringNotContainsString('evil.example', $clean);
        $this->assertSame('Nur Text', SafeHtml::clean('Nur Text'));
    }

    // ── Rechteprüfung bei Beiträgen und Rückmeldungen ────────────────────────

    /** @test */
    public function parents_cannot_delete_or_change_feedback_of_foreign_posts(): void
    {
        $author = $this->parent();
        $post = $this->postFor($author);
        $rueckmeldung = Rueckmeldungen::factory()->create(['post_id' => $post->id, 'type' => 'email', 'commentable' => false]);
        $parent = $this->parent();

        $this->actingAs($parent)->deleteJson('/rueckmeldung/'.$rueckmeldung->id)->assertForbidden();
        $this->actingAs($parent)->delete('/rueckmeldungen/'.$post->id)->assertForbidden();
        $this->actingAs($parent)->get('/rueckmeldungen/'.$rueckmeldung->id.'/commentable')->assertForbidden();
        $this->actingAs($parent)->get('/rueckmeldungen/'.$post->id.'/createDiskussion')->assertForbidden();

        $this->assertModelExists($rueckmeldung);
        $this->assertFalse((bool) $rueckmeldung->fresh()->commentable);

        $this->actingAs($author)->deleteJson('/rueckmeldung/'.$rueckmeldung->id)->assertOk();
    }

    /** @test */
    public function parents_cannot_copy_foreign_posts_or_comment_without_permission(): void
    {
        $post = $this->postFor($this->parent());
        $post->forceFill(['archiv_ab' => now()->subMonths(2)])->save();
        $parent = $this->parent();
        $count = Post::count();

        $this->actingAs($parent)->get('/posts/touch/'.$post->id)->assertForbidden();
        $this->assertSame($count, Post::count());

        $this->actingAs($parent)->post('/nachricht/'.$post->id.'/comment/create', ['comment' => 'Hallo'])->assertForbidden();
    }

    // ── Elternrat ────────────────────────────────────────────────────────────

    /** @test */
    public function elternrat_members_cannot_edit_foreign_discussions_or_delete_post_comments(): void
    {
        $owner = $this->parent();
        $member = $this->parent();
        $member->givePermissionTo('view elternrat');
        $discussion = Discussion::factory()->create(['owner' => $owner->id]);

        $this->actingAs($member)->get('/elternrat/discussion/edit/'.$discussion->id)->assertForbidden();
        $this->actingAs($member)->put('/elternrat/discussion/'.$discussion->id, ['header' => 'X', 'text' => 'Y', 'sticky' => 0])->assertForbidden();

        $postComment = Comment::factory()->create();
        $this->actingAs($member)->delete('/elternrat/comment/'.$postComment->id)->assertNotFound();
        $this->assertModelExists($postComment);

        $foreign = Comment::factory()->create(['commentable_type' => Discussion::class, 'commentable_id' => $discussion->id, 'creator_id' => $owner->id]);
        $this->actingAs($member)->delete('/elternrat/comment/'.$foreign->id)->assertForbidden();
        $this->assertModelExists($foreign);
    }

    // ── Kontaktformular ──────────────────────────────────────────────────────

    /** @test */
    public function contact_form_only_sends_to_listed_staff(): void
    {
        Mail::fake();
        $target = $this->parent();

        $this->actingAs($this->parent())->post('/feedback', ['betreff' => 'Hallo', 'text' => 'Test', 'mitarbeiter' => $target->id]);

        Mail::assertNothingSent();
    }
}
