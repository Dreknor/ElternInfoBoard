<?php

namespace Tests\Feature;

use App\Jobs\SendPushNotifications;
use App\Model\Group;
use App\Model\Notification;
use App\Model\Post;
use App\Model\User;
use App\Model\UserDevice;
use App\Settings\GeneralSetting;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Werden einer bereits veröffentlichten Nachricht Gruppen oder Personen hinzugefügt,
 * erhalten nur die neu hinzugekommenen Empfänger eine Benachrichtigung.
 */
class PostNewRecipientsNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $author;

    protected Group $oldGroup;

    protected Group $newGroup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app[PermissionRegistrar::class]->forgetCachedPermissions();
        Permission::firstOrCreate(['name' => 'create posts', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'release posts', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'edit posts', 'guard_name' => 'web']);

        $this->author = User::factory()->create(['password_changed_at' => now()]);
        $this->author->givePermissionTo(['create posts', 'release posts', 'edit posts']);

        $this->oldGroup = Group::factory()->create();
        $this->newGroup = Group::factory()->create();
    }

    private function member(Group ...$groups): User
    {
        $user = User::factory()->create(['password_changed_at' => now()]);
        foreach ($groups as $group) {
            $user->groups()->attach($group);
        }
        UserDevice::create(['user_id' => $user->id, 'token' => 'token-'.$user->id, 'provider' => 'fcm', 'platform' => 'android']);

        return $user;
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'header' => 'Elternabend',
            'news' => 'Einladung zum Elternabend',
            'type' => 'info',
            'archiv_ab' => Carbon::now()->addWeek()->format('Y-m-d'),
            'released' => 1,
            'reactable' => 0,
            'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ], $overrides);
    }

    private function publishedPost(array $groups, array $persons = []): Post
    {
        $post = Post::factory()->create(['author' => $this->author->id, 'released' => 1, 'external' => 0]);
        $post->groups()->attach($groups);
        $post->recipientUsers()->attach($persons);

        return $post;
    }

    private function notifiedUserIds(Post $post): array
    {
        return Notification::query()
            ->where('url', url('post/'.$post->id))
            ->orderBy('user_id')
            ->pluck('user_id')
            ->all();
    }

    #[Test]
    public function only_members_of_newly_added_groups_are_notified(): void
    {
        Bus::fake([SendPushNotifications::class]);

        $this->member($this->oldGroup);
        $this->member($this->oldGroup, $this->newGroup);
        $newcomer = $this->member($this->newGroup);

        $post = $this->publishedPost([$this->oldGroup->id]);

        $this->actingAs($this->author)
            ->put('/posts/'.$post->id, $this->payload(['gruppen' => [$this->oldGroup->id, $this->newGroup->id]]))
            ->assertSessionHasNoErrors();

        $this->assertSame([$newcomer->id], $this->notifiedUserIds($post));
        Bus::assertDispatchedTimes(SendPushNotifications::class, 1);
        Bus::assertDispatched(SendPushNotifications::class, fn ($job) => $job->userIds === [$newcomer->id]);
    }

    #[Test]
    public function newly_added_persons_are_notified_but_not_previous_ones(): void
    {
        Bus::fake([SendPushNotifications::class]);

        $settings = app(GeneralSetting::class);
        $settings->allow_individual_recipients = true;
        $settings->save();

        $member = $this->member($this->oldGroup);
        $previousPerson = $this->member();
        $addedPerson = $this->member();

        $post = $this->publishedPost([$this->oldGroup->id], [$previousPerson->id]);

        $this->actingAs($this->author)
            ->put('/posts/'.$post->id, $this->payload([
                'gruppen' => [$this->oldGroup->id],
                'personen' => [$previousPerson->id, $addedPerson->id, $member->id],
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame([$addedPerson->id], $this->notifiedUserIds($post));
        Bus::assertDispatched(SendPushNotifications::class, fn ($job) => $job->userIds === [$addedPerson->id]);
    }

    #[Test]
    public function editing_without_new_recipients_sends_nothing(): void
    {
        Bus::fake([SendPushNotifications::class]);

        $this->member($this->oldGroup);
        $post = $this->publishedPost([$this->oldGroup->id]);

        $this->actingAs($this->author)
            ->put('/posts/'.$post->id, $this->payload(['gruppen' => [$this->oldGroup->id], 'news' => 'Geänderter Text']))
            ->assertSessionHasNoErrors();

        $this->assertSame([], $this->notifiedUserIds($post));
        Bus::assertNotDispatched(SendPushNotifications::class);
    }

    #[Test]
    public function unreleased_post_still_notifies_all_recipients_on_publication(): void
    {
        Bus::fake([SendPushNotifications::class]);

        $first = $this->member($this->oldGroup);
        $second = $this->member($this->newGroup);

        $post = Post::factory()->create(['author' => $this->author->id, 'released' => 0, 'external' => 0]);
        $post->groups()->attach($this->oldGroup);

        $this->actingAs($this->author)
            ->put('/posts/'.$post->id, $this->payload(['gruppen' => [$this->oldGroup->id, $this->newGroup->id]]))
            ->assertSessionHasNoErrors();

        $this->assertSame([$first->id, $second->id], $this->notifiedUserIds($post));
        Bus::assertDispatchedTimes(SendPushNotifications::class, 1);
    }
}
