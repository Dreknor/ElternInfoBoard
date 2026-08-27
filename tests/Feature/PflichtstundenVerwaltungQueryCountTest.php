<?php

namespace Tests\Feature;

use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PflichtstundenVerwaltungQueryCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_query_count_stays_low_with_many_families(): void
    {
        Permission::findOrCreate('view Pflichtstunden');
        Permission::findOrCreate('edit Pflichtstunden');

        $admin = User::factory()->create(['changePassword' => false]);
        $admin->givePermissionTo(['view Pflichtstunden', 'edit Pflichtstunden']);

        // Simulate a realistic number of families to catch N+1 regressions.
        $users = User::factory()->count(60)->create();
        foreach ($users as $user) {
            $user->givePermissionTo('view Pflichtstunden');
        }

        $queryCount = 0;
        DB::listen(function () use (&$queryCount) {
            $queryCount++;
        });

        $response = $this->actingAs($admin)->get(route('pflichtstunden.indexVerwaltung'));
        $response->assertOk();

        // Should stay roughly constant regardless of family count (no N+1 per family).
        $this->assertLessThan(60, $queryCount, "Expected far fewer than 60 queries for 60 families, got {$queryCount}.");
    }
}
