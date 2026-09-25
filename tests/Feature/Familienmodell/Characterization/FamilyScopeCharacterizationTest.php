<?php

namespace Tests\Feature\Familienmodell\Characterization;

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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\BuildsFamilies;
use Tests\TestCase;

/**
 * Charakterisierung (FAM-01): Funktionen, bei denen eine gekoppelte Familie
 * als Einheit behandelt wird (Pflichtstunden, Rückmeldungen, Lesebestätigungen,
 * Termine, Reinigung).
 */
class FamilyScopeCharacterizationTest extends TestCase
{
    use BuildsFamilies;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Notification::fake();
        foreach (['view Pflichtstunden', 'edit Pflichtstunden', 'edit reinigung', 'edit terminliste'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }

    // ── Pflichtstunden ───────────────────────────────────────────────────────

    private function approvedHours(User $user, int $minutes): Pflichtstunde
    {
        $start = now()->subDays(2)->setTime(9, 0);

        return Pflichtstunde::factory()->create([
            'user_id' => $user->id,
            'start' => $start,
            'end' => $start->copy()->addMinutes($minutes),
            'approved' => true,
        ]);
    }

    #[Test]
    public function pflichtstunden_index_shows_entries_of_partner(): void
    {
        [$a, $b] = $this->coupleWithChildOfA();
        $a->givePermissionTo('view Pflichtstunden');
        $b->givePermissionTo('view Pflichtstunden');
        $entryA = $this->approvedHours($a, 60);

        $this->actingAs($b)->get('pflichtstunden')
            ->assertOk()
            ->assertViewHas('pflichtstunden', fn ($p) => $p->contains('id', $entryA->id));
    }

    #[Test]
    public function pflichtstunden_ranking_counts_couple_as_one_family(): void
    {
        [$a, $b] = $this->coupleWithChildOfA();
        $single = $this->makeParent();
        foreach ([$a, $b, $single] as $user) {
            $user->givePermissionTo('view Pflichtstunden');
        }
        $this->approvedHours($a, 120);
        $this->approvedHours($b, 120);

        $this->actingAs($b)->get('pflichtstunden')
            ->assertOk()
            ->assertViewHas('parent_stats', function ($stats) {
                // 20 h Soll (Settings-Default), Familie hat 4 h → 20 %
                return $stats['total_parents'] === 2
                    && $stats['your_rank'] === 1
                    && (float) $stats['your_progress'] === 20.0;
            });
    }

    #[Test]
    public function pflichtstunden_api_stats_count_couple_as_one_family(): void
    {
        [$a, $b] = $this->coupleWithChildOfA();
        $single = $this->makeParent();
        foreach ([$a, $b, $single] as $user) {
            $user->givePermissionTo('view Pflichtstunden');
        }
        $this->approvedHours($a, 120);

        Sanctum::actingAs($b);
        $this->getJson('api/pflichtstunden/stats')
            ->assertOk()
            ->assertJsonPath('ranking.total_families', 2)
            ->assertJsonPath('ranking.your_rank', 1)
            ->assertJsonStructure([
                'progress' => ['percent', 'total_minutes_completed', 'total_hours_completed', 'required_minutes',
                    'required_hours', 'open_minutes', 'open_hours', 'is_completed'],
                'ranking' => ['your_rank', 'total_families', 'avg_progress', 'better_than_average'],
                'payment' => ['remaining_payment', 'currency'],
            ]);
    }

    #[Test]
    public function pflichtstunden_api_index_contains_entries_of_partner(): void
    {
        [$a, $b] = $this->coupleWithChildOfA();
        $a->givePermissionTo('view Pflichtstunden');
        $b->givePermissionTo('view Pflichtstunden');
        $entryA = $this->approvedHours($a, 60);

        Sanctum::actingAs($b);
        $this->getJson('api/pflichtstunden')
            ->assertOk()
            ->assertJsonFragment(['id' => $entryA->id]);
    }

    #[Test]
    public function pflichtstunden_verwaltung_groups_couple_into_one_family(): void
    {
        [$a, $b] = $this->coupleWithChildOfA();
        $single = $this->makeParent();
        foreach ([$a, $b, $single] as $user) {
            $user->givePermissionTo('view Pflichtstunden');
        }
        $this->approvedHours($a, 60);
        $this->approvedHours($b, 60);
        $admin = $this->makeParent();
        $admin->givePermissionTo('edit Pflichtstunden');

        $this->actingAs($admin)->get('verwaltung/pflichtstunden')
            ->assertOk()
            ->assertViewHas('stats', fn ($stats) => $stats['totalFamilies'] === 2
                && abs($stats['totalHoursCompleted'] - 2.0) < 0.001)
            ->assertViewHas('groupedUsers', function ($grouped) use ($a, $b) {
                $family = $grouped->first(fn ($row) => in_array($row['user']->id, [$a->id, $b->id]));

                return (int) $family['totalMinutes'] === 120
                    && in_array($family['partner']?->id, [$a->id, $b->id]);
            });
    }

    // ── Rückmeldungen & Lesebestätigungen (Erinnerungen) ─────────────────────

    /**
     * @return array{0: Post, 1: Group}
     */
    private function releasedPostFor(User ...$users): array
    {
        $group = Group::factory()->create(['protected' => false]);
        foreach ($users as $user) {
            $user->groups()->attach($group->id);
        }
        $post = Post::factory()->create([
            'released' => 1,
            'author' => $this->makeParent()->id,
            'archiv_ab' => now()->addDays(10),
        ]);
        $post->groups()->attach($group->id);

        return [$post, $group];
    }

    #[Test]
    public function reminder_is_skipped_when_partner_already_answered_rueckmeldung(): void
    {
        [$a, $b] = $this->coupleWithChildOfA();
        $single = $this->makeParent();
        [$post] = $this->releasedPostFor($a, $b, $single);
        $rueckmeldung = Rueckmeldungen::factory()->create([
            'post_id' => $post->id, 'pflicht' => true, 'ende' => now()->addDay(), 'type' => 'email',
        ]);
        UserRueckmeldungen::factory()->create(['post_id' => $post->id, 'users_id' => $a->id]);

        (new ProcessRemindersJob)->handle(app(\App\Settings\ReminderSetting::class));

        $reminded = ReminderLog::where('remindable_type', Rueckmeldungen::class)
            ->where('remindable_id', $rueckmeldung->id)->pluck('user_id')->unique()->values()->all();
        $this->assertSame([$single->id], $reminded);
    }

    #[Test]
    public function read_receipt_reminder_is_skipped_when_partner_confirmed(): void
    {
        [$a, $b] = $this->coupleWithChildOfA();
        $single = $this->makeParent();
        [$post] = $this->releasedPostFor($a, $b, $single);
        $post->update(['read_receipt' => true, 'read_receipt_deadline' => now()->addDay()]);
        ReadReceipts::create(['post_id' => $post->id, 'user_id' => $b->id, 'confirmed_at' => now()]);

        (new ProcessRemindersJob)->handle(app(\App\Settings\ReminderSetting::class));

        $reminded = ReminderLog::where('remindable_type', Post::class)
            ->where('remindable_id', $post->id)->pluck('user_id')->unique()->values()->all();
        $this->assertSame([$single->id], $reminded);
    }

    #[Test]
    public function partner_may_edit_rueckmeldung_of_linked_parent_but_stranger_may_not(): void
    {
        [$a, $b] = $this->coupleWithChildOfA();
        [$post] = $this->releasedPostFor($a, $b);
        Rueckmeldungen::factory()->create(['post_id' => $post->id, 'type' => 'email']);
        $answer = UserRueckmeldungen::factory()->create(['post_id' => $post->id, 'users_id' => $a->id]);

        $this->actingAs($b)->get("userrueckmeldung/edit/{$answer->id}")
            ->assertOk()
            ->assertViewIs('userrueckmeldung.edit');

        $this->actingAs($this->makeParent())->get("userrueckmeldung/edit/{$answer->id}")
            ->assertRedirect()
            ->assertSessionHas('Meldung', 'Berechtigung fehlt');
    }

    #[Test]
    public function api_rueckmeldung_index_includes_answer_of_partner(): void
    {
        [$a, $b] = $this->coupleWithChildOfA();
        [$post] = $this->releasedPostFor($a, $b);
        Rueckmeldungen::factory()->create(['post_id' => $post->id, 'type' => 'email']);
        $answer = UserRueckmeldungen::factory()->create(['post_id' => $post->id, 'users_id' => $a->id]);

        Sanctum::actingAs($b);
        $this->getJson("api/rueckmeldung/{$post->id}")
            ->assertOk()
            ->assertJsonFragment(['id' => $answer->id]);
    }

    // ── Listen / Termine ─────────────────────────────────────────────────────

    #[Test]
    public function partner_may_cancel_appointment_booked_by_linked_parent(): void
    {
        [$a, $b] = $this->coupleWithChildOfA();
        $termin = listen_termine::factory()->create([
            'listen_id' => Liste::factory()->create(['besitzer' => $this->makeParent()->id])->id,
            'reserviert_fuer' => $a->id,
            'termin' => now()->addDays(3),
        ]);

        $this->actingAs($b)->delete("listen/termine/absagen/{$termin->id}", ['text' => 'Krank'])
            ->assertRedirect();

        $this->assertNull($termin->fresh()->reserviert_fuer);
    }

    #[Test]
    public function stranger_may_not_cancel_appointment_of_other_family(): void
    {
        [$a] = $this->coupleWithChildOfA();
        $termin = listen_termine::factory()->create([
            'listen_id' => Liste::factory()->create(['besitzer' => $this->makeParent()->id])->id,
            'reserviert_fuer' => $a->id,
            'termin' => now()->addDays(3),
        ]);

        $this->actingAs($this->makeParent())->delete("listen/termine/absagen/{$termin->id}", ['text' => 'x']);

        $this->assertSame($a->id, $termin->fresh()->reserviert_fuer);
    }

    // ── Reinigung ────────────────────────────────────────────────────────────

    #[Test]
    public function reinigung_auto_assignment_assigns_only_one_member_of_a_family(): void
    {
        [$a, $b] = $this->coupleWithChildOfA();
        $group = Group::factory()->create(['bereich' => 'Grundschule', 'protected' => false]);
        $a->groups()->attach($group);
        $b->groups()->attach($group);
        $admin = $this->makeParent();
        $admin->givePermissionTo('edit reinigung');
        $task = ReinigungsTask::factory()->create();

        $start = now()->next('Monday');
        $this->actingAs($admin)->post('reinigung/Grundschule/auto', [
            'aufgaben' => [$task->id],
            'start' => $start->toDateString(),
            'end' => $start->copy()->addWeeks(3)->toDateString(),
        ]);

        $this->assertSame(1, Reinigung::whereIn('users_id', [$a->id, $b->id])->count());
    }
}
