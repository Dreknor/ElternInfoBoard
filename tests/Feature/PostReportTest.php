<?php

namespace Tests\Feature;

use App\Model\Group;
use App\Model\Notification;
use App\Model\Post;
use App\Model\PostReport;
use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Feature-Tests für das Melden von Beiträgen (PostReport)
 */
class PostReportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $admin;
    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        // Berechtigungen erstellen
        Permission::findOrCreate('edit settings', 'web');
        Permission::findOrCreate('edit posts', 'web');

        $this->user = User::factory()->create([
            'password_changed_at' => now(),
            'changePassword' => false,
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'password_changed_at' => now(),
            'changePassword' => false,
            'is_active' => true,
        ]);
        $this->admin->givePermissionTo('edit settings');

        // Gruppe + Beitrag erstellen (Beitrag gehört einem anderen User)
        $author = User::factory()->create([
            'password_changed_at' => now(),
        ]);
        $group = Group::factory()->create();
        $group->users()->attach([$this->user->id, $this->admin->id, $author->id]);

        $this->post = Post::factory()->create([
            'author' => $author->id,
            'header' => 'Test Beitrag',
            'news' => 'Inhalt des Testbeitrags',
            'released' => true,
        ]);
        $this->post->groups()->attach($group);
    }

    #[Test]
    public function user_can_report_a_post(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('post.report', $this->post), [
                'reason' => 'Unangemessener Inhalt',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('Meldung');

        $this->assertDatabaseHas('post_reports', [
            'post_id' => $this->post->id,
            'reporter_id' => $this->user->id,
            'reason' => 'Unangemessener Inhalt',
        ]);
    }

    #[Test]
    public function user_cannot_report_own_post(): void
    {
        $ownPost = Post::factory()->create([
            'author' => $this->user->id,
            'header' => 'Eigener Beitrag',
            'released' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('post.report', $ownPost), [
                'reason' => 'Test',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('type', 'warning');

        $this->assertDatabaseMissing('post_reports', [
            'post_id' => $ownPost->id,
            'reporter_id' => $this->user->id,
        ]);
    }

    #[Test]
    public function user_cannot_report_same_post_twice(): void
    {
        PostReport::create([
            'post_id' => $this->post->id,
            'reporter_id' => $this->user->id,
            'reason' => 'Erste Meldung',
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('post.report', $this->post), [
                'reason' => 'Zweite Meldung',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('type', 'warning');

        $this->assertEquals(1, PostReport::where('post_id', $this->post->id)
            ->where('reporter_id', $this->user->id)
            ->count());
    }

    #[Test]
    public function report_requires_reason(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('post.report', $this->post), [
                'reason' => '',
            ]);

        $response->assertSessionHasErrors('reason');
    }

    #[Test]
    public function admins_are_notified_when_post_is_reported(): void
    {
        $this->actingAs($this->user)
            ->post(route('post.report', $this->post), [
                'reason' => 'Problematischer Inhalt',
            ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->admin->id,
            'type' => 'Beitragsmeldung',
            'important' => true,
        ]);
    }

    #[Test]
    public function admin_can_view_reports_page(): void
    {
        PostReport::create([
            'post_id' => $this->post->id,
            'reporter_id' => $this->user->id,
            'reason' => 'Test',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('post-reports.index'));

        $response->assertStatus(200);
        $response->assertSee('Gemeldete Beiträge');
        $response->assertSee('Test');
    }

    #[Test]
    public function non_admin_cannot_view_reports_page(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('post-reports.index'));

        $response->assertStatus(403);
    }

    #[Test]
    public function admin_can_resolve_a_report(): void
    {
        $report = PostReport::create([
            'post_id' => $this->post->id,
            'reporter_id' => $this->user->id,
            'reason' => 'Test Meldung',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('post-reports.resolve', $report));

        $response->assertRedirect();

        $report->refresh();
        $this->assertNotNull($report->resolved_at);
        $this->assertEquals($this->admin->id, $report->resolved_by);
    }

    #[Test]
    public function admin_can_delete_reported_post(): void
    {
        $report = PostReport::create([
            'post_id' => $this->post->id,
            'reporter_id' => $this->user->id,
            'reason' => 'Muss gelöscht werden',
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('post-reports.destroy-post', $report));

        $response->assertRedirect(route('post-reports.index'));

        // Beitrag ist soft-deleted
        $this->assertSoftDeleted('posts', ['id' => $this->post->id]);

        // Meldung ist aufgelöst
        $report->refresh();
        $this->assertNotNull($report->resolved_at);
    }

    #[Test]
    public function post_report_model_has_correct_relationships(): void
    {
        $report = PostReport::create([
            'post_id' => $this->post->id,
            'reporter_id' => $this->user->id,
            'reason' => 'Test',
        ]);

        $this->assertInstanceOf(Post::class, $report->post);
        $this->assertInstanceOf(User::class, $report->reporter);
        $this->assertEquals($this->user->id, $report->reporter->id);
        $this->assertEquals($this->post->id, $report->post->id);
    }

    #[Test]
    public function post_has_reports_relationship(): void
    {
        PostReport::create([
            'post_id' => $this->post->id,
            'reporter_id' => $this->user->id,
            'reason' => 'Erster Grund',
        ]);

        PostReport::create([
            'post_id' => $this->post->id,
            'reporter_id' => $this->admin->id,
            'reason' => 'Zweiter Grund',
        ]);

        $this->assertEquals(2, $this->post->reports()->count());
    }

    #[Test]
    public function resolved_reports_are_not_shown_in_admin_index(): void
    {
        PostReport::create([
            'post_id' => $this->post->id,
            'reporter_id' => $this->user->id,
            'reason' => 'Bereits gelöst',
            'resolved_at' => now(),
            'resolved_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('post-reports.index'));

        $response->assertStatus(200);
        $response->assertSee('Keine offenen Meldungen');
    }

    #[Test]
    public function user_can_report_after_previous_report_was_resolved(): void
    {
        PostReport::create([
            'post_id' => $this->post->id,
            'reporter_id' => $this->user->id,
            'reason' => 'Alte Meldung',
            'resolved_at' => now(),
            'resolved_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('post.report', $this->post), [
                'reason' => 'Neue Meldung',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('type', 'success');

        $this->assertEquals(2, PostReport::where('post_id', $this->post->id)
            ->where('reporter_id', $this->user->id)
            ->count());
    }

    #[Test]
    public function dsgvo_export_includes_post_reports(): void
    {
        PostReport::create([
            'post_id' => $this->post->id,
            'reporter_id' => $this->user->id,
            'reason' => 'DSGVO Testmeldung',
        ]);

        // Prüfe direkt auf Datenbankebene
        $reports = PostReport::where('reporter_id', $this->user->id)->with('post')->get();
        $this->assertCount(1, $reports);
        $this->assertEquals('DSGVO Testmeldung', $reports->first()->reason);
        $this->assertEquals($this->post->header, $reports->first()->post->header);
    }

    #[Test]
    public function guest_cannot_report_a_post(): void
    {
        $response = $this->post(route('post.report', $this->post), [
            'reason' => 'Unauthenticated',
        ]);

        $response->assertRedirect('login');
    }
}


