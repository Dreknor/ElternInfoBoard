<?php

namespace Tests\Feature;

use App\Model\SearchLog;
use App\Model\User;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LogControllerTest extends TestCase
{
    public function test_logs_index_includes_search_statistics(): void
    {
        Permission::findOrCreate('see logs', 'web');

        $user = User::factory()->create([
            'password_changed_at' => now(),
            'changePassword' => false,
        ]);
        $user->givePermissionTo('see logs');

        SearchLog::factory()->create([
            'search_term' => 'Sommerfest',
            'nachrichten_count' => 2,
            'seiten_count' => 1,
            'results_count' => 3,
            'created_at' => now(),
        ]);

        SearchLog::factory()->withoutResults()->create([
            'search_term' => 'Elternabend',
            'created_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($user)->get('/logs');

        $response->assertOk();
        $response->assertViewHas('searchStats', function (array $searchStats) {
            return $searchStats['totalSearches'] === 2
                && $searchStats['resultsDistribution']['mit_treffern'] === 1
                && $searchStats['resultsDistribution']['ohne_treffer'] === 1
                && (float) $searchStats['averageResults'] === 1.5
                && $searchStats['topSearchTerms']->contains(fn ($term) => $term->search_term === 'Sommerfest' && (int) $term->searches === 1);
        });
    }

    public function test_cleanup_search_logs_deletes_only_old_entries(): void
    {
        Permission::findOrCreate('see logs', 'web');
        Permission::findOrCreate('delete logs', 'web');

        $user = User::factory()->create([
            'password_changed_at' => now(),
            'changePassword' => false,
        ]);
        $user->givePermissionTo(['see logs', 'delete logs']);

        $oldSearchLog = SearchLog::factory()->create(['created_at' => now()->subDays(120)]);
        $recentSearchLog = SearchLog::factory()->create(['created_at' => now()->subDays(10)]);

        $response = $this->actingAs($user)->delete('/logs/search/cleanup');

        $response->assertRedirect();
        $this->assertModelMissing($oldSearchLog);
        $this->assertModelExists($recentSearchLog);
    }
}
