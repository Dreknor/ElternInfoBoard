<?php

namespace Tests\Feature\Anwesenheit;

use App\Http\Controllers\Anwesenheit\CareController;
use App\Model\Child;
use App\Model\ChildAttendanceEvent;
use App\Model\ChildCheckIn;
use App\Model\Holiday;
use App\Model\Krankmeldungen;
use App\Model\User;
use App\Services\Attendance\AttendanceCalculator;
use App\Services\Attendance\AttendanceDay;
use App\Settings\CareSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * An-/Abmeldeverlauf und Berechnung des tatsächlichen Betreuungsumfangs.
 */
class AttendanceHistoryTest extends TestCase
{
    private User $staff;

    private Child $child;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Queue::fake();
        Http::fake();

        // Mittwoch, 18.11.2026, 14:00 – Ferien liegen in der Woche davor
        Carbon::setTestNow(Carbon::parse('2026-11-18 14:00', 'Europe/Berlin'));

        $settings = new CareSetting;
        $settings->end_time = '17:00';
        $settings->save();

        Holiday::create([
            'year' => 2026,
            'bundesland' => $settings->bundesland,
            'name' => 'Testferien',
            'start' => '2026-11-09',
            'end' => '2026-11-13',
        ]);

        Permission::firstOrCreate(['name' => 'edit schickzeiten', 'guard_name' => 'web']);
        $this->staff = User::factory()->create();
        $this->staff->givePermissionTo('edit schickzeiten');
        $this->child = Child::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function event(string $type, string $at, array $extra = []): ChildAttendanceEvent
    {
        $time = Carbon::parse($at, 'Europe/Berlin');

        return ChildAttendanceEvent::create($extra + [
            'child_id' => $this->child->id,
            'date' => $time->toDateString(),
            'type' => $type,
            'occurred_at' => $time,
            'source' => ChildAttendanceEvent::SOURCE_STAFF,
        ]);
    }

    private function dayFor(string $date): AttendanceDay
    {
        return app(AttendanceCalculator::class)
            ->period(collect([$this->child]), Carbon::parse($date), Carbon::parse($date))
            ->get($this->child->id)
            ->first();
    }

    // ── Erfassung ────────────────────────────────────────────────────────────

    #[Test]
    public function check_in_and_out_are_recorded_with_staff_member(): void
    {
        $this->actingAs($this->staff)->post("care/anwesenheit/{$this->child->id}/anmelden")->assertSuccessful();
        Carbon::setTestNow(now()->addMinutes(90));
        $this->actingAs($this->staff)->post("care/anwesenheit/{$this->child->id}/abmelden")->assertSuccessful();

        $events = ChildAttendanceEvent::where('child_id', $this->child->id)->orderBy('id')->get();
        $this->assertSame([ChildAttendanceEvent::TYPE_CHECK_IN, ChildAttendanceEvent::TYPE_CHECK_OUT], $events->pluck('type')->all());
        $this->assertSame($this->staff->id, $events->first()->user_id);
        $this->assertSame(90, $this->dayFor('2026-11-18')->minutes);
    }

    #[Test]
    public function repeated_check_in_or_check_out_without_status_change_is_not_recorded(): void
    {
        $this->actingAs($this->staff)->post("care/anwesenheit/{$this->child->id}/anmelden");
        $this->actingAs($this->staff)->post("care/anwesenheit/{$this->child->id}/anmelden");
        $this->actingAs($this->staff)->post("care/anwesenheit/{$this->child->id}/abmelden");
        $this->actingAs($this->staff)->post("care/anwesenheit/{$this->child->id}/abmelden");

        $this->assertSame(2, ChildAttendanceEvent::where('child_id', $this->child->id)->count());
    }

