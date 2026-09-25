<?php

namespace Tests\Feature\Familienmodell;

use App\Enums\GuardianRelation;
use App\Exports\AbfrageExport;
use App\Jobs\ProcessRemindersJob;
use App\Model\AbfrageAntworten;
use App\Model\AbfrageOptions;
use App\Model\Child;
use App\Model\Group;
use App\Model\Post;
use App\Model\ReminderLog;
use App\Model\Rueckmeldungen;
use App\Model\User;
use App\Model\UserRueckmeldungen;
use App\Services\Family\FamilyResolver;
use App\Services\Rueckmeldungen\RueckmeldungStatusService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsFamilies;
use Tests\TestCase;

/**
 * FAM-05b: Rückmeldung pro Kind in der Gruppe der Nachricht (E2), nur
 * Sorgeberechtigte antworten (E7), Fallback für Empfänger ohne Kind.
 */
class RueckmeldungProKindTest extends TestCase
{
    use BuildsFamilies;

    private Group $klasse;

    private Post $post;

    private Rueckmeldungen $rueckmeldung;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Notification::fake();
        $this->useResolver(FamilyResolver::MODE_CHILD_CENTRIC);

        $this->klasse = Group::factory()->create(['protected' => false, 'owner_id' => null]);
        $this->post = Post::factory()->create(['released' => 1, 'author' => $this->makeParent()->id, 'archiv_ab' => now()->addDays(10)]);
        $this->post->groups()->attach($this->klasse->id);
        $this->rueckmeldung = Rueckmeldungen::factory()->create([
            'post_id' => $this->post->id, 'type' => 'email', 'pflicht' => true, 'multiple' => false,
            'ende' => now()->addDay(), 'empfaenger' => 'schule@example.com',
        ]);
    }

    private function recipient(User $user): User
    {
        $user->groups()->syncWithoutDetaching([$this->klasse->id]);

        return $user;
    }

    private function kindInKlasse(array $guardians): Child
    {
        $child = $this->childFor($guardians, ['class_id' => $this->klasse->id, 'group_id' => null]);
        foreach ($guardians as $guardian) {
            $this->recipient($guardian);
        }

        return $child;
    }

    private function statusService(): RueckmeldungStatusService
    {
        return app(RueckmeldungStatusService::class);
    }

    private function answer(User $user, ?Child $child, string $text = 'Ja')
    {
        return $this->actingAs($user)->post("rueckmeldung/{$this->post->id}", array_filter([
            'text' => $text,
            'child_id' => $child?->id,
        ]));
    }

    #[Test]
    public function new_rueckmeldung_defaults_to_child_scope_and_legacy_mode_acts_like_family(): void
    {
        $this->assertSame('child', $this->rueckmeldung->fresh()->scope);
        $this->assertSame('child', $this->statusService()->effectiveScope($this->rueckmeldung->fresh()));

        $this->useResolver(FamilyResolver::MODE_LEGACY);
        $this->assertSame('family', $this->statusService()->effectiveScope($this->rueckmeldung->fresh()));
    }

    #[Test]
    public function parent_with_two_children_gets_two_targets_and_answers_each(): void
    {
        $parent = $this->makeParent();
        $x = $this->kindInKlasse([$parent]);
        $y = $this->kindInKlasse([$parent]);
        $outsideChild = $this->childFor([$parent], ['class_id' => Group::factory()->create()->id]);

        $targets = $this->statusService()->targetsFor($parent, $this->post->fresh());
        $this->assertEqualsCanonicalizing([$x->id, $y->id], $targets->map(fn ($t) => $t->child->id)->all());

        $this->answer($parent, $x)->assertSessionHas('type', 'success');
        $this->answer($parent, $y)->assertSessionHas('type', 'success');
        $this->answer($parent, $x)->assertSessionHas('type', 'warning'); // nicht mehrfach
        $this->answer($parent, $outsideChild)->assertSessionHas('type', 'warning');

        $this->assertEqualsCanonicalizing([$x->id, $y->id], UserRueckmeldungen::pluck('child_id')->all());
        Mail::assertQueued(\App\Mail\UserRueckmeldung::class, 2);
    }

    #[Test]
    public function separated_parents_share_one_answer_per_child(): void
    {
        [$a, $b] = [$this->makeParent(), $this->makeParent()];
        $this->familyOf($a);
        $this->familyOf($b);
        $x = $this->kindInKlasse([$a, $b]);

        $this->answer($a, $x)->assertSessionHas('type', 'success');

        $targetB = $this->statusService()->targetsFor($b, $this->post->fresh())->first();
        $this->assertTrue($targetB->isAnswered());
        $this->assertSame($a->name, $targetB->answeredBy());
        $this->assertTrue($this->statusService()->mayEdit($b, UserRueckmeldungen::first()));
        $this->answer($b, $x)->assertSessionHas('type', 'warning');

        $summary = $this->statusService()->summary($this->post->fresh());
        $this->assertSame(['expected' => 1, 'answered' => 1], ['expected' => $summary['expected'], 'answered' => $summary['answered']]);
    }

    #[Test]
    public function guardians_without_custody_see_status_but_cannot_answer(): void
    {
        $mother = $this->makeParent();
        $x = $this->kindInKlasse([$mother]);
        $grandma = $this->recipient($this->makeParent());
        $partner = $this->recipient($this->makeParent());
        $this->linkGuardian($x, $grandma, GuardianRelation::Grandparent);
        $this->linkGuardian($x, $partner, GuardianRelation::Partner);

        $this->answer($grandma, $x)->assertSessionHas('type', 'warning');
        $this->answer($partner, $x)->assertSessionHas('type', 'warning');
        $this->answer($partner, null)->assertSessionHas('type', 'warning'); // kein Fallback
        $this->assertSame(0, UserRueckmeldungen::count());

        $target = $this->statusService()->targetsFor($partner, $this->post->fresh())->first();
        $this->assertTrue($target->isChild());
        $this->assertFalse($target->canAnswer);

        $this->actingAs($partner);
        $html = view('nachrichten.footer.rueckmeldung', ['nachricht' => $this->post->fresh(), 'user' => $partner])->render();
        $this->assertStringContainsString('Rückmeldung für '.$x->first_name, $html);
        $this->assertStringContainsString('nur Sorgeberechtigte', $html);
        $this->assertStringNotContainsString('name="child_id"', $html);

        $this->actingAs($mother);
        $html = view('nachrichten.footer.rueckmeldung', ['nachricht' => $this->post->fresh(), 'user' => $mother])->render();
        $this->assertStringContainsString('name="child_id" value="'.$x->id.'"', $html);
    }

    #[Test]
    public function recipient_without_child_answers_once_per_family(): void
    {
        $this->kindInKlasse([$this->makeParent()]);
        $elternrat = $this->recipient($this->makeParent());
        $partner = $this->recipient($this->makeParent());
        $this->familyOf($elternrat, $partner);

        $this->answer($elternrat, null)->assertSessionHas('type', 'success');
        $this->answer($partner, null)->assertSessionHas('type', 'warning');

        $this->assertNull(UserRueckmeldungen::first()->child_id);
        $summary = $this->statusService()->summary($this->post->fresh());
        $this->assertSame(2, $summary['expected']);   // 1 Kind + 1 Fallback-Familie
        $this->assertSame(1, $summary['answered']);
    }

    #[Test]
    public function reminders_go_to_custodians_of_open_children_only(): void
    {
        $mother = $this->makeParent();
        $grandma = $this->recipient($this->makeParent());
        $x = $this->kindInKlasse([$mother]);
        $this->linkGuardian($x, $grandma, GuardianRelation::Grandparent);
        $father = $this->makeParent();
        $y = $this->kindInKlasse([$father]);
        $this->answer($father, $y);

        $open = $this->statusService()->openRecipients($this->post->fresh());
        $this->assertSame([$mother->id], $open->keys()->all());
        $this->assertSame([trim($x->first_name.' '.$x->last_name)], $open->get($mother->id)['children']);

        (new ProcessRemindersJob)->handle(app(\App\Settings\ReminderSetting::class));
        $this->assertSame([$mother->id], ReminderLog::where('remindable_type', Rueckmeldungen::class)->pluck('user_id')->unique()->values()->all());
    }

    #[Test]
    public function child_without_custodian_is_not_counted(): void
    {
        $mother = $this->makeParent();
        $this->kindInKlasse([$mother]);
        $orphanLinked = $this->childFor([], ['class_id' => $this->klasse->id, 'group_id' => null]);
        $grandma = $this->recipient($this->makeParent());
        $this->linkGuardian($orphanLinked, $grandma, GuardianRelation::Grandparent);

        $summary = $this->statusService()->summary($this->post->fresh());

        $this->assertSame(1, $summary['expected']);
        $this->assertSame(1, $summary['unanswerable']);
    }

    #[Test]
    public function existing_family_scoped_rueckmeldung_keeps_behavior(): void
    {
        $this->rueckmeldung->update(['scope' => 'family']);
        [$a, $b] = [$this->makeParent(), $this->makeParent()];
        $this->familyOf($a, $b);
        $this->kindInKlasse([$a, $b]);

        $this->answer($a, null)->assertSessionHas('type', 'success');
        $this->answer($b, null)->assertSessionHas('type', 'warning');
        $this->assertNull(UserRueckmeldungen::first()->child_id);
    }

    #[Test]
    public function abfrage_answers_per_child_and_export_contains_child(): void
    {
        $this->rueckmeldung->update(['type' => 'abfrage', 'multiple' => false]);
        $option = AbfrageOptions::factory()->create(['rueckmeldung_id' => $this->rueckmeldung->id, 'type' => 'check', 'option' => 'Teilnahme']);
        $parent = $this->makeParent();
        $x = $this->kindInKlasse([$parent]);
        $y = $this->kindInKlasse([$parent]);

        $this->actingAs($parent);
        $html = view('nachrichten.footer.abfrage', ['nachricht' => $this->post->fresh(), 'user' => $parent])->render();
        $this->assertSame(2, substr_count($html, 'name="child_id"'));

        foreach ([$x, $y] as $child) {
            $this->actingAs($parent)->post("userrueckmeldung/{$this->rueckmeldung->id}", [
                'child_id' => $child->id,
                'answers' => ['options' => [$option->id]],
            ])->assertSessionHas('type', 'success');
        }
        $this->actingAs($parent)->post("userrueckmeldung/{$this->rueckmeldung->id}", [
            'child_id' => $x->id,
            'answers' => ['options' => [$option->id]],
        ])->assertSessionHas('type', 'warning');

        $this->assertEqualsCanonicalizing([$x->id, $y->id], AbfrageAntworten::pluck('child_id')->all());

        $export = new AbfrageExport($this->rueckmeldung->options, $this->rueckmeldung->userRueckmeldungen()->get());
        $this->assertSame('Kind', $export->headings()[2]);
        $this->assertStringContainsString($x->first_name, $export->map(UserRueckmeldungen::where('child_id', $x->id)->first())[2]);
    }

    #[Test]
    public function api_requires_child_for_multiple_children_and_custody(): void
    {
        $parent = $this->makeParent();
        $x = $this->kindInKlasse([$parent]);
        $y = $this->kindInKlasse([$parent]);
        Sanctum::actingAs($parent);

        $this->postJson('api/rueckmeldung', ['post_id' => $this->post->id, 'text' => 'Ja'])->assertStatus(422);
        $this->postJson('api/rueckmeldung', ['post_id' => $this->post->id, 'text' => 'Ja', 'child_id' => $y->id])->assertOk();

        $this->getJson("api/rueckmeldung/{$this->post->id}")
            ->assertOk()
            ->assertJsonPath('scope', 'child')
            ->assertJsonCount(2, 'targets')
            ->assertJsonFragment(['child_id' => $y->id, 'answered' => true])
            ->assertJsonFragment(['child_id' => $x->id, 'answered' => false]);
    }

    #[Test]
    public function api_old_app_with_single_child_works_and_without_custody_is_forbidden(): void
    {
        $parent = $this->makeParent();
        $x = $this->kindInKlasse([$parent]);
        $grandma = $this->recipient($this->makeParent());
        $this->linkGuardian($x, $grandma, GuardianRelation::Grandparent);

        Sanctum::actingAs($grandma);
        $this->postJson('api/rueckmeldung', ['post_id' => $this->post->id, 'text' => 'Ja'])->assertForbidden();

        Sanctum::actingAs($parent);
        $this->postJson('api/rueckmeldung', ['post_id' => $this->post->id, 'text' => 'Ja'])->assertOk();
        $this->assertSame($x->id, UserRueckmeldungen::first()->child_id);
    }
}
