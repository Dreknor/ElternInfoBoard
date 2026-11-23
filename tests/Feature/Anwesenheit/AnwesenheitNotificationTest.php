<?php

namespace Tests\Feature\Anwesenheit;

use App\Jobs\AnwesenheitNotificationJob;
use App\Model\Child;
use App\Model\Notification;
use App\Model\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Eltern dürfen nur dann eine An-/Abmelde-Push erhalten, wenn sich der Status
 * des Kindes tatsächlich geändert hat und die Meldung beim Versand noch stimmt.
 */
class AnwesenheitNotificationTest extends TestCase
{
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Permission::firstOrCreate(['name' => 'edit schickzeiten', 'guard_name' => 'web']);
        $this->staff = User::factory()->create();
        $this->staff->givePermissionTo('edit schickzeiten');
    }

    private function childWithParent(): array
    {
        $parent = User::factory()->create();
        $child = Child::factory()->create(['notification' => true]);
        $parent->children_rel()->attach($child);

        return [$parent, $child];
    }

    private function checkIn(Child $child, bool $in = true, bool $out = false): void
    {
        $child->checkIns()->create(['checked_in' => $in, 'checked_out' => $out, 'date' => now()->toDateString()]);
    }

    #[Test]
    public function check_out_without_open_check_in_sends_no_notification(): void
    {
        Queue::fake();
        [, $child] = $this->childWithParent();
        $this->checkIn($child, true, true); // bereits abgemeldet (z. B. von anderem Gerät)

        $this->actingAs($this->staff)->post("care/anwesenheit/{$child->id}/abmelden")->assertSuccessful();

        Queue::assertNotPushed(AnwesenheitNotificationJob::class);
    }

    #[Test]
    public function check_out_of_present_child_notifies_parent(): void
    {
        Queue::fake();
        [, $child] = $this->childWithParent();
        $this->checkIn($child);

        $this->actingAs($this->staff)->post("care/anwesenheit/{$child->id}/abmelden")->assertSuccessful();

        Queue::assertPushed(AnwesenheitNotificationJob::class, 1);
    }

    #[Test]
    public function check_in_of_already_present_child_sends_no_notification(): void
    {
        Queue::fake();
        [, $child] = $this->childWithParent();
        $this->checkIn($child);

        $this->actingAs($this->staff)->post("care/anwesenheit/{$child->id}/anmelden")->assertSuccessful();

        Queue::assertNotPushed(AnwesenheitNotificationJob::class);
    }

    #[Test]
    public function check_in_of_absent_child_notifies_parent(): void
    {
        Queue::fake();
        [, $child] = $this->childWithParent();
        $this->checkIn($child, false, false);

        $this->actingAs($this->staff)->post("care/anwesenheit/{$child->id}/anmelden")->assertSuccessful();

        Queue::assertPushed(AnwesenheitNotificationJob::class, 1);
    }

    #[Test]
    public function outdated_check_out_notification_is_dropped_when_child_is_present_again(): void
    {
        [$parent, $child] = $this->childWithParent();
        $this->checkIn($child); // Abmeldung wurde inzwischen per Anmeldung korrigiert

        (new AnwesenheitNotificationJob($parent, $child->first_name, 'checkOut', $child->id, now()))->handle();

        $this->assertSame(0, Notification::where('user_id', $parent->id)->count());
    }

    #[Test]
    public function notification_uses_time_of_action_not_time_of_processing(): void
    {
        [$parent, $child] = $this->childWithParent();
        $this->checkIn($child, true, true);
        $actionTime = now()->setTime(12, 5);

        (new AnwesenheitNotificationJob($parent, $child->first_name, 'checkOut', $child->id, $actionTime))->handle();

        $this->assertSame(
            'Abmeldung am '.$actionTime->format('d.m.Y').' 12:05',
            Notification::where('user_id', $parent->id)->value('title')
        );
    }
}
