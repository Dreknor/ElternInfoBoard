<?php

namespace Tests\Feature;

use App\Model\Group;
use App\Model\Post;
use App\Model\User;
use App\Services\App\PostQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Vertrauliche Beiträge: nur Autor und Empfänger (Gruppenmitglieder) sehen sie –
 * keine Berechtigung („view all“, „edit posts“, „create posts“ …) hebt das auf.
 */
class ConfidentialPostTest extends TestCase
{
    use RefreshDatabase;

    private Group $group;

    private User $author;

    private User $parent;

    private User $staff;

    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['view all', 'edit posts', 'create posts', 'release posts', 'upload files'] as $name) {
            Permission::findOrCreate($name);
        }

        $this->group = Group::factory()->create(['owner_id' => null]);

        $this->author = User::factory()->create(['changePassword' => false]);
        $this->author->givePermissionTo(['create posts', 'release posts', 'view all']);

        $this->parent = User::factory()->create(['changePassword' => false]);
        $this->parent->groups()->attach($this->group);

        // Mitarbeiter mit allen Leserechten, aber nicht in der Zielgruppe
        $this->staff = User::factory()->create(['changePassword' => false]);
        $this->staff->givePermissionTo(['view all', 'edit posts', 'create posts', 'release posts', 'upload files']);

        $this->post = Post::factory()->create([
            'author' => $this->author->id,
            'header' => 'Vertrauliche Elterninfo',
            'released' => 1,
            'external' => 0,
            'confidential' => true,
            'archiv_ab' => now()->addWeek(),
        ]);
        $this->post->groups()->attach($this->group);
    }

    /** @test */
    public function only_author_and_recipients_may_view(): void
    {
        $this->assertTrue($this->author->can('view', $this->post));
        $this->assertTrue($this->parent->can('view', $this->post));
        $this->assertFalse($this->staff->can('view', $this->post));
    }

    /** @test */
    public function view_all_staff_who_are_recipients_still_see_it(): void
    {
        $this->staff->groups()->attach($this->group);

        $this->assertTrue($this->staff->can('view', $this->post->fresh()));
    }

    /** @test */
    public function directly_selected_persons_are_recipients_too(): void
    {
        $person = User::factory()->create(['changePassword' => false]);
        $person->givePermissionTo('view all');
        $this->post->recipientUsers()->attach($person);

        $this->assertTrue($person->can('view', $this->post->fresh()));
        $this->assertTrue(PostQuery::visibleTo($person)->pluck('id')->contains($this->post->id));
        $this->assertFalse(PostQuery::visibleTo($this->staff)->pluck('id')->contains($this->post->id));
    }

    /** @test */
    public function post_lists_for_view_all_exclude_foreign_confidential_posts(): void
    {
        $normal = Post::factory()->create([
            'author' => $this->author->id, 'released' => 1, 'external' => 0,
            'confidential' => false, 'archiv_ab' => now()->addWeek(),
        ]);

        $ids = PostQuery::visibleTo($this->staff)->pluck('id');

        $this->assertTrue($ids->contains($normal->id));
        $this->assertFalse($ids->contains($this->post->id));
        $this->assertTrue(PostQuery::visibleTo($this->parent)->pluck('id')->contains($this->post->id));
        $this->assertTrue(PostQuery::visibleTo($this->author)->pluck('id')->contains($this->post->id));
    }

    /** @test */
    public function news_page_hides_post_from_staff_but_shows_it_to_recipients(): void
    {
        $this->actingAs($this->staff)->get('/nachrichten')
            ->assertOk()
            ->assertViewHas('nachrichten', fn ($posts) => ! $posts->contains('id', $this->post->id));

        $this->actingAs($this->parent)->get('/nachrichten')
            ->assertOk()
            ->assertViewHas('nachrichten', fn ($posts) => $posts->contains('id', $this->post->id));
    }

    /** @test */
    public function staff_cannot_open_edit_or_pdf(): void
    {
        $this->actingAs($this->staff)
            ->get('posts/edit/'.$this->post->id)
            ->assertRedirect('/home')
            ->assertSessionHas('Meldung', 'Dieser Beitrag ist vertraulich.');

        $this->actingAs($this->staff)
            ->get('post/'.$this->post->id.'/pdf')
            ->assertRedirect();
    }

    /** @test */
    public function search_with_create_posts_does_not_find_foreign_confidential_posts(): void
    {
        $visible = Post::withoutForeignConfidential($this->staff)
            ->whereLike(['header', 'news'], 'Vertrauliche')
            ->pluck('id');

        $this->assertFalse($visible->contains($this->post->id));
    }

    /** @test */
    public function attachments_are_hidden_even_with_upload_files(): void
    {
        $media = $this->post->addMediaFromString('geheim')->usingFileName('info.txt')->toMediaCollection('files');

        $this->assertFalse(\App\Services\App\MediaAccess::canView($this->staff, $media));
        $this->assertTrue(\App\Services\App\MediaAccess::canView($this->parent, $media));
    }

    /** @test */
    public function only_users_who_can_release_may_create_confidential_posts(): void
    {
        $writer = User::factory()->create(['changePassword' => false]);
        $writer->givePermissionTo('create posts');

        $this->actingAs($writer)->post('posts', [
            'header' => 'Test',
            'news' => 'Text',
            'gruppen' => [$this->group->id],
            'archiv_ab' => now()->addWeek()->toDateString(),
            'type' => 'info',
            'confidential' => 1,
        ])->assertSessionHasErrors('confidential');
    }
}
