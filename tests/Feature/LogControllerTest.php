<?php

namespace Tests\Feature;

use App\Model\SearchLog;
use App\Model\User;
use danielme85\LaravelLogToDB\LogToDB;
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

    private function logViewer(): User
    {
        Permission::findOrCreate('see logs', 'web');

        $user = User::factory()->create([
            'password_changed_at' => now(),
            'changePassword' => false,
        ]);
        $user->givePermissionTo('see logs');

        return $user;
    }

    private function createLog(string $message, array $attributes = []): void
    {
        $now = $attributes['created_at'] ?? now();

        LogToDB::model()->insert(array_merge([
            'message' => $message,
            'channel' => 'testing',
            'level' => 200,
            'level_name' => 'INFO',
            'unix_time' => $now->timestamp,
            'datetime' => $now->toDateTimeString(),
            'context' => null,
            'extra' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ], $attributes));
    }

    private function messagesFor(string $url): array
    {
        $response = $this->actingAs($this->logViewer())->get($url);
        $response->assertOk();

        return collect($response->viewData('logs')->items())->pluck('message')->all();
    }

    public function test_logs_can_be_filtered_by_login_category(): void
    {
        $this->createLog('Login successful');
        $this->createLog('Keycloak callback received');
        $this->createLog('Willkommens-E-Mail versendet an test@example.com');

        $messages = $this->messagesFor('/logs?category=login');

        $this->assertEqualsCanonicalizing(['Login successful', 'Keycloak callback received'], $messages);
    }

    public function test_logs_can_be_filtered_by_mail_category(): void
    {
        $this->createLog('Login successful');
        $this->createLog('E-Mail gesendet:');
        $this->createLog('Mailserver nicht erreichbar: timeout');

        $messages = $this->messagesFor('/logs?category=mail');

        $this->assertEqualsCanonicalizing(['E-Mail gesendet:', 'Mailserver nicht erreichbar: timeout'], $messages);
    }

    public function test_unknown_category_is_ignored(): void
    {
        $this->createLog('Login successful');
        $this->createLog('E-Mail gesendet:');

        $this->assertCount(2, $this->messagesFor('/logs?category=gibtsnicht'));
    }

    public function test_logs_can_be_filtered_by_date_range(): void
    {
        $this->createLog('alt', ['created_at' => now()->subDays(10)]);
        $this->createLog('neu', ['created_at' => now()]);

        $messages = $this->messagesFor('/logs?from='.now()->subDay()->toDateString().'&to='.now()->toDateString());

        $this->assertSame(['neu'], $messages);
    }

    public function test_search_includes_log_context(): void
    {
        $this->createLog('Login successful', ['context' => json_encode(['email' => 'eltern@example.com'])]);
        $this->createLog('Login successful', ['context' => json_encode(['email' => 'andere@example.com'])]);

        $this->assertCount(1, $this->messagesFor('/logs?search=eltern@example.com'));
    }
}
