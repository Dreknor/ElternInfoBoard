<?php

namespace Tests\Feature;

use App\Model\Arbeitsgemeinschaft;
use App\Model\Child;
use App\Model\ChildCheckIn;
use App\Model\Group;
use App\Model\Holiday;
use App\Model\Krankmeldungen;
use App\Model\Schickzeiten;
use App\Model\Termin;
use App\Model\User;
use App\Model\Vertretung;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature-Tests für Feature 4: Persönlicher Wochenplan / Familien-Dashboard
 *
 * Abgedeckt:
 *  1. Unauthentizierter Zugriff → Redirect
 *  2. Nutzer ohne Kinder → leerer Zustand
 *  3. Nutzer mit Kind → Wochenplan mit Kindname
 *  4. Wochennavigation via ?week=
 *  5. Ferien werden angezeigt
 *  6. Vertretung erscheint
 *  7. Krankmeldung wird angezeigt
 *  8. Schickzeit wird angezeigt
 *  9. GTA erscheint
 * 10. Anwesenheitsabfrage mit Kommentar erscheint
 * 11. PDF-Export gibt 200 mit application/pdf zurück
 * 12. API GET /api/family/weekly gibt korrektes JSON zurück
 * 13. API GET /api/family/weekly/{child_id} gibt 403 für fremdes Kind zurück
 */
class FamilyWeeklyTest extends TestCase
{
    use RefreshDatabase;

    private User  $parent;
    private Child $child;
    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->group  = Group::factory()->create(['name' => 'TestKlasse', 'owner_id' => null]);
        $this->child  = Child::factory()->create(['group_id' => $this->group->id]);
        $this->parent = User::factory()->create([
            'password_changed_at' => now(),
            'changePassword'      => false,
            'changeSettings'      => false,
            'track_login'         => false,
            'is_active'           => true,
        ]);

