<?php

namespace Tests\Feature\Web;

use App\Model\ActiveDisease;
use App\Model\Child;
use App\Model\ChildCheckIn;
use App\Model\Disease;
use App\Model\Group;
use App\Model\Krankmeldungen;
use App\Model\Module;
use App\Model\Post;
use App\Model\ReadReceipts;
use App\Model\Schickzeiten;
use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Fehler aus der Analyse der Eltern-Funktionen im Web (Lesebestätigung, Krankmeldung,
 * Abmelden vom Hort, Startseite).
 */
class ElternWebKorrekturenTest extends TestCase
{
    use RefreshDatabase;

    private User $parent;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->parent = User::factory()->create(['password_changed_at' => now(), 'changePassword' => false]);
    }

    // ── Lesebestätigung ────────────────────────────────────────

    #[Test]
    public function read_receipt_is_stored_for_visible_post(): void
    {
        $group = Group::factory()->create(['owner_id' => null]);
        $this->parent->groups()->attach($group);
        $post = Post::factory()->create(['released' => 1, 'read_receipt' => 1]);
        $post->groups()->attach($group);

        $this->actingAs($this->parent)->post('post/readReceipt', ['post_id' => $post->id])->assertRedirect();

        $this->assertNotNull(ReadReceipts::where('post_id', $post->id)->where('user_id', $this->parent->id)->value('confirmed_at'));
    }

    #[Test]
    public function read_receipt_is_rejected_for_foreign_or_missing_post(): void
    {
        $post = Post::factory()->create(['released' => 1, 'read_receipt' => 1]);
        $post->groups()->attach(Group::factory()->create(['owner_id' => null]));

        $this->actingAs($this->parent)->post('post/readReceipt', ['post_id' => $post->id])->assertSessionHas('type', 'danger');
        $this->actingAs($this->parent)->post('post/readReceipt', ['post_id' => 999999])->assertSessionHas('type', 'danger');

        $this->assertSame(0, ReadReceipts::count());
    }

    // ── Krankmeldung ───────────────────────────────────────────

    #[Test]
    public function krankmeldung_stores_reportable_disease(): void
    {
        $disease = Disease::factory()->create(['aushang_dauer' => 7]);

        $this->actingAs($this->parent)->post('krankmeldung', [
            'name' => 'Test Kind',
            'start' => today()->toDateString(),
            'ende' => today()->addDay()->toDateString(),
            'kommentar' => 'Krank',
            'disease_id' => $disease->id,
        ])->assertSessionHas('type', 'success');

        $this->assertSame($disease->id, Krankmeldungen::firstOrFail()->disease_id);
        $this->assertSame(1, ActiveDisease::where('disease_id', $disease->id)->count());
    }

    #[Test]
    public function krankmeldung_without_disease_sends_zero_and_is_accepted(): void
    {
        $this->actingAs($this->parent)->post('krankmeldung', [
            'name' => 'Test Kind',
            'start' => today()->toDateString(),
            'ende' => today()->toDateString(),
            'kommentar' => 'Krank',
            'disease_id' => '0',
        ])->assertSessionHas('type', 'success');

        $this->assertNull(Krankmeldungen::firstOrFail()->disease_id);
    }

    #[Test]
    public function krankmeldung_end_before_start_is_rejected(): void
    {
        $this->actingAs($this->parent)->post('krankmeldung', [
            'name' => 'Test Kind',
            'start' => today()->addDays(2)->toDateString(),
            'ende' => today()->toDateString(),
            'kommentar' => 'Krank',
        ])->assertSessionHasErrors('ende');

        $this->assertSame(0, Krankmeldungen::count());
    }

    #[Test]
    public function duplicate_krankmeldung_is_not_stored_twice(): void
    {
        $data = [
            'name' => 'Test Kind',
            'start' => today()->toDateString(),
            'ende' => today()->addDay()->toDateString(),
            'kommentar' => 'Krank',
        ];

        $this->actingAs($this->parent)->post('krankmeldung', $data)->assertSessionHas('type', 'success');
        $this->actingAs($this->parent)->post('krankmeldung', $data)->assertSessionHas('type', 'info');

        $this->assertSame(1, Krankmeldungen::count());
        Mail::assertQueuedCount(1);
    }

    // ── Abmelden vom Hort ──────────────────────────────────────

    private function checkIn(array $attributes): ChildCheckIn
    {
        $child = Child::factory()->create();
        $child->parents()->attach($this->parent->id);

        return ChildCheckIn::create(array_merge([
            'child_id' => $child->id,
            'checked_in' => false,
            'checked_out' => false,
            'should_be' => null,
            'date' => today()->addDay(),
        ], $attributes));
    }

    #[Test]
    public function abmelden_works_for_future_day(): void
    {
        $checkIn = $this->checkIn([]);

        $this->actingAs($this->parent)->put("anwesenheit/{$checkIn->id}/abmelden")
            ->assertSessionHas('Meldung', 'Abmeldung wurde gespeichert');

        $this->assertFalse($checkIn->fresh()->should_be);
    }

    #[Test]
    public function abmelden_is_rejected_for_past_day_and_when_checked_in(): void
    {
        $past = $this->checkIn(['date' => today()->subDay(), 'should_be' => true]);
        $checkedIn = $this->checkIn(['date' => today(), 'checked_in' => true, 'should_be' => true]);

        $this->actingAs($this->parent)->put("anwesenheit/{$past->id}/abmelden")->assertSessionHas('type', 'warning');
        $this->actingAs($this->parent)->put("anwesenheit/{$checkedIn->id}/abmelden")->assertSessionHas('type', 'warning');

        $this->assertTrue($past->fresh()->should_be);
        $this->assertTrue($checkedIn->fresh()->should_be);
    }

    // ── Startseite ─────────────────────────────────────────────

    private function activateCare(): Child
    {
        Module::create(['setting' => 'Anwesenheitsliste', 'category' => 'module', 'options' => ['active' => '1', 'rights' => []]]);
        Cache::flush();

        $child = Child::factory()->create(['first_name' => 'Mia']);
        $child->parents()->attach($this->parent->id);

        return $child;
    }

    #[Test]
    public function dashboard_hint_counts_unanswered_attendance_queries_only(): void
    {
        $child = $this->activateCare();
        ChildCheckIn::create(['child_id' => $child->id, 'date' => today()->addDays(2), 'should_be' => false, 'checked_in' => false, 'checked_out' => false]);

        $this->withoutVite()->actingAs($this->parent)->get('/dashboard')->assertViewHas('openAttendanceSurveys', false);

        ChildCheckIn::create(['child_id' => $child->id, 'date' => today()->addDays(3), 'should_be' => null, 'checked_in' => false, 'checked_out' => false]);
        Cache::flush();

        $this->withoutVite()->actingAs($this->parent)->get('/dashboard')->assertViewHas('openAttendanceSurveys', true);
    }

    #[Test]
    public function dashboard_shows_regular_schickzeit_before_check_in(): void
    {
        $child = $this->activateCare();
        Schickzeiten::create([
            'child_id' => $child->id,
            'users_id' => $this->parent->id,
            'weekday' => today()->dayOfWeek,
            'type' => 'genau',
            'time' => '15:30:00',
        ]);

        $this->withoutVite()->actingAs($this->parent)->get('/dashboard')
            ->assertOk()
            ->assertSee('Nicht angemeldet')
            ->assertSee('Genau 15:30 Uhr');
    }
}
