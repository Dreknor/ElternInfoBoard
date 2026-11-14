<?php

namespace Tests\Feature\Security;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Middleware\RequireSameOrigin;
use App\Model\Comment;
use App\Model\Discussion;
use App\Model\Group;
use App\Model\Poll;
use App\Model\Poll_Option;
use App\Model\Post;
use App\Model\Rueckmeldungen;
use App\Model\Site;
use App\Model\User;
use App\Model\VertretungsplanAbsence;
use App\Model\VertretungsplanWeek;
use App\Support\SafeHtml;
use App\Support\UploadGuard;
use App\Support\Uploads;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
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

    // ── Nachgezogen aus dem Audit vom 09.11.2026 ─────────────────────────────

    private function userWith(string ...$permissions): User
    {
        $user = $this->parent();
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
            $user->givePermissionTo($permission);
        }

        return $user;
    }

    /** @test */
    public function votes_require_access_to_the_post_and_a_valid_option(): void
    {
        $author = $this->parent();
        $post = $this->postFor($author);
        $poll = Poll::factory()->create(['post_id' => $post->id, 'max_number' => 1]);
        $option = Poll_Option::factory()->create(['poll_id' => $poll->id]);
        $foreignOption = Poll_Option::factory()->create(['poll_id' => Poll::factory()->create(['post_id' => $this->postFor($author)->id])->id]);

        $this->actingAs($this->parent())->post("poll/{$post->id}/vote", ["{$poll->id}_answers" => [$option->id]])->assertForbidden();

        // Option einer anderen Umfrage
        $this->actingAs($author)->post("poll/{$post->id}/vote", ["{$poll->id}_answers" => [$foreignOption->id]])
            ->assertSessionHas('Meldung', 'Ungültige Auswahl.');
        $this->assertSame(0, $poll->votes()->count());

        $this->actingAs($author)->post("poll/{$post->id}/vote", ["{$poll->id}_answers" => [$option->id]])
            ->assertSessionHas('type', 'success');
        $this->assertSame(1, $poll->votes()->count());
    }

    /** @test */
    public function upload_rules_only_allow_listed_types_and_reject_active_content(): void
    {
        $check = fn (UploadedFile $file, array $rules) => Validator::make(['f' => $file], ['f' => $rules])->passes();

        $svg = UploadedFile::fake()->createWithContent('bild.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
        $html = UploadedFile::fake()->createWithContent('seite.html', '<html><body><script>alert(1)</script></body></html>');
        // Echte Datei (Fake-Dateien melden den Typ anhand des Namens): HTML-Inhalt mit .pdf-Endung
        $path = tempnam(sys_get_temp_dir(), 'upl');
        file_put_contents($path, '<!DOCTYPE html><html><head><title>x</title></head><body><script>alert(1)</script></body></html>');
        $disguised = new UploadedFile($path, 'rechnung.pdf', null, null, true);
        $php = UploadedFile::fake()->createWithContent('shell.php', '<?php echo 1;');
        $exe = UploadedFile::fake()->create('setup.exe', 10);
        $pdf = UploadedFile::fake()->createWithContent('brief.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF");
        $docx = UploadedFile::fake()->create('Elternbrief.docx', 10);
        $jpg = UploadedFile::fake()->image('foto.jpg');

        $this->assertFalse($check($svg, Uploads::fileRules()));
        $this->assertFalse($check($html, Uploads::fileRules()));
        $this->assertFalse($check($disguised, Uploads::fileRules()), 'HTML mit .pdf-Endung muss abgelehnt werden');
        $this->assertFalse($check($php, Uploads::fileRules()));
        $this->assertFalse($check($exe, Uploads::fileRules()));
        $this->assertTrue($check($pdf, Uploads::fileRules()));
        $this->assertTrue($check($docx, Uploads::fileRules()));
        $this->assertTrue($check($jpg, Uploads::fileRules()));

        $this->assertTrue($check($jpg, Uploads::imageRules()));
        $this->assertFalse($check($svg, Uploads::imageRules()));
        $this->assertFalse($check($pdf, Uploads::imageRules()));
    }

    /** @test */
    public function post_attachments_are_checked_against_the_allowlist(): void
    {
        Storage::fake('media');
        $author = $this->userWith('create posts');
        $post = $this->postFor($author);
        $group = Group::factory()->create();

        $this->actingAs($author)->put('posts/'.$post->id, [
            'header' => 'Ausflug', 'news' => 'Text', 'gruppen' => [$group->id], 'type' => 'info',
            'archiv_ab' => now()->addWeek()->format('Y-m-d'), 'reactable' => 0,
            'collection' => 'files', 'files' => [UploadedFile::fake()->create('setup.exe', 10)],
        ])->assertSessionHasErrors('files.0');

        $this->assertSame(0, $post->fresh()->getMedia('files')->count());
    }

    /** @test */
    public function author_can_upload_header_and_post_images_when_creating_or_editing(): void
    {
        Storage::fake('media');
        Mail::fake();
        $author = $this->userWith('create posts');
        $group = Group::factory()->create();
        $post = $this->postFor($author);
        $payload = [
            'header' => 'Ausflug', 'news' => 'Text', 'gruppen' => [$group->id], 'type' => 'info',
            'archiv_ab' => now()->addWeek()->format('Y-m-d'), 'reactable' => 0,
        ];

        // Ohne Datei (leeres Dateifeld im Formular)
        $this->actingAs($author)->put('posts/'.$post->id, $payload + ['collection' => 'files'])->assertSessionHasNoErrors();

        // Header-Bild – Beitrag hat keine Rückmeldung
        $this->actingAs($author)->put('posts/'.$post->id, $payload + [
            'collection' => 'header', 'files' => [UploadedFile::fake()->image('kopf.jpg')],
        ])->assertSessionHasNoErrors();

        // Beitragsbilder
        $this->actingAs($author)->put('posts/'.$post->id, $payload + [
            'collection' => 'images', 'files' => [UploadedFile::fake()->image('a.png'), UploadedFile::fake()->image('b.webp')],
        ])->assertSessionHasNoErrors();

        $this->assertCount(1, $post->fresh()->getMedia('header'));
        $this->assertCount(2, $post->fresh()->getMedia('images'));
    }

    /** @test */
    public function author_can_add_header_and_post_images_via_upload_route_without_image_feedback(): void
    {
        Storage::fake('media');
        Mail::fake();
        $author = $this->parent();
        $editor = $this->userWith('edit posts');
        $post = $this->postFor($author);

        $this->actingAs($author)
            ->post("rueckmeldung/{$post->id}/saveFile", ['collection' => 'header', 'files' => UploadedFile::fake()->image('kopf.jpg')])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($editor)
            ->post("rueckmeldung/{$post->id}/saveFile", ['collection' => 'images', 'files' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.png')]])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertCount(1, $post->fresh()->getMedia('header'));
        $this->assertCount(2, $post->fresh()->getMedia('images'));

        // Auch für Autoren nur Bilder
        $this->actingAs($author)
            ->post("rueckmeldung/{$post->id}/saveFile", ['collection' => 'header', 'files' => [UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')]])
            ->assertSessionHasErrors();
        $this->assertCount(1, $post->fresh()->getMedia('header'));
        Mail::assertNothingQueued();
    }

    /** @test */
    public function recipients_cannot_set_a_header_image_or_upload_several_files(): void
    {
        Storage::fake('media');
        Mail::fake();
        $group = Group::factory()->create();
        $post = $this->postFor($this->parent(), $group);
        Rueckmeldungen::factory()->create(['post_id' => $post->id, 'type' => 'bild', 'ende' => now()->addWeek()]);
        $member = $this->parent();
        $member->groups()->attach($group);

        $this->actingAs($member)->post("rueckmeldung/{$post->id}/saveFile", [
            'name' => 'x', 'files' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
        ])->assertSessionHasErrors('files');

        $this->actingAs($member)->post("rueckmeldung/{$post->id}/saveFile", [
            'name' => 'x', 'collection' => 'header', 'files' => UploadedFile::fake()->image('a.jpg'),
        ])->assertRedirect();

        $this->assertCount(0, $post->fresh()->getMedia('header'));
        $this->assertCount(1, $post->fresh()->getMedia('images'));
        Mail::assertQueued(\App\Mail\newFilesAddToPost::class);
    }

    /** @test */
    public function media_collection_can_only_be_changed_for_post_files(): void
    {
        Storage::fake('media');
        $editor = $this->userWith('edit posts');
        $post = $this->postFor($editor);
        $media = $post->addMedia(UploadedFile::fake()->image('a.jpg'))->toMediaCollection('images');
        $groupMedia = Group::factory()->create()->addMedia(UploadedFile::fake()->image('b.jpg'))->toMediaCollection('default');

        $this->actingAs($editor)->get("posts/{$media->id}/changeCollection/beliebig")->assertNotFound();
        $this->actingAs($editor)->get("posts/{$groupMedia->id}/changeCollection/files")->assertNotFound();
        $this->assertSame('default', $groupMedia->fresh()->collection_name);

        $this->actingAs($editor)->get("posts/{$media->id}/changeCollection/files")->assertRedirect();
        $this->assertSame('files', $media->fresh()->collection_name);
    }

    /** @test */
    public function pdf_export_embeds_only_images_the_user_may_see_and_no_files_outside_public(): void
    {
        Storage::fake('media');
        $author = $this->parent();
        $own = $this->postFor($author)->addMedia(UploadedFile::fake()->image('eigen.jpg'))->toMediaCollection('images');
        $foreign = $this->postFor($this->parent())->addMedia(UploadedFile::fake()->image('fremd.jpg'))->toMediaCollection('images');

        $this->actingAs($author);
        $controller = app(\App\Http\Controllers\NachrichtenController::class);
        $resolve = fn (string $src) => (new \ReflectionMethod($controller, 'resolveImageSrcToBase64'))->invoke($controller, $src);

        $this->assertStringStartsWith('data:image/jpeg;base64,', (string) $resolve('/image/'.$own->id));
        $this->assertNull($resolve('/image/'.$foreign->id), 'Fremde Datei darf nicht eingebettet werden');
        $this->assertNull($resolve('/api/file/'.$foreign->uuid));
        $this->assertNull($resolve('/../.env'), 'Nur Dateien unterhalb von public/');
        $this->assertNull($resolve('/index.php'), 'Nur Bilder');
    }

    /** @test */
    public function pdf_renderer_may_not_fetch_remote_urls_or_read_the_project(): void
    {
        // Die App-Konfiguration nutzt den Schlüssel „defines“ (hat im Paket Vorrang vor „options“)
        $options = app('dompdf.options');
        $this->assertFalse($options['enable_remote']);
        $this->assertNotContains(realpath(base_path()), (array) $options['chroot']);
        $this->assertContains(realpath(public_path()), (array) $options['chroot']);
    }

    /** @test */
    public function deactivating_an_account_revokes_its_tokens_and_blocks_session_api_access(): void
    {
        $user = $this->parent();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/me')->assertOk();

        $user->update(['is_active' => false]);
        $this->assertSame(0, $user->tokens()->count(), 'Tokens werden beim Deaktivieren widerrufen');
        $this->assertDatabaseHas('security_events', ['event' => 'token.revoked', 'user_id' => $user->id]);

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();

        // API-Aufruf über eine Web-Sitzung (Sanctum „stateful“)
        $this->app['auth']->forgetGuards();
        $this->actingAs($user, 'sanctum')->getJson('/api/me')->assertForbidden()->assertJsonPath('code', 'account_disabled');
    }

    /** @test */
    public function keycloak_email_must_be_verified(): void
    {
        $verified = (object) ['user' => ['email_verified' => true]];
        $unverified = (object) ['user' => ['email_verified' => false]];
        $missing = (object) ['user' => []];

        config(['services.keycloak.require_verified_email' => true]);
        $this->assertTrue(LoginController::keycloakEmailIsVerified($verified));
        $this->assertFalse(LoginController::keycloakEmailIsVerified($unverified));
        $this->assertFalse(LoginController::keycloakEmailIsVerified($missing));

        config(['services.keycloak.require_verified_email' => false]);
        $this->assertTrue(LoginController::keycloakEmailIsVerified($unverified));
    }

    /** @test */
    public function site_restricted_to_a_group_is_hidden_from_other_users(): void
    {
        $member = $this->userWith('view sites');
        $other = $this->userWith('view sites');
        $group = Group::factory()->create();
        $member->groups()->attach($group);

        $site = Site::create(['name' => 'Intern', 'author_id' => $this->parent()->id, 'is_active' => true]);
        $site->groups()->attach($group);

        $this->actingAs($other)->get('sites/'.$site->id)->assertForbidden();
        $this->actingAs($member)->get('sites/'.$site->id)->assertOk();
    }

    /** @test */
    public function same_origin_middleware_blocks_cross_site_requests(): void
    {
        config(['app.enforce_same_origin_in_tests' => true]);
        $middleware = new RequireSameOrigin;
        $next = fn () => response('ok');
        $make = function (array $headers) {
            $request = Request::create('https://eltern.example.org/posts/release/1', 'GET');
            foreach ($headers as $name => $value) {
                $request->headers->set($name, $value);
            }

            return $request;
        };

        $this->assertSame('ok', $middleware->handle($make(['Sec-Fetch-Site' => 'same-origin']), $next)->getContent());
        $this->assertSame('ok', $middleware->handle($make(['Referer' => 'https://eltern.example.org/home']), $next)->getContent());

        foreach ([
            ['Sec-Fetch-Site' => 'cross-site'],
            ['Sec-Fetch-Site' => 'none'],
            ['Referer' => 'https://evil.example.com/'],
            ['Referer' => 'https://eltern.example.org.evil.com/'],
            [],
        ] as $headers) {
            try {
                $middleware->handle($make($headers), $next);
                $this->fail('Anfrage hätte abgelehnt werden müssen: '.json_encode($headers));
            } catch (HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }
        }
    }

    /** @test */
    public function state_changing_get_routes_use_the_same_origin_middleware(): void
    {
        foreach (['posts/release/{posts}', 'posts/touch/{posts}', 'posts/{post}/react/{reaction}', 'modules/modul/{modul}', 'logoutAsUser', 'listen/{liste}/archiv', 'rueckmeldungen/{rueckmeldungen}/commentable'] as $uri) {
            $route = collect(app('router')->getRoutes())->first(fn ($r) => $r->uri() === $uri && in_array('GET', $r->methods(), true));
            $this->assertNotNull($route, $uri);
            $this->assertContains('same_origin', $route->gatherMiddleware(), $uri);
        }
    }

    /** @test */
    public function responses_carry_security_headers(): void
    {
        $response = $this->get('/login');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    /** @test */
    public function impersonation_cannot_escalate_privileges(): void
    {
        $support = $this->userWith('edit user', 'loginAsUser');
        $admin = $this->userWith('edit user', 'loginAsUser', 'edit permission');
        $parent = $this->parent();

        $this->actingAs($support)->post('showUser/'.$admin->id)->assertRedirect();
        $this->assertSame($support->id, auth()->id(), 'Konto mit weitergehenden Rechten darf nicht übernommen werden');

        $this->actingAs($support)->post('showUser/'.$parent->id)->assertRedirect();
        $this->assertSame($parent->id, auth()->id());
    }

    /** @test */
    public function passwordless_login_does_not_reveal_whether_an_account_exists(): void
    {
        $response = $this->post('/login', ['submit' => 'password-less', 'email' => 'niemand@example.org']);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('type', 'success');
    }
}
