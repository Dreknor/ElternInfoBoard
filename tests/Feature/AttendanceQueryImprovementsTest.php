<?php

namespace Tests\Feature;

use App\Model\Child;
use App\Model\ChildCheckIn;
use App\Model\Schickzeiten;
use App\Model\User;
use App\Settings\CareSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Feature-Tests für Sprint 2: Anwesenheitsabfragen-Verbesserungen (A + C)
 */
class AttendanceQueryImprovementsTest extends TestCase
{
    use RefreshDatabase;

    private User $parent;
    private User $admin;
    private Child $child;

    protected function setUp(): void
    {
        parent::setUp();

        // Permission erstellen
        Permission::findOrCreate('edit schickzeiten', 'web');

        $this->admin = User::factory()->create(['password_changed_at' => now()]);
        $this->admin->givePermissionTo('edit schickzeiten');

        $this->parent = User::factory()->create(['password_changed_at' => now()]);
        $this->child = Child::factory()->create();
        $this->child->parents()->attach($this->parent->id);
    }

    // ═══════════════════════════════════════════════════════════════
    //  Verbesserung A: Rücklauf-Dashboard-Logik
    // ═══════════════════════════════════════════════════════════════

    #[Test]
    public function response_rate_calculation_works_correctly(): void
    {
        // Simuliere die Rücklauf-Berechnung wie im Controller
        $checkIns = collect([
            (object) ['should_be' => true, 'child_id' => 1],
            (object) ['should_be' => false, 'child_id' => 2],
            (object) ['should_be' => null, 'child_id' => 3],
        ]);

        $coming = $checkIns->where('should_be', true)->count();
        $notComing = $checkIns->whereStrict('should_be', false)->count();
        $pending = $checkIns->whereNull('should_be')->count();
        $responded = $checkIns->whereNotNull('should_be')->count();
        $total = $checkIns->count();
        $responseRate = $total > 0 ? round($responded / $total * 100) : 0;

        $this->assertEquals(1, $coming);
        $this->assertEquals(1, $notComing);
        $this->assertEquals(1, $pending);
        $this->assertEquals(67, $responseRate);
        $this->assertEquals(3, $total);
    }

    #[Test]
    public function response_rate_is_100_when_all_answered(): void
    {
        $checkIns = collect([
            (object) ['should_be' => true, 'child_id' => 1],
            (object) ['should_be' => false, 'child_id' => 2],
        ]);

        $responded = $checkIns->whereNotNull('should_be')->count();
        $total = $checkIns->count();
        $responseRate = $total > 0 ? round($responded / $total * 100) : 0;

        $this->assertEquals(100, $responseRate);
        $this->assertEquals(0, $checkIns->whereNull('should_be')->count());
    }

    #[Test]
    public function response_rate_is_0_when_empty(): void
    {
        $checkIns = collect();

        $responded = $checkIns->whereNotNull('should_be')->count();
        $total = $checkIns->count();
        $responseRate = $total > 0 ? round($responded / $total * 100) : 0;

        $this->assertEquals(0, $responseRate);
    }

    #[Test]
    public function view_file_contains_ruecklauf_elements(): void
    {
        $viewPath = resource_path('views/schickzeiten/index_verwaltung.blade.php');
        $this->assertFileExists($viewPath);

        $content = file_get_contents($viewPath);
        $this->assertStringContainsString('response_rate', $content);
        $this->assertStringContainsString('pending_children', $content);
        $this->assertStringContainsString('Alle Eltern haben geantwortet', $content);
        $this->assertStringContainsString('beantwortet', $content);
    }

    // ═══════════════════════════════════════════════════════════════
    //  Verbesserung C: Bulk-Antwort
    // ═══════════════════════════════════════════════════════════════