    #[Test]
    public function check_out_and_in_again_on_the_same_day_keeps_both_intervals(): void
    {
        $this->actingAs($this->staff)->post("care/anwesenheit/{$this->child->id}/anmelden");
        Carbon::setTestNow(now()->addHour());
        $this->actingAs($this->staff)->post("care/anwesenheit/{$this->child->id}/abmelden");
        Carbon::setTestNow(now()->addHour());
        $this->actingAs($this->staff)->post("care/anwesenheit/{$this->child->id}/anmelden");
        Carbon::setTestNow(now()->addMinutes(30));
        $this->actingAs($this->staff)->post("care/anwesenheit/{$this->child->id}/abmelden");

        $day = $this->dayFor('2026-11-18');
        $this->assertCount(2, $day->intervals);
        $this->assertSame(90, $day->minutes);
    }

    #[Test]
    public function daily_auto_check_in_is_recorded_as_automatic(): void
    {
        $this->child->update(['auto_checkIn' => true]);
        $settings = new CareSetting;
        $settings->auto_checkin_enabled_schulzeit = true;
        $settings->auto_checkin_enabled_ferien = true;
        $settings->save();

        app(CareController::class)->dailyCheckIn();
        app(CareController::class)->dailyCheckIn();

        $events = ChildAttendanceEvent::where('child_id', $this->child->id)->get();
        $this->assertCount(1, $events);
        $this->assertSame(ChildAttendanceEvent::SOURCE_AUTO, $events->first()->source);
        $this->assertNotNull(ChildCheckIn::where('child_id', $this->child->id)->value('checked_in_at'));
        $this->assertTrue($this->dayFor('2026-11-18')->automatic);
    }

    // ── Berechnung ───────────────────────────────────────────────────────────

    #[Test]
    public function multiple_intervals_are_summed_and_cancelled_events_ignored(): void
    {
        $this->event('check_in', '2026-11-17 12:30');
        $this->event('check_out', '2026-11-17 14:00');
        $this->event('check_in', '2026-11-17 15:00');
        // Fehlklick, storniert
        $this->event('check_out', '2026-11-17 15:10', ['cancelled_at' => now(), 'cancel_note' => 'Fehlklick']);
        $this->event('check_out', '2026-11-17 16:30');

        $day = $this->dayFor('2026-11-17');

        $this->assertSame(180, $day->minutes);
        $this->assertFalse($day->incomplete);
        $this->assertTrue($day->edited);
        $this->assertSame('3:00', $day->formattedDuration());
    }

    #[Test]
    public function missing_check_out_on_past_day_counts_until_care_end_and_is_incomplete(): void
    {
        $this->event('check_in', '2026-11-17 13:00');

        $day = $this->dayFor('2026-11-17');

        $this->assertSame(240, $day->minutes);
        $this->assertTrue($day->incomplete);
        $this->assertTrue($day->intervals[0]['capped']);
    }

    #[Test]
    public function missing_check_out_without_care_end_counts_zero(): void
    {
        $settings = new CareSetting;
        $settings->end_time = null;
        $settings->save();
        $this->event('check_in', '2026-11-17 13:00');

        $day = $this->dayFor('2026-11-17');

        $this->assertSame(0, $day->minutes);
        $this->assertTrue($day->incomplete);
    }

    #[Test]
    public function today_without_check_out_is_running_until_now(): void
    {
        $this->event('check_in', '2026-11-18 12:15');

        $day = $this->dayFor('2026-11-18');

        $this->assertTrue($day->running);
        $this->assertFalse($day->incomplete);
        $this->assertSame(105, $day->minutes);
    }

    #[Test]
    public function check_out_without_check_in_marks_day_incomplete(): void
    {
        $this->event('check_out', '2026-11-17 15:00');

        $day = $this->dayFor('2026-11-17');

        $this->assertSame(0, $day->minutes);
        $this->assertTrue($day->incomplete);
    }

