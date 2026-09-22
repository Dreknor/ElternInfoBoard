<?php

namespace Tests\Feature;

use App\Model\SearchLog;
use Tests\TestCase;

class CleanupSearchLogsCommandTest extends TestCase
{
    public function test_it_deletes_only_search_logs_older_than_given_days(): void
    {
        $oldSearchLog = SearchLog::factory()->create(['created_at' => now()->subDays(100)]);
        $recentSearchLog = SearchLog::factory()->create(['created_at' => now()->subDays(5)]);

        $this->artisan('search-logs:cleanup', ['--days' => 90])
            ->assertExitCode(0);

        $this->assertModelMissing($oldSearchLog);
        $this->assertModelExists($recentSearchLog);
    }
}
