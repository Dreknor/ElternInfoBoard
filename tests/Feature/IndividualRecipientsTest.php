<?php

namespace Tests\Feature;

use App\Model\Group;
use App\Model\Liste;
use App\Model\Post;
use App\Model\Termin;
use App\Model\User;
use App\Services\App\ListenService;
use App\Services\App\PostQuery;
use App\Services\App\TerminQuery;
use App\Settings\GeneralSetting;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Freigabe von Beiträgen und Terminen an einzelne Personen ohne Gruppenzugehörigkeit
 * (general.allow_individual_recipients).
 */
class IndividualRecipientsTest extends TestCase
{
    use RefreshDatabase;

    protected Group $group;

    protected User $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app[PermissionRegistrar::class]->forgetCachedPermissions();
        Permission::firstOrCreate(['name' => 'create posts', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'release posts', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'create terminliste', 'guard_name' => 'web']);

        $this->group = Group::factory()->create();
        $this->author = User::factory()->create(['password_changed_at' => now()]);
        $this->author->givePermissionTo(['create posts', 'release posts', 'create terminliste']);
    }

    private function enable(bool $enabled = true): void
    {
        $settings = app(GeneralSetting::class);
        $settings->allow_individual_recipients = $enabled;
        $settings->save();
    }

    private function parent(bool $inGroup = false): User
    {
        $user = User::factory()->create(['password_changed_at' => now()]);
        if ($inGroup) {
            $user->groups()->attach($this->group);
        }

        return $user;
    }

    private function postPayload(array $overrides = []): array
    {
        return array_merge([
            'header' => 'Einzelfreigabe',
            'news' => 'Nur für ausgewählte Personen',
            'type' => 'info',
            'archiv_ab' => Carbon::now()->addWeek()->format('Y-m-d'),
            'released' => 1,
            'reactable' => 0,
        ], $overrides);
    }

    #[Test]
    public function directly_assigned_person_without_group_sees_post(): void
    {
        $person = $this->parent();
        $other = $this->parent();

        $post = Post::factory()->create(['author' => $this->author->id, 'released' => 1, 'external' => 0]);
        $post->recipientUsers()->attach($person);

        $this->assertTrue($person->can('view', $post));
        $this->assertFalse($other->can('view', $post));
        $this->assertTrue(PostQuery::visibleTo($person)->whereKey($post->id)->exists());
        $this->assertFalse(PostQuery::visibleTo($other)->whereKey($post->id)->exists());
        $this->assertSame([$post->id], $person->visiblePosts()->pluck('id')->all());
    }

    #[Test]
    public function recipients_combine_groups_and_persons_without_duplicates(): void
    {
        $member = $this->parent(inGroup: true);
        $person = $this->parent();

        $post = Post::factory()->create(['author' => $this->author->id, 'released' => 1]);
        $post->groups()->attach($this->group);
        $post->recipientUsers()->attach([$person->id, $member->id]);

        $ids = $post->recipients()->pluck('id')->sort()->values()->all();

        $this->assertSame(collect([$member->id, $person->id])->sort()->values()->all(), $ids);
        $this->assertSame(1, $member->visiblePosts()->whereKey($post->id)->count());
    }

    #[Test]
    public function post_can_be_created_for_persons_only_when_enabled(): void
    {
        $this->enable();
        $person = $this->parent();

        $this->actingAs($this->author)
            ->post('/posts', $this->postPayload(['personen' => [$person->id]]))
            ->assertSessionHasNoErrors();

        $post = Post::query()->where('header', 'Einzelfreigabe')->firstOrFail();
        $this->assertSame([$person->id], $post->recipientUsers()->pluck('users.id')->all());
        $this->assertTrue($post->groups()->doesntExist());
    }

    #[Test]
    public function groups_or_persons_are_required(): void
    {
        $this->enable();

        $this->actingAs($this->author)
            ->post('/posts', $this->postPayload())
            ->assertSessionHasErrors(['gruppen', 'personen']);
    }

    #[Test]
    public function persons_are_ignored_and_groups_required_when_disabled(): void
    {
        $person = $this->parent();

        $this->actingAs($this->author)
            ->post('/posts', $this->postPayload(['personen' => [$person->id]]))
            ->assertSessionHasErrors('gruppen');

        $this->actingAs($this->author)
            ->post('/posts', $this->postPayload(['gruppen' => [$this->group->id], 'personen' => [$person->id]]))
            ->assertSessionHasNoErrors();

        $post = Post::query()->where('header', 'Einzelfreigabe')->firstOrFail();
        $this->assertTrue($post->recipientUsers()->doesntExist());
    }

    #[Test]
    public function directly_assigned_person_sees_termin(): void
    {
        $person = $this->parent();
        $other = $this->parent();

        $termin = Termin::create([
            'terminname' => 'Elterngespräch',
            'start' => Carbon::tomorrow()->setTime(10, 0),
            'ende' => Carbon::tomorrow()->setTime(11, 0),
            'fullDay' => false,
            'public' => false,
        ]);
        $termin->recipientUsers()->attach($person);

        $from = Carbon::today();
        $to = Carbon::today()->addWeek();

        $this->assertSame(['termin-'.$termin->id], collect(TerminQuery::between($person, $from, $to, false))->pluck('id')->all());
        $this->assertSame([], TerminQuery::between($other, $from, $to, false));
        $this->assertSame([$termin->id], $person->visibleTermine()->pluck('id')->all());
    }

    private function listePayload(array $overrides = []): array
    {
        return array_merge([
            'listenname' => 'Elterngespräche',
            'type' => 'termin',
            'visible_for_all' => 0,
            'multiple' => 0,
            'active' => 0,
            'ende' => Carbon::now()->addWeeks(2)->format('Y-m-d'),
        ], $overrides);
    }

    #[Test]
    public function directly_assigned_person_without_group_can_access_liste(): void
    {
        $person = $this->parent();
        $other = $this->parent();

        $liste = Liste::create($this->listePayload([
            'besitzer' => $this->author->id,
            'active' => 1,
            'ende' => now()->addWeek(),
        ]));
        $liste->recipientUsers()->attach($person);

        $service = app(ListenService::class);
        $this->assertTrue($service->canAccess($person, $liste));
        $this->assertFalse($service->canAccess($other, $liste));
        $this->assertSame([$liste->id], $person->visibleListen()->pluck('id')->all());

        $this->actingAs($person)->get('/listen/'.$liste->id)->assertOk();
        $this->actingAs($other)->get('/listen/'.$liste->id)->assertRedirect();
    }

    #[Test]
    public function liste_can_be_created_for_persons_only_when_enabled(): void
    {
        $this->enable();
        $person = $this->parent();

        $this->actingAs($this->author)
            ->post('/listen', $this->listePayload(['personen' => [$person->id]]))
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('Meldung');

        $liste = Liste::query()->where('listenname', 'Elterngespräche')->firstOrFail();
        $this->assertSame([$person->id], $liste->recipientUsers()->pluck('users.id')->all());
    }

    #[Test]
    public function liste_without_groups_is_rejected_when_disabled(): void
    {
        $person = $this->parent();

        $this->actingAs($this->author)
            ->post('/listen', $this->listePayload(['personen' => [$person->id]]))
            ->assertSessionHas('Meldung', 'Es muss mindestens eine Gruppe ausgewählt werden.');

        $this->assertDatabaseMissing('listen', ['listenname' => 'Elterngespräche']);
    }
}