    #[Test]
    public function days_without_attendance_show_their_reason(): void
    {
        Krankmeldungen::create(['name' => 'x', 'kommentar' => '', 'start' => '2026-11-16', 'ende' => '2026-11-16', 'users_id' => $this->staff->id, 'child_id' => $this->child->id]);
        ChildCheckIn::create(['child_id' => $this->child->id, 'date' => '2026-11-17', 'should_be' => false]);

        $days = app(AttendanceCalculator::class)
            ->period(collect([$this->child]), Carbon::parse('2026-11-14'), Carbon::parse('2026-11-20'))
            ->get($this->child->id)
            ->keyBy(fn ($d) => $d->date->toDateString());

        $this->assertFalse($days->has('2026-11-15'), 'Wochenende wird nicht aufgeführt');
        $this->assertFalse($days->has('2026-11-19'), 'Zukunft wird nicht aufgeführt');
        $this->assertSame(AttendanceDay::STATUS_SICK, $days['2026-11-16']->status);
        $this->assertSame(AttendanceDay::STATUS_NOT_EXPECTED, $days['2026-11-17']->status);
        $this->assertSame(AttendanceDay::STATUS_NO_DATA, $days['2026-11-18']->status);
    }

    #[Test]
    public function summary_separates_school_time_and_holidays(): void
    {
        $this->event('check_in', '2026-11-12 08:00');   // Ferien
        $this->event('check_out', '2026-11-12 12:00');
        $this->event('check_in', '2026-11-17 13:00');   // Schulzeit
        $this->event('check_out', '2026-11-17 15:30');

        $days = app(AttendanceCalculator::class)
            ->period(collect([$this->child]), Carbon::parse('2026-11-09'), Carbon::parse('2026-11-17'))
            ->get($this->child->id);
        $summary = AttendanceCalculator::summarize($days);

        $this->assertSame(2, $summary['days_present']);
        $this->assertSame(390, $summary['minutes_total']);
        $this->assertSame(150, $summary['minutes_school']);
        $this->assertSame(240, $summary['minutes_holiday']);
    }

    // ── Korrektur, Storno, Nachtrag ──────────────────────────────────────────

    #[Test]
    public function correction_keeps_original_time_and_requires_note(): void
    {
        $event = $this->event('check_out', '2026-11-17 17:30');

        $this->actingAs($this->staff)
            ->postJson(route('anwesenheit.verlauf.correct', ['attendanceEvent' => $event]), ['time' => '15:10'])
            ->assertStatus(422);

        $this->actingAs($this->staff)
            ->postJson(route('anwesenheit.verlauf.correct', ['attendanceEvent' => $event]), ['time' => '15:10', 'note' => 'Abmelden vergessen'])
            ->assertOk()
            ->assertJsonPath('data.time', '15:10')
            ->assertJsonPath('data.corrected.original_time', '17:30');

        $event->refresh();
        $this->assertSame('2026-11-17 15:10', $event->occurred_at->format('Y-m-d H:i'));
        $this->assertSame($this->staff->id, $event->corrected_by);

        // Zweite Korrektur: der erste ursprüngliche Zeitpunkt bleibt erhalten
        $this->actingAs($this->staff)
            ->postJson(route('anwesenheit.verlauf.correct', ['attendanceEvent' => $event]), ['time' => '15:20', 'note' => 'nochmal'])
            ->assertJsonPath('data.corrected.original_time', '17:30');
    }

