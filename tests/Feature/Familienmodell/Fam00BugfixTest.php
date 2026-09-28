<?php

namespace Tests\Feature\Familienmodell;

use App\Jobs\AnwesenheitNotificationJob;
use App\Model\Child;
use App\Model\Krankmeldungen;
use App\Model\Mail as MailModel;
use App\Model\Schickzeiten;
use App\Model\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Regressionstests für die in docs/kind-zentriertes-familienmodell-konzept.md §16
 * gefundenen Fehler (Arbeitspaket FAM-00).
 */
class Fam00BugfixTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Permission::firstOrCreate(['name' => 'edit schickzeiten', 'guard_name' => 'web']);
    }

    private function parentWithChild(): array
    {
        $parent = User::factory()->create();
        $child = Child::factory()->create();
        $parent->children_rel()->attach($child);

        return [$parent, $child];
    }

    // ── §16 Nr. 1: Mail-Scope ────────────────────────────────────────────────

    #[Test]
    public function mail_scope_only_returns_own_sent_and_received_mails(): void
    {
        $user = User::factory()->create();
        $partner = User::factory()->create();
        $other = User::factory()->create();
        $user->update(['sorg2' => $partner->id]);
        $partner->update(['sorg2' => $user->id]);

        $sent = MailModel::withoutGlobalScopes()->create(['senders_id' => $user->id, 'subject' => 'A', 'text' => 'x', 'to' => 'x@example.com']);
        $received = MailModel::withoutGlobalScopes()->create(['senders_id' => $other->id, 'subject' => 'B', 'text' => 'x', 'to' => $user->email]);
        MailModel::withoutGlobalScopes()->create(['senders_id' => $other->id, 'subject' => 'C', 'text' => 'x', 'to' => $other->email]);
        // Früher: Vergleich von `to` mit der Partner-ID – darf nichts matchen
        MailModel::withoutGlobalScopes()->create(['senders_id' => $other->id, 'subject' => 'D', 'text' => 'x', 'to' => (string) $partner->id]);

        $this->actingAs($user);

        $this->assertEqualsCanonicalizing([$sent->id, $received->id], MailModel::pluck('id')->all());
    }

    #[Test]
    public function mail_scope_is_grouped_so_additional_conditions_apply(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        MailModel::withoutGlobalScopes()->create(['senders_id' => $user->id, 'subject' => 'A', 'text' => 'x', 'to' => 'x@example.com']);
        MailModel::withoutGlobalScopes()->create(['senders_id' => $other->id, 'subject' => 'B', 'text' => 'x', 'to' => $user->email]);
        MailModel::withoutGlobalScopes()->create(['senders_id' => $other->id, 'subject' => 'A', 'text' => 'x', 'to' => $other->email]);

        $this->actingAs($user);

        $this->assertSame(['A'], MailModel::where('subject', 'A')->pluck('subject')->all());
    }

    #[Test]
    public function mail_scope_returns_nothing_without_authenticated_user(): void
    {
        $user = User::factory()->create();
        MailModel::withoutGlobalScopes()->create(['senders_id' => $user->id, 'subject' => 'A', 'text' => 'x', 'to' => 'x@example.com']);

        $this->assertSame(0, MailModel::count());
    }

    // ── §16 Nr. 2, 4, 5: Krankmeldung Web ────────────────────────────────────

    #[Test]
    public function web_krankmeldung_for_foreign_child_is_rejected(): void
    {
        [$parent] = $this->parentWithChild();
        $foreignChild = Child::factory()->create();

        $this->actingAs($parent)->post('krankmeldung', [
            'child_id' => $foreignChild->id,
            'start' => now()->format('Y-m-d'),
            'ende' => now()->addDay()->format('Y-m-d'),
            'kommentar' => 'Fieber',
        ])->assertRedirect()->assertSessionHas('type', 'danger');

        $this->assertDatabaseMissing('krankmeldungen', ['child_id' => $foreignChild->id]);
    }

    #[Test]
    public function web_krankmeldung_for_own_child_without_disease_is_stored_with_group_in_mail(): void
    {
        [$parent, $child] = $this->parentWithChild();

        $this->actingAs($parent)->post('krankmeldung', [
            'child_id' => $child->id,
            'start' => now()->format('Y-m-d'),
            'ende' => now()->addDay()->format('Y-m-d'),
            'kommentar' => 'Fieber',
        ])->assertRedirect()->assertSessionHas('type', 'success');

        $this->assertDatabaseHas('krankmeldungen', ['child_id' => $child->id, 'users_id' => $parent->id]);
        Mail::assertQueued(\App\Mail\Krankmeldung::class);
    }

    #[Test]
    public function web_krankmeldung_for_partner_child_is_allowed(): void
    {
        [$parent, $child] = $this->parentWithChild();
        $partner = User::factory()->create(['sorg2' => $parent->id]);
        $parent->update(['sorg2' => $partner->id]);

        $this->actingAs($partner)->post('krankmeldung', [
            'child_id' => $child->id,
            'start' => now()->format('Y-m-d'),
            'ende' => now()->addDay()->format('Y-m-d'),
            'kommentar' => 'Fieber',
        ])->assertSessionHas('type', 'success');

        $this->assertDatabaseHas('krankmeldungen', ['child_id' => $child->id, 'users_id' => $partner->id]);
    }

    #[Test]
    public function staff_may_report_any_child_sick(): void
    {
        $staff = User::factory()->create();
        $staff->givePermissionTo('edit schickzeiten');
        $child = Child::factory()->create();

        $this->actingAs($staff)->post('krankmeldung', [
            'child_id' => $child->id,
            'start' => now()->format('Y-m-d'),
            'ende' => now()->addDay()->format('Y-m-d'),
            'kommentar' => 'Fieber',
        ])->assertSessionHas('type', 'success');

        $this->assertDatabaseHas('krankmeldungen', ['child_id' => $child->id]);
    }

    // ── §16 Nr. 3, 5: Krankmeldung API ───────────────────────────────────────

    #[Test]
    public function api_krankmeldung_for_foreign_child_is_forbidden(): void
    {
        [$parent] = $this->parentWithChild();
        $foreignChild = Child::factory()->create();
        Sanctum::actingAs($parent);

        $this->postJson('api/krankmeldung', [
            'child_id' => $foreignChild->id,
            'start' => now()->format('d.m.Y'),
            'ende' => now()->addDay()->format('d.m.Y'),
            'kommentar' => 'Fieber',
        ])->assertForbidden();

        $this->assertSame(0, Krankmeldungen::count());
    }

    #[Test]
    public function api_krankmeldung_for_own_child_without_disease_succeeds_and_stores_child_id(): void
    {
        [$parent, $child] = $this->parentWithChild();
        Sanctum::actingAs($parent);

        $this->postJson('api/krankmeldung', [
            'child_id' => $child->id,
            'start' => now()->format('d.m.Y'),
            'ende' => now()->addDay()->format('d.m.Y'),
            'kommentar' => 'Fieber',
        ])->assertOk();

        $this->assertDatabaseHas('krankmeldungen', ['child_id' => $child->id, 'users_id' => $parent->id]);
        Mail::assertQueued(\App\Mail\Krankmeldung::class);
    }

    // ── §16 Nr. 6: ChildController::update ───────────────────────────────────

    #[Test]
    public function updating_child_parent_keeps_existing_parents(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo('edit schickzeiten');
        [$parentA, $child] = $this->parentWithChild();
        $ucsParent = User::factory()->create();
        $child->parents()->attach($ucsParent, ['is_auto_provisioned' => true]);
        $parentB = User::factory()->create();

        $this->actingAs($admin)->put("child/{$child->id}", [
            'first_name' => $child->first_name,
            'last_name' => $child->last_name,
            'parent_id' => $parentB->id,
        ])->assertRedirect();

        $this->assertEqualsCanonicalizing(
            [$parentA->id, $ucsParent->id, $parentB->id],
            $child->parents()->pluck('users.id')->all()
        );
    }

    // ── §16 Nr. 9: Kind ohne Eltern / Benachrichtigungen ─────────────────────

    #[Test]
    public function staff_can_store_daily_schickzeit_for_child_without_parents(): void
    {
        $staff = User::factory()->create();
        $staff->givePermissionTo('edit schickzeiten');
        $child = Child::factory()->create();

        $this->actingAs($staff)->post("care/anwesenheit/{$child->id}/schickzeit", [
            'type' => 'genau',
            'time' => '14:00',
        ])->assertRedirect();

        $this->assertSame($staff->id, Schickzeiten::where('child_id', $child->id)->value('users_id'));
    }

    #[Test]
    public function check_in_without_parents_does_not_fail(): void
    {
        Queue::fake();
        $staff = User::factory()->create();
        $staff->givePermissionTo('edit schickzeiten');
        $child = Child::factory()->create(['notification' => true]);

        $this->actingAs($staff)->post("care/anwesenheit/{$child->id}/anmelden")->assertSuccessful();

        Queue::assertNotPushed(AnwesenheitNotificationJob::class);
    }

    #[Test]
    public function check_out_notifies_all_parents_and_linked_partner_once(): void
    {
        Queue::fake();
        $staff = User::factory()->create();
        $staff->givePermissionTo('edit schickzeiten');
        [$parentA, $child] = $this->parentWithChild();
        $child->update(['notification' => true]);
        $parentB = User::factory()->create();
        $child->parents()->attach($parentB);
        $partnerOfA = User::factory()->create(['sorg2' => $parentA->id]);
        $parentA->update(['sorg2' => $partnerOfA->id]);

        $this->actingAs($staff)->post("care/anwesenheit/{$child->id}/abmelden")->assertSuccessful();

        Queue::assertPushed(AnwesenheitNotificationJob::class, 3);
    }
}
