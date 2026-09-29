<?php

namespace Tests\Feature;

use App\Model\Pflichtstunde;
use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PflichtstundenReportPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_the_extended_pdf_report(): void
    {
        Permission::findOrCreate('edit Pflichtstunden');

        $admin = User::factory()->create(['changePassword' => false]);
        $admin->givePermissionTo('edit Pflichtstunden');

        $start = now()->startOfYear()->addDays(10)->startOfDay();
        $end = $start->copy()->addHours(4);

        Pflichtstunde::create([
            'user_id' => $admin->id,
            'start' => $start,
            'end' => $end,
            'description' => 'Genehmigte Hilfe',
            'approved' => true,
            'approved_at' => now(),
            'approved_by' => $admin->id,
            'created_at' => now()->subDay(),
            'updated_at' => now(),
        ]);

        Pflichtstunde::create([
            'user_id' => $admin->id,
            'start' => $start->copy()->addDays(1),
            'end' => $start->copy()->addDays(1)->addHours(3),
            'description' => 'Noch ausstehend',
            'approved' => false,
            'rejected' => false,
            'created_at' => now()->subHours(2),
            'updated_at' => now(),
        ]);

        Pflichtstunde::create([
            'user_id' => $admin->id,
            'start' => $start->copy()->addDays(2),
            'end' => $start->copy()->addDays(2)->addHours(14),
            'description' => 'Falsche Dauer',
            'approved' => false,
            'rejected' => true,
            'rejection_reason' => 'Falscher Zeitraum',
            'rejected_by' => $admin->id,
            'rejected_at' => now(),
            'created_at' => now()->subHours(6),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('pflichtstunden.report.pdf', ['year' => $start->year]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_admin_can_open_verwaltungs_dashboard_with_family_overview(): void
    {
        Permission::findOrCreate('view Pflichtstunden');
        Permission::findOrCreate('edit Pflichtstunden');

        $admin = User::factory()->create(['changePassword' => false]);
        $admin->givePermissionTo('view Pflichtstunden');
        $admin->givePermissionTo('edit Pflichtstunden');

        $user = User::factory()->create(['name' => 'Falk Wenzel']);
        $user->givePermissionTo('view Pflichtstunden');
        $periodStart = now()->startOfYear()->addDays(10)->startOfDay();

        Pflichtstunde::create([
            'user_id' => $user->id,
            'start' => $periodStart,
            'end' => $periodStart->copy()->addHours(2),
            'description' => 'Familienüberblick Test',
            'approved' => true,
            'approved_at' => now(),
            'approved_by' => $admin->id,
            'created_at' => now()->subDay(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('pflichtstunden.indexVerwaltung'));

        $response->assertOk();
        $response->assertSee('Übersicht der Pflichtstunden');
        $response->assertSee('Falk Wenzel');
    }

    public function test_plausibility_report_only_includes_approved_entries(): void
    {
        Permission::findOrCreate('edit Pflichtstunden');

        $admin = User::factory()->create(['changePassword' => false]);
        $admin->givePermissionTo('edit Pflichtstunden');

        $periodStart = now()->startOfYear()->addDays(15)->startOfDay();

        $approved = Pflichtstunde::create([
            'user_id' => $admin->id,
            'start' => $periodStart->copy(),
            'end' => $periodStart->copy()->addHours(14),
            'description' => 'Genehmigt und auffällig',
            'approved' => true,
            'approved_at' => now(),
            'approved_by' => $admin->id,
            'created_at' => now()->subDay(),
            'updated_at' => now(),
        ]);

        $rejected = Pflichtstunde::create([
            'user_id' => $admin->id,
            'start' => $periodStart->copy()->addDays(1),
            'end' => $periodStart->copy()->addDays(1)->addHours(16),
            'description' => 'Abgelehnt und auffällig',
            'approved' => false,
            'rejected' => true,
            'rejection_reason' => 'Falscher Zeitraum',
            'rejected_by' => $admin->id,
            'rejected_at' => now(),
            'created_at' => now()->subHours(2),
            'updated_at' => now(),
        ]);

        $deleted = Pflichtstunde::create([
            'user_id' => $admin->id,
            'start' => $periodStart->copy()->addDays(2),
            'end' => $periodStart->copy()->addDays(2)->addHours(18),
            'description' => 'Gelöscht und auffällig',
            'approved' => true,
            'approved_at' => now(),
            'approved_by' => $admin->id,
            'created_at' => now()->subHours(3),
            'updated_at' => now(),
        ]);
        $deleted->delete();

        $report = app(\App\Services\PflichtstundenReportPdfService::class)
            ->buildReport($periodStart, $periodStart->copy()->addDays(30), 'family_name', false);

        $ids = collect($report['error_entries'])->pluck('id')->all();

        $this->assertContains($approved->id, $ids);
        $this->assertNotContains($rejected->id, $ids);
        $this->assertNotContains($deleted->id, $ids);
    }

    public function test_anonymized_report_shows_required_hours_and_outstanding_amount_without_pending_or_suspicious_entries(): void
    {
        Permission::findOrCreate('view Pflichtstunden');

        $first = User::factory()->create(['name' => 'Erste Familie']);
        $second = User::factory()->create(['name' => 'Zweite Familie']);
        $first->givePermissionTo('view Pflichtstunden');
        $second->givePermissionTo('view Pflichtstunden');

        $periodStart = now()->startOfYear()->addDays(15)->startOfDay();
        Pflichtstunde::create([
            'user_id' => $first->id,
            'start' => $periodStart->copy(),
            'end' => $periodStart->copy()->addHours(13),
            'description' => 'Auffällige Hilfe',
            'approved' => true,
        ]);
        Pflichtstunde::create([
            'user_id' => $first->id,
            'start' => $periodStart->copy()->addDays(1),
            'end' => $periodStart->copy()->addDays(1)->addHours(3),
            'description' => 'Wartende Hilfe',
            'approved' => false,
            'rejected' => false,
        ]);

        $service = app(\App\Services\PflichtstundenReportPdfService::class);
        $periodEnd = $periodStart->copy()->addDays(30);
        $anonymous = $service->buildReport($periodStart, $periodEnd, 'family_name', true);
        $named = $service->buildReport($periodStart, $periodEnd, 'family_name', false);

        $this->assertEquals(40, $anonymous['summary']['total_required_hours']);
        $this->assertEquals(675, $anonymous['summary']['total_billed_amount']);
        $this->assertCount(0, $anonymous['error_entries']);
        $this->assertCount(1, $named['error_entries']);

        $anonymousHtml = view('pflichtstunden.report-pdf', $anonymous)->render();
        $namedHtml = view('pflichtstunden.report-pdf', $named)->render();

        $this->assertStringContainsString('Soll-Stunden', $anonymousHtml);
        $this->assertStringContainsString('40,00h', $anonymousHtml);
        $this->assertStringContainsString('675,00 €', $anonymousHtml);
        $this->assertStringNotContainsString('Wartende Einträge', $anonymousHtml);
        $this->assertStringNotContainsString('Auffällige Einträge', $anonymousHtml);
        $this->assertStringNotContainsString('Ausstehend', $anonymousHtml);
        $this->assertStringNotContainsString('Auffällige Hilfe', $anonymousHtml);
        $this->assertStringNotContainsString('Erste Familie', $anonymousHtml);
        $this->assertStringContainsString('Wartende Einträge', $namedHtml);
        $this->assertStringContainsString('Auffällige Einträge', $namedHtml);
        $this->assertStringContainsString('Ausstehend', $namedHtml);
    }
}