    #[Test]
    public function bulk_update_attendance_sets_should_be_for_multiple_checkins(): void
    {
        $checkIn1 = ChildCheckIn::create([
            'child_id' => $this->child->id,
            'date' => now()->addDays(5)->toDateString(),
            'should_be' => null,
            'lock_at' => now()->addDays(3)->toDateString(),
            'checked_in' => false,
            'checked_out' => false,
        ]);

        $checkIn2 = ChildCheckIn::create([
            'child_id' => $this->child->id,
            'date' => now()->addDays(6)->toDateString(),
            'should_be' => null,
            'lock_at' => now()->addDays(3)->toDateString(),
            'checked_in' => false,
            'checked_out' => false,
        ]);

        $response = $this->actingAs($this->parent)
            ->withoutMiddleware(\App\Http\Middleware\PasswordExpired::class)
            ->post(route('attendance.bulk-update'), [
            'responses' => [
                'key1' => ['check_in_id' => $checkIn1->id, 'should_be' => '1'],
                'key2' => ['check_in_id' => $checkIn2->id, 'should_be' => '0'],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('type', 'success');

        $this->assertTrue($checkIn1->fresh()->should_be);
        $this->assertFalse($checkIn2->fresh()->should_be);
    }

    #[Test]
    public function bulk_update_attendance_creates_schickzeit_when_should_be_true(): void
    {
        $checkIn = ChildCheckIn::create([
            'child_id' => $this->child->id,
            'date' => now()->addDays(5)->toDateString(),
            'should_be' => null,
            'lock_at' => now()->addDays(3)->toDateString(),
            'checked_in' => false,
            'checked_out' => false,
        ]);

        $response = $this->actingAs($this->parent)
            ->withoutMiddleware(\App\Http\Middleware\PasswordExpired::class)
            ->post(route('attendance.bulk-update'), [
            'responses' => [
                'key1' => [
                    'check_in_id' => $checkIn->id,
                    'should_be' => '1',
                    'schickzeit_time' => '15:30',
                    'schickzeit_type' => 'genau',
                ],
            ],
        ]);

        $response->assertRedirect();

        // CheckIn sollte bestätigt sein
        $this->assertTrue($checkIn->fresh()->should_be);

        // Schickzeit sollte erstellt worden sein
        $this->assertTrue(
            Schickzeiten::withTrashed()->where('child_id', $this->child->id)
                ->whereDate('specific_date', now()->addDays(5)->toDateString())
                ->exists()
        );
    }

    #[Test]
    public function bulk_update_attendance_skips_locked_checkins(): void
    {
        $checkIn = ChildCheckIn::create([
            'child_id' => $this->child->id,
            'date' => now()->addDays(5)->toDateString(),
            'should_be' => null,
            'lock_at' => now()->subDays(1)->toDateString(), // Bereits abgelaufen
            'checked_in' => false,
            'checked_out' => false,
        ]);

        $response = $this->actingAs($this->parent)
            ->withoutMiddleware(\App\Http\Middleware\PasswordExpired::class)
            ->post(route('attendance.bulk-update'), [
            'responses' => [
                'key1' => ['check_in_id' => $checkIn->id, 'should_be' => '1'],
            ],
        ]);

        $response->assertRedirect();

        // CheckIn sollte unverändert bleiben (null wird zu false gecastet)
        $this->assertNull($checkIn->fresh()->getRawOriginal('should_be'));
    }

    #[Test]
    public function bulk_update_attendance_rejects_foreign_children(): void
    {
        $otherChild = Child::factory()->create();
        $checkIn = ChildCheckIn::create([
            'child_id' => $otherChild->id,
            'date' => now()->addDays(5)->toDateString(),
            'should_be' => null,
            'lock_at' => now()->addDays(3)->toDateString(),
            'checked_in' => false,
            'checked_out' => false,
        ]);

        $response = $this->actingAs($this->parent)
            ->withoutMiddleware(\App\Http\Middleware\PasswordExpired::class)
            ->post(route('attendance.bulk-update'), [
            'responses' => [
                'key1' => ['check_in_id' => $checkIn->id, 'should_be' => '1'],
            ],
        ]);

        $response->assertRedirect();

        // CheckIn sollte unverändert bleiben
        $this->assertNull($checkIn->fresh()->getRawOriginal('should_be'));
    }

    #[Test]
    public function bulk_update_attendance_does_not_create_schickzeit_when_declined(): void
    {
        $checkIn = ChildCheckIn::create([
            'child_id' => $this->child->id,
            'date' => now()->addDays(5)->toDateString(),
            'should_be' => null,
            'lock_at' => now()->addDays(3)->toDateString(),
            'checked_in' => false,
            'checked_out' => false,
        ]);

        $response = $this->actingAs($this->parent)
            ->withoutMiddleware(\App\Http\Middleware\PasswordExpired::class)
            ->post(route('attendance.bulk-update'), [
            'responses' => [
                'key1' => [
                    'check_in_id' => $checkIn->id,
                    'should_be' => '0',
                    'schickzeit_time' => '15:30',
                ],
            ],
        ]);

        $response->assertRedirect();

        // Keine Schickzeit bei Abmeldung
        $this->assertFalse(
            Schickzeiten::withTrashed()->where('child_id', $this->child->id)
                ->whereDate('specific_date', now()->addDays(5)->toDateString())
                ->exists()
        );
    }

    #[Test]
    public function bulk_update_requires_authentication(): void
    {
        $checkIn = ChildCheckIn::create([
            'child_id' => $this->child->id,
            'date' => now()->addDays(5)->toDateString(),
            'should_be' => null,
            'lock_at' => now()->addDays(3)->toDateString(),
            'checked_in' => false,
            'checked_out' => false,
        ]);

        $response = $this->post(route('attendance.bulk-update'), [
            'responses' => [
                'key1' => ['check_in_id' => $checkIn->id, 'should_be' => '1'],
            ],
        ]);

        $response->assertRedirect(route('login'));
    }

    // ═══════════════════════════════════════════════════════════════
    //  Verbesserung C: API Bulk-Endpunkt
    // ═══════════════════════════════════════════════════════════════

    #[Test]
    public function api_bulk_update_attendance_works(): void
    {
        $checkIn = ChildCheckIn::create([
            'child_id' => $this->child->id,
            'date' => now()->addDays(5)->toDateString(),
            'should_be' => null,
            'lock_at' => now()->addDays(3)->toDateString(),
            'checked_in' => false,
            'checked_out' => false,
        ]);

        $response = $this->actingAs($this->parent, 'sanctum')
            ->postJson('/api/parent/attendance-queries/bulk', [
                'responses' => [
                    ['check_in_id' => $checkIn->id, 'should_be' => true],
                ],
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'updated' => 1,
            'skipped' => 0,
        ]);

        $this->assertTrue($checkIn->fresh()->should_be);
    }

    #[Test]
    public function api_bulk_update_with_schickzeit(): void
    {
        $checkIn = ChildCheckIn::create([
            'child_id' => $this->child->id,
            'date' => now()->addDays(5)->toDateString(),
            'should_be' => null,
            'lock_at' => now()->addDays(3)->toDateString(),
            'checked_in' => false,
            'checked_out' => false,
        ]);

        $response = $this->actingAs($this->parent, 'sanctum')
            ->postJson('/api/parent/attendance-queries/bulk', [
                'responses' => [
                    [
                        'check_in_id' => $checkIn->id,
                        'should_be' => true,
                        'schickzeit_time' => '16:00',
                    ],
                ],
            ]);

        $response->assertOk();
        $response->assertJson(['updated' => 1]);

        // Schickzeit sollte erstellt sein
        $this->assertTrue(
            Schickzeiten::withTrashed()->where('child_id', $this->child->id)
                ->whereDate('specific_date', now()->addDays(5)->toDateString())
                ->exists()
        );
    }

    #[Test]
    public function api_bulk_update_skips_foreign_children(): void
    {
        $otherChild = Child::factory()->create();
        $checkIn = ChildCheckIn::create([
            'child_id' => $otherChild->id,
            'date' => now()->addDays(5)->toDateString(),
            'should_be' => null,
            'lock_at' => now()->addDays(3)->toDateString(),
            'checked_in' => false,
            'checked_out' => false,
        ]);

        $response = $this->actingAs($this->parent, 'sanctum')
            ->postJson('/api/parent/attendance-queries/bulk', [
                'responses' => [
                    ['check_in_id' => $checkIn->id, 'should_be' => true],
                ],
            ]);

        $response->assertOk();
        $response->assertJson([
            'updated' => 0,
            'skipped' => 1,
        ]);

        // Unverändert
        $this->assertNull($checkIn->fresh()->getRawOriginal('should_be'));
    }

    #[Test]
    public function api_bulk_update_requires_authentication(): void
    {
        $checkIn = ChildCheckIn::create([
            'child_id' => $this->child->id,
            'date' => now()->addDays(5)->toDateString(),
            'should_be' => null,
            'lock_at' => now()->addDays(3)->toDateString(),
            'checked_in' => false,
            'checked_out' => false,
        ]);

        $response = $this->postJson('/api/parent/attendance-queries/bulk', [
            'responses' => [
                ['check_in_id' => $checkIn->id, 'should_be' => true],
            ],
        ]);

        $response->assertUnauthorized();
    }

    #[Test]
    public function api_bulk_update_validates_empty_responses(): void
    {
        $response = $this->actingAs($this->parent, 'sanctum')
            ->postJson('/api/parent/attendance-queries/bulk', [
                'responses' => [],
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('responses');
    }

    // ═══════════════════════════════════════════════════════════════
    //  Verbesserung C: Bulk-View
    // ═══════════════════════════════════════════════════════════════

    #[Test]
    public function bulk_form_view_exists(): void
    {
        $viewPath = resource_path('views/schickzeiten/index.blade.php');
        $this->assertFileExists($viewPath);

        $content = file_get_contents($viewPath);
        $this->assertStringContainsString('attendance.bulk-update', $content);
        $this->assertStringContainsString('bulk-attendance-form', $content);
    }

    // ═══════════════════════════════════════════════════════════════
    //  Feature 6E: Statistiken & Ferienplan-PDF
    // ═══════════════════════════════════════════════════════════════

    #[Test]
    public function attendance_stats_returns_json_with_correct_structure(): void
    {
        Permission::findOrCreate('edit schickzeiten', 'web');
        $admin = User::factory()->create(['password_changed_at' => now()]);
        $admin->givePermissionTo('edit schickzeiten');

        ChildCheckIn::create([
            'child_id'    => $this->child->id,
            'date'        => now()->addDays(3)->toDateString(),
            'should_be'   => true,
            'lock_at'     => now()->addDays(2)->toDateString(),
            'checked_in'  => false,
            'checked_out' => false,
        ]);

        ChildCheckIn::create([
            'child_id'    => $this->child->id,
            'date'        => now()->addDays(3)->toDateString(),
            'should_be'   => null,
            'lock_at'     => now()->addDays(2)->toDateString(),
            'checked_in'  => false,
            'checked_out' => false,
        ]);

        $response = $this->actingAs($admin)
            ->withoutMiddleware(\App\Http\Middleware\PasswordExpired::class)
            ->getJson(route('care.abfrage.stats', [
                'date_start' => now()->addDays(1)->toDateString(),
                'date_end'   => now()->addDays(5)->toDateString(),
            ]));

        $response->assertOk();
        $response->assertJsonStructure([
            'by_date',
            'by_group',
            'response_rate',
            'forecast_max_children',
            'avg_per_day',
            'pending_count',
            'total',
        ]);

        $this->assertEquals(2, $response->json('total'));
        $this->assertEquals(1, $response->json('pending_count'));
        $this->assertEquals(50.0, $response->json('response_rate'));
        $this->assertEquals(1, $response->json('forecast_max_children'));
    }

    #[Test]
    public function attendance_stats_requires_authentication(): void
    {
        $response = $this->getJson(route('care.abfrage.stats', [
            'date_start' => now()->toDateString(),
            'date_end'   => now()->addDays(5)->toDateString(),
        ]));

        $response->assertUnauthorized();
    }

    #[Test]
    public function attendance_stats_requires_edit_schickzeiten_permission(): void
    {
        $user = User::factory()->create(['password_changed_at' => now()]);

        $response = $this->actingAs($user)
            ->withoutMiddleware(\App\Http\Middleware\PasswordExpired::class)
            ->getJson(route('care.abfrage.stats', [
                'date_start' => now()->toDateString(),
                'date_end'   => now()->addDays(5)->toDateString(),
            ]));

        $response->assertForbidden();
    }

    #[Test]
    public function attendance_stats_validates_date_range(): void
    {
        Permission::findOrCreate('edit schickzeiten', 'web');
        $admin = User::factory()->create(['password_changed_at' => now()]);
        $admin->givePermissionTo('edit schickzeiten');

        // date_end vor date_start → Validierungsfehler
        $response = $this->actingAs($admin)
            ->withoutMiddleware(\App\Http\Middleware\PasswordExpired::class)
            ->getJson(route('care.abfrage.stats', [
                'date_start' => now()->addDays(5)->toDateString(),
                'date_end'   => now()->toDateString(),
            ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('date_end');
    }

    #[Test]
    public function attendance_stats_returns_zero_for_empty_range(): void
    {
        Permission::findOrCreate('edit schickzeiten', 'web');
        $admin = User::factory()->create(['password_changed_at' => now()]);
        $admin->givePermissionTo('edit schickzeiten');

        $response = $this->actingAs($admin)
            ->withoutMiddleware(\App\Http\Middleware\PasswordExpired::class)
            ->getJson(route('care.abfrage.stats', [
                'date_start' => '2099-01-01',
                'date_end'   => '2099-01-07',
            ]));

        $response->assertOk();
        $this->assertEquals(0, $response->json('total'));
        $this->assertEquals(0, $response->json('response_rate'));
        $this->assertEquals(0, $response->json('forecast_max_children'));
    }

    #[Test]
    public function ferienplan_pdf_download_works(): void
    {
        Permission::findOrCreate('edit schickzeiten', 'web');
        $admin = User::factory()->create(['password_changed_at' => now()]);
        $admin->givePermissionTo('edit schickzeiten');

        ChildCheckIn::create([
            'child_id'    => $this->child->id,
            'date'        => now()->addDays(3)->toDateString(),
            'should_be'   => true,
            'lock_at'     => now()->addDays(2)->toDateString(),
            'checked_in'  => false,
            'checked_out' => false,
        ]);

        $response = $this->actingAs($admin)
            ->withoutMiddleware(\App\Http\Middleware\PasswordExpired::class)
            ->post(route('care.abfrage.ferienplan.pdf'), [
                'date_start' => now()->addDays(1)->toDateString(),
                'date_end'   => now()->addDays(7)->toDateString(),
            ]);

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    #[Test]
    public function ferienplan_pdf_requires_authentication(): void
    {
        $response = $this->post(route('care.abfrage.ferienplan.pdf'), [
            'date_start' => now()->toDateString(),
            'date_end'   => now()->addDays(5)->toDateString(),
        ]);

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function ferienplan_pdf_requires_edit_schickzeiten_permission(): void
    {
        $user = User::factory()->create(['password_changed_at' => now()]);

        $response = $this->actingAs($user)
            ->withoutMiddleware(\App\Http\Middleware\PasswordExpired::class)
            ->post(route('care.abfrage.ferienplan.pdf'), [
                'date_start' => now()->toDateString(),
                'date_end'   => now()->addDays(5)->toDateString(),
            ]);

        $response->assertForbidden();
    }

    #[Test]
    public function ferienplan_pdf_validates_date_range(): void
    {
        Permission::findOrCreate('edit schickzeiten', 'web');
        $admin = User::factory()->create(['password_changed_at' => now()]);
        $admin->givePermissionTo('edit schickzeiten');

        $response = $this->actingAs($admin)
            ->withoutMiddleware(\App\Http\Middleware\PasswordExpired::class)
            ->post(route('care.abfrage.ferienplan.pdf'), [
                'date_start' => now()->addDays(5)->toDateString(),
                'date_end'   => now()->toDateString(), // Vor date_start → ungültig
            ]);

        $response->assertSessionHasErrors('date_end');
    }

    #[Test]
    public function ferienplan_pdf_view_file_exists(): void
    {
        $this->assertFileExists(resource_path('views/pdf/ferienplan.blade.php'));
    }

    #[Test]
    public function statistics_widget_is_present_in_verwaltung_view(): void
    {
        $viewPath = resource_path('views/schickzeiten/index_verwaltung.blade.php');
        $this->assertFileExists($viewPath);

        $content = file_get_contents($viewPath);
        $this->assertStringContainsString('care.abfrage.stats', $content);
        $this->assertStringContainsString('care.abfrage.ferienplan.pdf', $content);
        $this->assertStringContainsString('Statistiken & Planungshilfe', $content);
        $this->assertStringContainsString('forecast_max_children', $content);
    }
}
