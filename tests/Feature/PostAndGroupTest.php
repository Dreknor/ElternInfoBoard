<?php

namespace Tests\Feature;

use App\Model\Group;
use App\Model\Post;
use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-Tests für Post und Gruppenverwaltung
 */
class PostAndGroupTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function user_can_create_post()
    {
        $user = User::factory()->create();

        $post = Post::factory()->create([
            'author' => $user->id,
            'header' => 'Test Header',
            'news' => 'Dies ist ein Testbeitrag',
        ]);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'author' => $user->id,
            'news' => 'Dies ist ein Testbeitrag',
        ]);
    }

    /**
     * @test
     */
    public function post_belongs_to_author()
    {
        $user = User::factory()->create(['password_changed_at' => now()]);
        $post = Post::factory()->create(['author' => $user->id]);

        $this->assertInstanceOf(User::class, $post->author_user);
        $this->assertEquals($user->id, $post->author_user->id);
    }

    /**
     * @test
     */
    public function group_can_have_multiple_users()
    {
        $group = Group::factory()->create();
        $users = User::factory()->count(5)->create();

        $group->users()->attach($users->pluck('id'));

        $this->assertCount(5, $group->users);
    }

    /**
     * @test
     */
    public function user_can_join_multiple_groups()
    {
        $user = User::factory()->create();
        $groups = Group::factory()->count(3)->create();

        $user->groups()->attach($groups->pluck('id'));

        $this->assertCount(3, $user->groups);
    }

    /**
     * @test
     */
    public function post_can_belong_to_group()
    {
        $group = Group::factory()->create();
        $post = Post::factory()->create();

        // Posts und Groups haben eine viele-zu-viele Beziehung über group_posts
        $post->groups()->attach($group->id);

        $this->assertTrue($post->groups->contains($group));
        $this->assertEquals($group->id, $post->groups->first()->id);
    }
}
