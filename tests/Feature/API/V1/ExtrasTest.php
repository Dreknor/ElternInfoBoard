<?php

namespace Tests\Feature\API\V1;

use App\Model\Arbeitsgemeinschaft;
use App\Model\Conversation;
use App\Model\Notification;
use App\Services\Push\PushTarget;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

class ExtrasTest extends AppApiTestCase
{
    /** @test */
    public function media_of_foreign_posts_cannot_be_downloaded_by_id_or_uuid(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $foreignPost = $this->postIn($this->group('Andere'));
        $media = $foreignPost->addMedia(UploadedFile::fake()->create('elternbrief.pdf', 10, 'application/pdf'))
            ->toMediaCollection('files');
        $user = $this->parentIn($this->group());
        Sanctum::actingAs($user);

        $this->get("/api/image/{$media->id}")->assertStatus(403);
        $this->get("/api/files/{$media->uuid}/download")->assertStatus(403);
    }

    /** @test */
    public function notifications_contain_date_and_app_target(): void
    {
        $user = $this->parentIn($this->group());
        Notification::withoutEvents(fn () => Notification::create([
            'user_id' => $user->id, 'type' => 'info', 'title' => 'Neu', 'message' => 'Text', 'url' => url('post/42'), 'read' => false,
        ]));
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/notifications')->assertOk()
            ->assertJsonPath('data.0.target', ['type' => 'post', 'id' => 42])
            ->assertJsonPath('meta.unread', 1)
            ->assertJsonStructure(['data' => [['created_at']]]);
        $this->getJson('/api/notifications')->assertJsonStructure(['data' => [['created_at']]]);
    }

    /** @test */
    public function push_texts_about_children_are_neutral(): void
    {
        $this->assertSame(['type' => 'attendance', 'id' => null], PushTarget::fromUrl('https://schule.de/schickzeiten'));
        $this->assertTrue(PushTarget::isSensitive(PushTarget::fromUrl('https://schule.de/schickzeiten')));
        $this->assertSame(['type' => 'conversation', 'id' => 7], PushTarget::fromUrl('https://schule.de/messenger/conversation/7'));
    }

    /** @test */
    public function child_can_be_enrolled_in_ag_until_full(): void
    {
        $group = $this->group();
        $user = $this->parentIn($group);
        $child = $this->childOf($user, $group);
        $ag = Arbeitsgemeinschaft::create([
            'name' => 'Schach', 'weekday' => 2, 'start_time' => '14:00', 'end_time' => '15:00',
            'start_date' => now()->subWeek(), 'end_date' => now()->addMonth(), 'max_participants' => 1, 'manager_id' => $user->id,
        ]);
        DB::table('arbeitsgemeinschaften_groups')->insert(['ag_id' => $ag->id, 'group_id' => $group->id]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/parent/ags')->assertOk()->assertJsonPath('data.0.eligible_children', [$child->id]);
        $this->postJson("/api/v1/parent/ags/{$ag->id}/enrollments", ['child_id' => $child->id])->assertCreated();
        $this->postJson("/api/v1/parent/ags/{$ag->id}/enrollments", ['child_id' => $child->id])->assertStatus(409);
        $this->getJson('/api/v1/parent/ags')->assertJsonPath('data.0.enrolled_children', [$child->id]);
    }

    /** @test */
    public function messenger_counts_unread_messages_with_one_query(): void
    {
        Permission::findOrCreate('use messenger', 'web');
        $group = $this->group();
        $me = $this->parentIn($group);
        $other = $this->parentIn($group);
        $me->givePermissionTo('use messenger');
        $conversation = Conversation::create(['type' => 'direct', 'created_by' => $other->id, 'is_active' => true]);
        $conversation->users()->attach([$me->id, $other->id]);
        $conversation->messages()->create(['sender_id' => $other->id, 'body' => 'Hallo', 'type' => 'text']);
        $conversation->messages()->create(['sender_id' => $other->id, 'body' => 'Noch da?', 'type' => 'text']);
        Sanctum::actingAs($me);

        $this->getJson('/api/v1/messenger/conversations')->assertOk()
            ->assertJsonPath('data.0.unread_count', 2)->assertJsonPath('meta.unread', 2);
        $this->getJson("/api/v1/messenger/conversations/{$conversation->id}/messages")->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/messenger/unread-count')->assertJsonPath('data.unread', 0);
    }
}
