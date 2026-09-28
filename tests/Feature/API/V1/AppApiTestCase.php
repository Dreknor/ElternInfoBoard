<?php

namespace Tests\Feature\API\V1;

use App\Model\Child;
use App\Model\Group;
use App\Model\Post;
use App\Model\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Gemeinsame Helfer für die Tests der App-API v1.
 */
abstract class AppApiTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Queue::fake();
    }

    protected function group(string $name = 'Klasse 3a'): Group
    {
        return Group::withoutGlobalScopes()->create(['name' => $name, 'protected' => false]);
    }

    protected function parentIn(Group $group, array $attributes = []): User
    {
        $user = User::factory()->create($attributes + ['changePassword' => false]);
        DB::table('group_user')->insert(['group_id' => $group->id, 'user_id' => $user->id]);

        return $user;
    }

    protected function postIn(Group $group, array $attributes = []): Post
    {
        $post = Post::withoutEvents(fn () => Post::factory()->create($attributes + [
            'released' => 1,
            'external' => false,
            'read_receipt' => false,
            'archiv_ab' => now()->addWeek(),
        ]));
        DB::table('group_post')->insert(['group_id' => $group->id, 'post_id' => $post->id]);

        return $post;
    }

    protected function childOf(User $user, ?Group $group = null): Child
    {
        $child = Child::create([
            'first_name' => 'Mia',
            'last_name' => 'Muster',
            'group_id' => $group?->id,
            'class_id' => $group?->id,
        ]);
        $user->children_rel()->attach($child->id);

        return $child;
    }
}
