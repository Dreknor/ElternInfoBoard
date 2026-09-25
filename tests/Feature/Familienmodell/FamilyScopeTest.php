<?php

namespace Tests\Feature\Familienmodell;

use App\Jobs\ProcessRemindersJob;
use App\Model\Group;
use App\Model\Liste;
use App\Model\listen_termine;
use App\Model\Pflichtstunde;
use App\Model\Post;
use App\Model\ReadReceipts;
use App\Model\Reinigung;
use App\Model\ReinigungsTask;
use App\Model\ReminderLog;
use App\Model\Rueckmeldungen;
use App\Model\User;
use App\Model\UserRueckmeldungen;
use App\Services\Family\FamilyResolver;
use App\Services\Pflichtstunden\PflichtstundenService;
use App\Services\Rueckmeldungen\RueckmeldungStatusService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\BuildsFamilies;
use Tests\TestCase;

/**
 * FAM-05: Familien-Scope mit mehr als zwei Mitgliedern (nur im
 * kind-zentrierten Modell abbildbar) und getrennten Familien.
 */
class FamilyScopeTest extends TestCase
{
    use BuildsFamilies;

    private User $a;

    private User $b;

    private User $grandma;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Notification::fake();
        foreach (['view Pflichtstunden', 'edit reinigung'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->useResolver(FamilyResolver::MODE_CHILD_CENTRIC);

        $this->a = $this->makeParent();
        $this->b = $this->makeParent();
        $this->grandma = $this->makeParent();
        $this->familyOf($this->a, $this->b, $this->grandma);
    }

    private function postFor(User ...$users): Post
    {
        $group = Group::factory()->create(['protected' => false]);
        foreach ($users as $user) {
            $user->groups()->attach($group->id);
        }
        $post = Post::factory()->create(['released' => 1, 'author' => $this->makeParent()->id, 'archiv_ab' => now()->addDays(10)]);
        $post->groups()->attach($group->id);

        return $post;
    }

    #[Test]
    public function one_answer_covers_whole_family_of_three(): void
    {
        $other = $this->makeParent();
        $post = $this->postFor($this->a, $this->b, $this->grandma, $other);
        $rueckmeldung = Rueckmeldungen::factory()->create(['post_id' => $post->id, 'pflicht' => true, 'ende' => now()->addDay(), 'type' => 'email', 'scope' => 'family']);
        UserRueckmeldungen::factory()->create(['post_id' => $post->id, 'users_id' => $this->grandma->id]);

        (new ProcessRemindersJob)->handle(app(\App\Settings\ReminderSetting::class));

        $this->assertSame([$other->id], ReminderLog::where('remindable_id', $rueckmeldung->id)
            ->where('remindable_type', Rueckmeldungen::class)->pluck('user_id')->unique()->values()->all());

        $status = app(RueckmeldungStatusService::class)->summary($post->fresh());
        $this->assertSame(['expected' => 2, 'answered' => 1, 'open' => 1, 'percent' => 50.0],
            array_intersect_key($status, array_flip(['expected', 'answered', 'open', 'percent'])));
    }

    #[Test]
    public function read_receipt_of_any_member_counts_for_family(): void
    {
        $post = $this->postFor($this->a, $this->b, $this->grandma);
        $post->update(['read_receipt' => true, 'read_receipt_deadline' => now()->addDay()]);
        ReadReceipts::create(['post_id' => $post->id, 'user_id' => $this->grandma->id, 'confirmed_at' => now()]);

        (new ProcessRemindersJob)->handle(app(\App\Settings\ReminderSetting::class));

        $this->assertSame(0, ReminderLog::where('remindable_type', Post::class)->count());
    }

    #[Test]
    public function any_member_may_edit_family_answer(): void
    {
        $post = $this->postFor($this->a, $this->b, $this->grandma);
        Rueckmeldungen::factory()->create(['post_id' => $post->id, 'type' => 'email', 'scope' => 'family']);
        $answer = UserRueckmeldungen::factory()->create(['post_id' => $post->id, 'users_id' => $this->a->id]);

        $this->actingAs($this->grandma)->get("userrueckmeldung/edit/{$answer->id}")->assertOk();
    }

    #[Test]
    public function pflichtstunden_sum_all_members_and_count_family_once(): void
    {
        foreach ([$this->a, $this->b, $this->grandma] as $user) {
            $user->givePermissionTo('view Pflichtstunden');
            $start = now()->subDays(2)->setTime(9, 0);
            Pflichtstunde::factory()->create(['user_id' => $user->id, 'start' => $start, 'end' => $start->copy()->addHour(), 'approved' => true]);
        }

        $service = app(PflichtstundenService::class);
        $unit = $service->unitFor($this->grandma);

        $this->assertCount(1, $service->units());
        $this->assertSame(180, $unit->doneMinutes);
        $this->assertSame(20 * 60, $unit->requiredMinutes);
        $this->assertEqualsCanonicalizing([$this->a->id, $this->b->id, $this->grandma->id], $unit->userIds);
        $this->assertCount(3, $service->entriesFor($this->b));
    }

    #[Test]
    public function third_member_may_cancel_but_other_family_may_not(): void
    {
        $termin = listen_termine::factory()->create([
            'listen_id' => Liste::factory()->create(['besitzer' => $this->makeParent()->id])->id,
            'reserviert_fuer' => $this->a->id,
            'termin' => now()->addDays(3),
        ]);
        $exPartner = $this->makeParent();
        $this->familyOf($exPartner);

        $this->actingAs($exPartner)->delete("listen/termine/absagen/{$termin->id}", ['text' => 'x']);
        $this->assertSame($this->a->id, $termin->fresh()->reserviert_fuer);

        $this->actingAs($this->grandma)->delete("listen/termine/absagen/{$termin->id}", ['text' => 'x']);
        $this->assertNull($termin->fresh()->reserviert_fuer);
    }

    #[Test]
    public function reinigung_assigns_one_member_and_skips_already_assigned_families(): void
    {
        $group = Group::factory()->create(['bereich' => 'Grundschule', 'protected' => false]);
        $single = $this->makeParent();
        foreach ([$this->a, $this->b, $this->grandma, $single] as $user) {
            $user->groups()->attach($group);
        }
        $admin = $this->makeParent();
        $admin->givePermissionTo('edit reinigung');
        $task = ReinigungsTask::factory()->create();
        $start = now()->next('Monday');

        $this->actingAs($admin)->post('reinigung/Grundschule/auto', [
            'aufgaben' => [$task->id],
            'start' => $start->toDateString(),
            'end' => $start->copy()->addWeeks(5)->toDateString(),
        ]);

        $this->assertSame(1, Reinigung::whereIn('users_id', [$this->a->id, $this->b->id, $this->grandma->id])->count());
        $this->assertSame(1, Reinigung::where('users_id', $single->id)->count());

        // Zweiter Lauf im selben Zeitraum teilt niemanden erneut ein
        $this->actingAs($admin)->post('reinigung/Grundschule/auto', [
            'aufgaben' => [$task->id],
            'start' => $start->toDateString(),
            'end' => $start->copy()->addWeeks(5)->toDateString(),
        ]);
        $this->assertSame(2, Reinigung::count());
    }
}
