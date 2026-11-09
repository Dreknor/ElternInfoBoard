<?php

namespace Tests\Feature\API\V1;

use App\Mcp\ReadOnlyApiGateway;
use App\Mcp\ToolCatalog;
use App\Model\User;
use App\Services\IntegrationTokens;
use App\Services\McpTokens;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;

/**
 * Absicherung des MCP-Servers für einen Eltern-Chatbot: nur freigeschaltete Instanzen, nur Bearer-Token,
 * kurzlebige MCP-Tokens ohne Zugriff auf die übrige API, kein Schreibzugriff.
 */
class McpSecurityTest extends AppApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['mcp_server.enabled' => true]);
    }

    private function bearer(string $token): static
    {
        // Sanctum merkt sich den Nutzer je Guard – zwischen Anfragen mit wechselnden Tokens zurücksetzen.
        $this->app['auth']->forgetGuards();

        return $this->withHeader('Authorization', 'Bearer '.$token);
    }

    private function rpc(string $method, array $params = [])
    {
        return $this->postJson('/api/v1/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => (object) $params], [
            'Accept' => 'application/json, text/event-stream',
        ]);
    }

    private function mintToken(User $user): string
    {
        $this->app['auth']->forgetGuards();

        return $this->actingAs($user)->postJson('/chat/mcp-token')->assertOk()->json('token');
    }

    /** @test */
    public function server_and_token_endpoint_are_hidden_unless_enabled_for_the_instance(): void
    {
        config(['mcp_server.enabled' => false]);
        $user = $this->parentIn($this->group());

        $this->rpc('tools/list')->assertNotFound();
        $this->bearer($user->createToken('App')->plainTextToken)->rpc('tools/list')->assertNotFound();
        $this->actingAs($user)->postJson('/chat/mcp-token')->assertNotFound();
        $this->assertSame(1, $user->tokens()->count());
    }

    /** @test */
    public function anonymous_requests_get_nothing(): void
    {
        $this->rpc('tools/list')->assertUnauthorized();
        $this->rpc('tools/call', ['name' => 'get_losung', 'arguments' => (object) []])->assertUnauthorized();
        $this->bearer('elterninfo_ungueltig')->rpc('tools/list')->assertUnauthorized();
        $this->postJson('/chat/mcp-token')->assertUnauthorized();
    }

    /** @test */
    public function session_cookies_are_not_accepted(): void
    {
        $this->actingAs($this->parentIn($this->group()));

        $this->rpc('tools/list')->assertUnauthorized();
    }

    /** @test */
    public function integration_tokens_cannot_use_the_server(): void
    {
        $user = $this->parentIn($this->group());
        $token = IntegrationTokens::create($user, 'Home Assistant', [], '365')->plainTextToken;

        $this->bearer($token)->rpc('tools/list')->assertForbidden();
    }

    /** @test */
    public function deactivated_users_lose_access(): void
    {
        $user = $this->parentIn($this->group());
        $token = $this->mintToken($user);
        $user->forceFill(['is_active' => false])->save();

        $this->bearer($token)->rpc('tools/list')->assertUnauthorized();
    }

    /** @test */
    public function minted_token_is_short_lived_and_works_only_for_the_mcp_server(): void
    {
        config(['mcp_server.token_lifetime' => 30]);
        $user = $this->parentIn($this->group());

        $response = $this->actingAs($user)->postJson('/chat/mcp-token')->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private');
        $token = $response->json('token');
        $this->assertSame('Bearer', $response->json('token_type'));
        $this->assertStringEndsWith('/api/v1/mcp', $response->json('mcp_url'));
        $stored = $user->tokens()->latest('id')->first();
        $this->assertSame([McpTokens::ABILITY, McpTokens::READ], $stored->abilities);
        $this->assertEqualsWithDelta(now()->addMinutes(30)->timestamp, $stored->expires_at->timestamp, 5);

        // MCP: lesen im eigenen Kontext
        $this->bearer($token)->rpc('tools/list')->assertOk();
        $profile = $this->bearer($token)->rpc('tools/call', ['name' => 'get_profile', 'arguments' => (object) []])->assertOk()->json('result');
        $this->assertFalse($profile['isError'] ?? false);
        $this->assertSame($user->id, json_decode($profile['content'][0]['text'], true)['data']['id']);

        // Übrige API: weder lesen noch schreiben, keine Web-Anmeldung, kein neues Token
        foreach ([
            ['get', '/api/v1/me'],
            ['get', '/api/v1/bootstrap'],
            ['get', '/api/v1/me/tokens'],
            ['get', '/api/me'],
            ['get', '/api/files'],
            ['post', '/api/v1/auth/web-link'],
            ['post', '/api/v1/token/refresh'],
            ['patch', '/api/v1/me'],
            ['post', '/api/v1/parent/krankmeldungen'],
            ['post', '/api/krankmeldung'],
            ['post', '/api/v1/notifications/read-all'],
            ['post', '/api/v1/messenger/conversations/1/messages'],
        ] as [$method, $uri]) {
            $this->bearer($token)->json($method, $uri)
                ->assertForbidden()
                ->assertJsonPath('code', 'mcp_token_scope');
        }

        // Abgelaufen
        $this->travel(31)->minutes();
        $this->bearer($token)->rpc('tools/list')->assertUnauthorized();
    }

    /** @test */
    public function minting_requires_a_web_session_and_keeps_the_number_of_tokens_small(): void
    {
        $user = $this->parentIn($this->group());
        $appToken = $user->createToken('iPhone')->plainTextToken;

        // Ein Bearer-Token kann kein MCP-Token erzeugen (Web-Route, nur Sitzung)
        $this->bearer($appToken)->postJson('/chat/mcp-token')->assertUnauthorized();

        for ($i = 0; $i < 7; $i++) {
            $this->mintToken($user);
        }

        $this->assertSame(5, $user->tokens()->where('name', McpTokens::NAME)->count());
        $this->assertSame(1, $user->tokens()->where('name', 'iPhone')->count());

        $this->travel(2)->hours();
        $this->mintToken($user);
        $this->assertSame(1, $user->tokens()->where('name', McpTokens::NAME)->count());
    }

    /** @test */
    public function users_who_must_change_their_password_get_no_token(): void
    {
        $user = $this->parentIn($this->group(), ['changePassword' => true]);

        $this->actingAs($user)->postJson('/chat/mcp-token')->assertStatus(302);
        $this->assertSame(0, $user->tokens()->count());
    }

    /** @test */
    public function disabled_tools_are_neither_listed_nor_callable(): void
    {
        config(['mcp_server.disabled_tools' => ['list_krankmeldungen', 'get_messenger_messages']]);
        Sanctum::actingAs($this->parentIn($this->group()), ['*']);

        $names = array_column($this->rpc('tools/list', ['per_page' => 100])->assertOk()->json('result.tools'), 'name');

        $this->assertNotContains('list_krankmeldungen', $names);
        $this->assertNotContains('get_messenger_messages', $names);
        $this->assertContains('get_profile', $names);
        $this->assertNotNull($this->rpc('tools/call', ['name' => 'list_krankmeldungen', 'arguments' => (object) []])->json('error'));
    }

    /** @test */
    public function no_tool_writes_to_the_database(): void
    {
        $group = $this->group();
        $user = $this->parentIn($group);
        $this->childOf($user, $group);
        $this->postIn($group, ['read_receipt' => true]);
        Sanctum::actingAs($user, ['*']);
        $before = $this->tableCounts();

        foreach (ToolCatalog::tools() as $tool) {
            $schema = $tool->toArray()['inputSchema']['required'] ?? [];
            if ($schema !== []) {
                continue; // Werkzeuge mit Pfad-IDs prüfen McpServerTest und die Controller-Tests
            }
            $result = $this->rpc('tools/call', ['name' => $tool->name(), 'arguments' => (object) []])->assertOk()->json('result');
            $this->assertStringNotContainsString('Fehler 500', $result['content'][0]['text'] ?? '', $tool->name());
        }

        $this->assertSame($before, $this->tableCounts());
    }

    /** @test */
    public function gateway_rolls_back_writes_of_get_routes_and_restores_the_guard(): void
    {
        Route::middleware('api')->get('api/mcp-test-schreibt', function () {
            DB::table('groups')->insert(['name' => 'Seiteneffekt', 'protected' => 0]);

            return response()->json(['ok' => true]);
        });
        $user = $this->parentIn($this->group());

        $result = app(ReadOnlyApiGateway::class)->get($user, 'api/mcp-test-schreibt');

        $this->assertSame(200, $result['status']);
        $this->assertDatabaseMissing('groups', ['name' => 'Seiteneffekt']);
        $this->assertFalse(Auth::guard('sanctum')->hasUser());
    }

    /** @return array<string, int> */
    private function tableCounts(): array
    {
        $counts = [];
        foreach (Schema::getTableListing() as $table) {
            $table = str_contains($table, '.') ? substr($table, strrpos($table, '.') + 1) : $table;
            if (in_array($table, ['personal_access_tokens', 'cache', 'cache_locks', 'sessions', 'jobs'], true)) {
                continue;
            }
            $counts[$table] = DB::table($table)->count();
        }

        return $counts;
    }
}
