<?php

namespace Tests\Feature\Security;

use App\Model\Group;
use App\Model\Post;
use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Mediendateien liegen nicht mehr unter /storage/… (öffentlich), sondern auf der privaten Disk `media`.
 */
class PrivateMediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('media');
        Storage::fake('local');
        config(['media-library.queue_conversions_by_default' => false]);
        $this->app[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    private function postInGroup(Group $group): Post
    {
        $post = Post::factory()->create(['released' => 1, 'external' => 0, 'archiv_ab' => now()->addWeek()]);
        $post->groups()->attach($group);

        return $post;
    }

    private function member(Group $group): User
    {
        $user = User::factory()->create(['password_changed_at' => now(), 'changePassword' => false]);
        $user->groups()->attach($group);

        return $user;
    }

    /** @test */
    public function new_uploads_are_stored_on_the_private_media_disk(): void
    {
        $post = $this->postInGroup(Group::factory()->create());

        $media = $post->addMedia(UploadedFile::fake()->create('brief.pdf', 10, 'application/pdf'))->toMediaCollection('files');

        $this->assertSame('media', $media->disk);
        Storage::disk('media')->assertExists($media->id.'/brief.pdf');
        Storage::disk('public')->assertMissing($media->id.'/brief.pdf');
    }

    /** @test */
    public function make_private_moves_existing_public_files_and_keeps_them_reachable_for_members(): void
    {
        $group = Group::factory()->create();
        $post = $this->postInGroup($group);
        config(['media-library.disk_name' => 'public']);
        $media = $post->addMedia(UploadedFile::fake()->create('brief.pdf', 10, 'application/pdf'))->toMediaCollection('files');
        Storage::disk('public')->put($media->id.'/conversions/brief-thumb.jpg', 'thumb');
        config(['media-library.disk_name' => 'media']);

        $this->artisan('media:make-private', ['--dry-run' => true])->assertSuccessful();
        $this->assertSame('public', $media->fresh()->disk);
        Storage::disk('public')->assertExists($media->id.'/brief.pdf');

        $this->artisan('media:make-private')->assertSuccessful();

        $media->refresh();
        $this->assertSame('media', $media->disk);
        $this->assertSame('media', $media->conversions_disk);
        Storage::disk('media')->assertExists([$media->id.'/brief.pdf', $media->id.'/conversions/brief-thumb.jpg']);
        Storage::disk('public')->assertMissing([$media->id.'/brief.pdf', $media->id.'/conversions/brief-thumb.jpg']);
        $this->assertFalse(Storage::disk('public')->exists((string) $media->id));

        $this->actingAs($this->member($group))->get('/image/'.$media->id)->assertOk();
        $this->actingAs($this->member(Group::factory()->create()))->get('/image/'.$media->id)->assertForbidden();

        // zweiter Lauf ändert nichts mehr
        $this->artisan('media:make-private')->expectsOutputToContain('Umgezogen: 0')->assertSuccessful();
    }

    /** @test */
    public function make_private_refuses_the_public_disk_as_target(): void
    {
        config(['media-library.disk_name' => 'public']);

        $this->artisan('media:make-private')->assertFailed();
    }

    /** @test */
    public function orphan_scan_only_removes_unknown_media_folders(): void
    {
        Permission::firstOrCreate(['name' => 'scan files', 'guard_name' => 'web']);
        $admin = User::factory()->create(['password_changed_at' => now(), 'changePassword' => false]);
        $admin->givePermissionTo('scan files');

        $media = $this->postInGroup(Group::factory()->create())
            ->addMedia(UploadedFile::fake()->create('brief.pdf', 10, 'application/pdf'))->toMediaCollection('files');
        $orphanId = $media->id + 1000;
        Storage::disk('media')->put($orphanId.'/alt.pdf', 'x');
        // Antwortdateien der Elternabfragen liegen auf `local` und gehören nicht zur Medienbibliothek
        Storage::disk('local')->put('elternabfragen/1/2/antwort.pdf', 'x');
        // ältere Einträge auf `local` – damit wird auch diese Disk durchsucht
        $old = $this->postInGroup(Group::factory()->create())
            ->addMedia(UploadedFile::fake()->create('alt.pdf', 10, 'application/pdf'))->toMediaCollection('files');
        DB::table('media')->where('id', $old->id)->update(['disk' => 'local', 'conversions_disk' => 'local']);
        Storage::disk('local')->put($old->id.'/alt.pdf', 'x');

        $this->actingAs($admin)->delete('/settings/removeUnusedFiles')->assertRedirect();

        Storage::disk('media')->assertExists($media->id.'/brief.pdf');
        Storage::disk('media')->assertMissing($orphanId.'/alt.pdf');
        Storage::disk('local')->assertExists(['elternabfragen/1/2/antwort.pdf', $old->id.'/alt.pdf']);
    }
}
