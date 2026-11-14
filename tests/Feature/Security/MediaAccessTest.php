<?php

namespace Tests\Feature\Security;

use App\Model\Conversation;
use App\Model\Group;
use App\Model\Krankmeldungen;
use App\Model\Mail;
use App\Model\Message;
use App\Model\Post;
use App\Model\SecurityEvent;
use App\Model\User;
use App\Services\App\MediaAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * „upload files“ ist die Dateiverwaltung für Beiträge/Gruppen/Seiten – kein Freibrief für sensible Dateien.
 */
class MediaAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('media');
        Storage::fake('public');
        Storage::fake('local');
        config(['media-library.queue_conversions_by_default' => false]);
        $this->app[PermissionRegistrar::class]->forgetCachedPermissions();
        foreach (['upload files', 'manage diseases', 'see mails'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }

    private function user(array $permissions = []): User
    {
        $user = User::factory()->create(['password_changed_at' => now(), 'changePassword' => false]);
        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }

        return $user;
    }

    private function krankmeldungMedia(User $owner)
    {
        $k = Krankmeldungen::factory()->create(['users_id' => $owner->id]);

        return $k->addMediaFromString('attest')->usingFileName('attest.txt')->toMediaCollection('files');
    }

    private function messageMedia(User $member)
    {
        $conversation = Conversation::create(['type' => 'direct', 'created_by' => $member->id]);
        $conversation->users()->attach($member->id);
        $message = Message::create(['conversation_id' => $conversation->id, 'sender_id' => $member->id, 'body' => 'x', 'type' => 'file']);

        return $message->addMediaFromString('privat')->usingFileName('privat.txt')->toMediaCollection('message-attachments');
    }

    /** @test */
    public function upload_files_does_not_open_sick_note_attachments(): void
    {
        $family = $this->user();
        $media = $this->krankmeldungMedia($family);

        $this->assertFalse(MediaAccess::canView($this->user(['upload files']), $media));
        $this->assertTrue(MediaAccess::canView($family, $media));
        $this->assertTrue(MediaAccess::canView($this->user(['manage diseases']), $media));
    }

    /** @test */
    public function upload_files_does_not_open_foreign_messenger_files(): void
    {
        $member = $this->user();
        $media = $this->messageMedia($member);

        $this->assertFalse(MediaAccess::canView($this->user(['upload files']), $media));
        $this->assertTrue(MediaAccess::canView($member, $media));
    }

    /** @test */
    public function upload_files_does_not_open_mail_attachments_but_see_mails_does(): void
    {
        $sender = $this->user();
        $mail = Mail::create(['senders_id' => $sender->id, 'subject' => 's', 'text' => 't', 'to' => 'x']);
        $media = $mail->addMediaFromString('anhang')->usingFileName('a.txt')->toMediaCollection('files');

        $this->assertFalse(MediaAccess::canView($this->user(['upload files']), $media));
        $this->assertTrue(MediaAccess::canView($this->user(['see mails']), $media));
        $this->assertTrue(MediaAccess::canView($sender, $media));
    }

    /** @test */
    public function upload_files_still_opens_post_attachments(): void
    {
        $post = Post::factory()->create(['released' => 1, 'external' => 0, 'archiv_ab' => now()->addWeek()]);
        $post->groups()->attach(Group::factory()->create());
        $media = $post->addMediaFromString('brief')->usingFileName('brief.txt')->toMediaCollection('files');

        $this->assertTrue(MediaAccess::canView($this->user(['upload files']), $media));
    }

    /** @test */
    public function download_of_sensitive_media_is_logged_without_content(): void
    {
        $family = $this->user();
        $media = $this->krankmeldungMedia($family);

        $this->actingAs($family)->get('/image/'.$media->id)->assertOk();

        $event = SecurityEvent::where('event', 'media.download')->firstOrFail();
        $this->assertSame($family->id, $event->user_id);
        $this->assertSame(['media_id' => $media->id, 'owner_type' => 'Krankmeldungen', 'owner_id' => $media->model_id], $event->context);
    }

    /** @test */
    public function denied_download_is_not_logged(): void
    {
        $media = $this->krankmeldungMedia($this->user());

        $this->actingAs($this->user(['upload files']))->get('/image/'.$media->id)->assertForbidden();

        $this->assertSame(0, SecurityEvent::where('event', 'media.download')->count());
    }

    /** @test */
    public function post_media_download_is_not_logged(): void
    {
        $post = Post::factory()->create(['released' => 1, 'external' => 0, 'archiv_ab' => now()->addWeek()]);
        DB::table('group_post')->insert(['group_id' => Group::factory()->create()->id, 'post_id' => $post->id]);
        $postMedia = $post->addMediaFromString('bild')->usingFileName('b.txt')->toMediaCollection('files');

        $this->actingAs($this->user(['upload files']))->get('/image/'.$postMedia->id)->assertOk();

        $this->assertSame(0, SecurityEvent::where('event', 'media.download')->count());
    }
}
