<?php

namespace Tests\Feature;

use App\Model\Child;
use App\Model\ChildCheckIn;
use App\Model\Schickzeiten;
use App\Model\User;
use App\Settings\CareSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    // ═══════════════════════════════════════════════════════════════
    //  Verbesserung C: API Bulk-Endpunkt
    // ═══════════════════════════════════════════════════════════════

    /** @test */
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

    /** @test */
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

    /** @test */
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
}











