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

        $admin = User::factory()->create();
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
}