        // Kind mit Elternteil verknüpfen
        $this->parent->children_rel()->attach($this->child->id);
    }

    // ── Hilfsmethode: ISO-Wochenanfang ──────────────────────────────────

    private function thisMonday(): Carbon
    {
        return Carbon::now()->startOfWeek(Carbon::MONDAY);
    }

    // ── 1. Unauthentizierter Zugriff ────────────────────────────────────

    #[Test]
    public function unauthenticated_user_is_redirected(): void
    {
        $response = $this->get(route('family.weekly'));

        $response->assertRedirect(route('login'));
    }

    // ── 2. Nutzer ohne Kinder sieht leeren Zustand ──────────────────────

    #[Test]
    public function user_without_children_sees_empty_state(): void
    {
        $userWithoutChild = User::factory()->create([
            'password_changed_at' => now(),
            'changePassword'      => false,
            'changeSettings'      => false,
            'track_login'         => false,
            'is_active'           => true,
        ]);

        $response = $this->actingAs($userWithoutChild)->get(route('family.weekly'));

        $response->assertOk();
        $response->assertSee('Keine Kinder verknüpft');
    }

    // ── 3. Nutzer mit Kind sieht Wochenplan ─────────────────────────────

    #[Test]
    public function user_with_child_sees_weekly_plan(): void
    {
        $response = $this->actingAs($this->parent)->get(route('family.weekly'));

        $response->assertOk();
        $response->assertSee($this->child->first_name);
    }

    // ── 4. Wochennavigation ─────────────────────────────────────────────

    #[Test]
    public function week_navigation_renders_correct_week(): void
    {
        $nextMonday = $this->thisMonday()->addWeek();
        $weekParam  = $nextMonday->format('Y-\WW');

        $response = $this->actingAs($this->parent)
            ->get(route('family.weekly', ['week' => $weekParam]));

        $response->assertOk();
        // Die KW-Nummer muss in der Wochenbeschriftung erscheinen
        $response->assertSee('KW ' . $nextMonday->isoWeek());
    }

    // ── 5. Ferien werden angezeigt ──────────────────────────────────────

    #[Test]
    public function holidays_are_shown_in_weekly_plan(): void
    {
        $monday = $this->thisMonday();

        Holiday::create([
            'year'       => $monday->year,
            'bundesland' => 'SN',
            'name'       => 'Testferien',
            'start'      => $monday->toDateString(),
            'end'        => $monday->copy()->addDays(4)->toDateString(),
        ]);

        $response = $this->actingAs($this->parent)->get(route('family.weekly'));

        $response->assertOk();
        $response->assertSee('Testferien');
    }

    // ── 6. Vertretung erscheint ─────────────────────────────────────────

    #[Test]
    public function vertretung_is_visible_in_weekly_plan(): void
    {
        $monday = $this->thisMonday();

        Vertretung::withoutGlobalScopes()->forceCreate([
            'date'            => $monday->toDateString(),
            'klasse_kurzform' => 'TestKlasse',
            'stunde'          => 2,
            'neuFach'         => 'Sport→Musik',
            'altFach'         => 'Musik',
            'lehrer'          => 'MUS',
            'comment'         => '',
            'klasse'          => $this->group->id,
        ]);

        $response = $this->actingAs($this->parent)->get(route('family.weekly'));

        $response->assertOk();
        $response->assertSee('Sport→Musik');
    }

    // ── 7. Krankmeldung wird angezeigt ──────────────────────────────────

    #[Test]
    public function sick_day_is_highlighted(): void
    {
        $monday = $this->thisMonday();

        Krankmeldungen::create([
            'child_id' => $this->child->id,
            'users_id' => $this->parent->id,
            'name'     => $this->child->first_name,
            'start'    => $monday->toDateString(),
            'ende'     => $monday->copy()->addDays(2)->toDateString(),
            'kommentar' => '',
        ]);

        $response = $this->actingAs($this->parent)->get(route('family.weekly'));

        $response->assertOk();
        $response->assertSee('krank');
    }

    // ── 8. Schickzeit wird angezeigt ────────────────────────────────────

    #[Test]
    public function schickzeit_is_shown_in_weekly_plan(): void
    {
        $wednesday = $this->thisMonday()->addDays(2);

        Schickzeiten::create([
            'child_id'      => $this->child->id,
            'users_id'      => $this->parent->id,
            'child_name'    => $this->child->first_name,
            'specific_date' => $wednesday->toDateString(),
            'time'          => '15:30:00',
            'type'          => 'genau',
            'changedBy'     => $this->parent->id,
        ]);

        $response = $this->actingAs($this->parent)->get(route('family.weekly'));

        $response->assertOk();
        $response->assertSee('15:30');
    }

    // ── 9. GTA erscheint ────────────────────────────────────────────────

    #[Test]
    public function gta_is_shown_for_correct_day(): void
    {
        $monday = $this->thisMonday();

        /** @var Arbeitsgemeinschaft $ag */
        $ag = Arbeitsgemeinschaft::create([
            'name'       => 'Töpfern AG',
            'weekday'    => 1, // Montag = Carbon::MONDAY = 1
            'start_time' => '14:30:00',
            'end_time'   => '16:00:00',
            'end_date'   => $monday->copy()->addMonths(6)->toDateString(),
        ]);
        $ag->participants()->attach($this->child->id);

        $response = $this->actingAs($this->parent)->get(route('family.weekly'));

        $response->assertOk();
        $response->assertSee('Töpfern AG');
    }

    // ── 10 (neu). Anwesenheitsabfrage mit Kommentar erscheint im Wochenplan ──

    #[Test]
    public function anwesenheitsabfrage_with_comment_is_shown_in_weekly_plan(): void
    {
        $monday = $this->thisMonday();

        ChildCheckIn::create([
            'child_id'  => $this->child->id,
            'date'      => $monday->toDateString(),
            'lock_at'   => $monday->copy()->addDays(2)->toDateString(),
            'comment'   => 'Bitte Anwesenheit für Schullandheim bestätigen',
            'should_be' => null,
        ]);

        $response = $this->actingAs($this->parent)->get(route('family.weekly'));

        $response->assertOk();
        $response->assertSee('Bitte Anwesenheit für Schullandheim bestätigen');
        $response->assertSee('📋');
        $response->assertSee('Offen');
    }

    // ── 11. PDF-Export ──────────────────────────────────────────────────

    #[Test]
    public function pdf_export_returns_pdf_response(): void
    {
        $response = $this->actingAs($this->parent)
            ->get(route('family.weekly.pdf'));

        $response->assertOk();
        $this->assertStringContainsString(
            'application/pdf',
            $response->headers->get('Content-Type')
        );
    }

    // ── 12. API: GET /api/family/weekly ─────────────────────────────────

    #[Test]
    public function api_weekly_returns_json_for_authenticated_user(): void
    {
        Sanctum::actingAs($this->parent);

        $response = $this->getJson('/api/family/weekly');

        $response->assertOk();
        $response->assertJsonStructure([
            'week_label',
            'week_start',
            'week_end',
            'holidays',
            'children' => [['child_id', 'child_name', 'klasse', 'summary', 'days', 'termine']],
        ]);

        $this->assertSame($this->child->id, $response->json('children.0.child_id'));
    }

    // ── 13. API: Fremdes Kind → 403 ────────────────────────────────────

    #[Test]
    public function api_show_returns_403_for_foreign_child(): void
    {
        $otherChild = Child::factory()->create();
        $otherUser  = User::factory()->create([
            'password_changed_at' => now(),
            'changePassword'      => false,
            'is_active'           => true,
        ]);
        $otherUser->children_rel()->attach($otherChild->id);

        Sanctum::actingAs($this->parent); // Elternteil A

        // versucht, Kind von Elternteil B abzurufen
        $response = $this->getJson("/api/family/weekly/{$otherChild->id}");

        $response->assertStatus(403);
    }
}






