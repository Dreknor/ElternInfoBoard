<?php

namespace Tests\Feature\Http\Controllers;

use App\Model\Group;
use App\Model\Post;
use App\Model\SearchLog;
use App\Model\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\SearchController
 */
class SearchControllerTest extends TestCase
{
    #[Test]
    public function search_returns_an_ok_response(): void
    {
        Permission::findOrCreate('create posts', 'web');

        $user = User::factory()->create([
            'password_changed_at' => now(),
            'changePassword' => false,
        ]);
        $user->givePermissionTo('create posts');

        $group = Group::factory()->create(['protected' => false]);
        $user->groups()->attach($group);

        $matchingPost = Post::factory()->create([
            'header' => 'Sommerfest Info',
            'news' => 'Alle Infos zum Sommerfest',
            'author' => $user->id,
            'released' => true,
        ]);
        $matchingPost->groups()->attach($group);

        $response = $this->actingAs($user)->post('search', [
            'suche' => 'Sommerfest',
        ]);

        $response->assertOk();
        $response->assertViewIs('search.result');
        $response->assertViewHas('nachrichten');
        $response->assertViewHas('archiv');
        $response->assertViewHas('user');
        $response->assertViewHas('gruppen');
        $response->assertViewHas('Suche');
        $response->assertViewHas('Suche', 'Sommerfest');
        $response->assertViewHas('nachrichten', function ($nachrichten) use ($matchingPost) {
            return $nachrichten->contains(fn ($post) => $post->is($matchingPost));
        });

        $this->assertDatabaseHas('search_logs', [
            'user_id' => $user->id,
            'search_term' => 'Sommerfest',
            'nachrichten_count' => 1,
            'results_count' => 1,
        ]);
        $this->assertSame(1, SearchLog::count());
    }

    #[Test]
    public function search_validates_with_a_form_request(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\SearchController::class,
            'search',
            \App\Http\Requests\searchRequest::class
        );
    }

    // test cases...
}