    #[Test]
    public function cancel_and_manual_entry_work_and_trigger_no_parent_notification(): void
    {
        $event = $this->event('check_out', '2026-11-17 14:00');

        $this->actingAs($this->staff)
            ->postJson(route('anwesenheit.verlauf.cancel', ['attendanceEvent' => $event]), ['note' => 'Fehlklick'])
            ->assertOk()
            ->assertJsonPath('data.cancelled.note', 'Fehlklick');

        $this->actingAs($this->staff)
            ->postJson(route('anwesenheit.verlauf.store', $this->child), [
                'type' => 'check_in', 'date' => '2026-11-17', 'time' => '12:00', 'note' => 'nachgetragen',
            ])
            ->assertOk()
            ->assertJsonPath('data.source', ChildAttendanceEvent::SOURCE_MANUAL);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function manual_entry_in_the_future_is_rejected(): void
    {
        $this->actingAs($this->staff)
            ->postJson(route('anwesenheit.verlauf.store', $this->child), [
                'type' => 'check_out', 'date' => '2026-11-18', 'time' => '16:00', 'note' => 'zu früh',
            ])
            ->assertStatus(422);
    }

    #[Test]
    public function day_endpoint_returns_history(): void
    {
        $this->event('check_in', '2026-11-17 13:00');
        $this->event('check_out', '2026-11-17 15:45');

        $this->actingAs($this->staff)
            ->getJson(route('anwesenheit.verlauf', ['child' => $this->child, 'date' => '2026-11-17']))
            ->assertOk()
            ->assertJsonPath('data.duration', '2:45')
            ->assertJsonCount(2, 'data.events');
    }

    #[Test]
    public function history_requires_permission(): void
    {
        $user = User::factory()->create();
        $event = $this->event('check_in', '2026-11-17 13:00');

        $this->actingAs($user)->getJson(route('anwesenheit.verlauf', $this->child))->assertForbidden();
        $this->actingAs($user)->postJson(route('anwesenheit.verlauf.cancel', ['attendanceEvent' => $event]), ['note' => 'xyz'])->assertForbidden();
        $this->actingAs($user)->get(route('anwesenheit.auswertung'))->assertForbidden();
    }

    // ── Auswertung, Export ───────────────────────────────────────────────────

    #[Test]
    public function report_page_and_export_are_available(): void
    {
        $this->event('check_in', '2026-11-17 13:00');
        $this->event('check_out', '2026-11-17 15:45');
        $this->withoutVite();

        $this->actingAs($this->staff)
            ->get(route('anwesenheit.auswertung', ['from' => '2026-11-16', 'to' => '2026-11-18']))
            ->assertOk()
            ->assertSee('Betreuungsumfang')
            ->assertSee($this->child->last_name)
            ->assertSee('2:45');

        $this->actingAs($this->staff)
            ->get(route('anwesenheit.auswertung.export', ['from' => '2026-11-16', 'to' => '2026-11-18']))
            ->assertOk()
            ->assertDownload('Betreuungsumfang_2026-11-16_2026-11-18.xlsx');
    }

    #[Test]
    public function attendance_list_contains_history_tab(): void
    {
        $this->withoutVite();

        $this->actingAs($this->staff)
            ->get('care/anwesenheit')
            ->assertOk()
            ->assertSee('id="verlaufTable"', false)
            ->assertSee(route('anwesenheit.verlauf.correct', ['attendanceEvent' => 'eventID']), false);
    }

    // ── Import, Aufbewahrung ─────────────────────────────────────────────────

    #[Test]
    public function import_takes_over_existing_check_ins_once(): void
    {
        ChildCheckIn::query()->insert([
            'child_id' => $this->child->id, 'date' => '2026-11-17', 'checked_in' => true, 'checked_out' => true,
            'checked_in_at' => '2026-11-17 12:45:00', 'checked_out_at' => '2026-11-17 16:00:00',
        ]);

        $this->artisan('attendance:import-checkins')->assertSuccessful();
        $this->artisan('attendance:import-checkins')->assertSuccessful();

        $events = ChildAttendanceEvent::where('child_id', $this->child->id)->get();
        $this->assertCount(2, $events);
        $this->assertTrue($events->every(fn ($e) => $e->source === ChildAttendanceEvent::SOURCE_IMPORT));
        $this->assertSame(195, $this->dayFor('2026-11-17')->minutes);
    }

    #[Test]
    public function cleanup_respects_configured_retention(): void
    {
        $settings = new CareSetting;
        $settings->attendance_history_months = 6;
        $settings->save();

        $this->event('check_in', '2026-05-10 13:00');   // älter als 6 Monate
        $this->event('check_in', '2026-06-10 13:00');

        $this->artisan('attendance:cleanup')->assertSuccessful();

        $this->assertSame(['2026-06-10'], ChildAttendanceEvent::pluck('date')->map->toDateString()->all());
    }
}
