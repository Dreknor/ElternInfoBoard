<?php

namespace Tests\Feature;

use App\Model\Group;
use App\Model\Post;
use App\Model\Rueckmeldungen;
use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * „view rueckmeldungen" erlaubt das Einsehen und Herunterladen von Abfrage-Antworten
 * für sichtbare Beiträge, aber nicht die Gesamtübersicht.
 */
class RueckmeldungenViewPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected Group $group;

    protected Rueckmeldungen $abfrage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app[PermissionRegistrar::class]->forgetCachedPermissions();
        Permission::firstOrCreate(['name' => 'view rueckmeldungen', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage rueckmeldungen', 'guard_name' => 'web']);

        $this->group = Group::factory()->create();

        $author = User::factory()->create(['password_changed_at' => now()]);
        $post = Post::factory()->create(['author' => $author->id, 'released' => 1, 'external' => 0]);
        $post->groups()->attach($this->group);

        $this->abfrage = Rueckmeldungen::factory()->create([
            'post_id' => $post->id,
            'type' => 'abfrage',
        ]);
    }

    private function viewer(bool $inGroup = true): User
    {
        $user = User::factory()->create(['password_changed_at' => now()]);
        $user->givePermissionTo('view rueckmeldungen');
        if ($inGroup) {
            $user->groups()->attach($this->group);
        }

        return $user;
    }

    /** @test */
    public function viewer_can_see_and_download_answers_of_visible_post(): void
    {
        $user = $this->viewer();

        $this->actingAs($user)->get("/rueckmeldungen/{$this->abfrage->id}/show")
            ->assertOk()
            ->assertViewIs('rueckmeldungen.showAbfrage');

        $this->actingAs($user)->get("/rueckmeldungen/{$this->abfrage->id}/download")
            ->assertOk()
            ->assertDownload();
    }

    /** @test */
    public function viewer_cannot_see_answers_of_post_outside_own_groups(): void
    {
        $user = $this->viewer(inGroup: false);

        $this->actingAs($user)->get("/rueckmeldungen/{$this->abfrage->id}/show")
            ->assertRedirect();

        $this->actingAs($user)->get("/rueckmeldungen/{$this->abfrage->id}/download")
            ->assertRedirect();
    }

    /** @test */
    public function viewer_cannot_open_overview_of_all_rueckmeldungen(): void
    {
        $this->actingAs($this->viewer())->get('/rueckmeldungen')
            ->assertForbidden();
    }

    /** @test */
    public function author_without_permissions_can_see_answers(): void
    {
        $author = User::find($this->abfrage->post->author);

        $this->actingAs($author)->get("/rueckmeldungen/{$this->abfrage->id}/show")
            ->assertOk();
    }

    /** @test */
    public function user_without_permission_cannot_see_answers(): void
    {
        $user = User::factory()->create(['password_changed_at' => now()]);
        $user->groups()->attach($this->group);

        $this->actingAs($user)->get("/rueckmeldungen/{$this->abfrage->id}/show")
            ->assertRedirect();
    }
}
