<?php

namespace Tests\Feature\API\V1;

use App\Mcp\ToolCatalog;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

class McpServerTest extends AppApiTestCase
{
    private function rpc(string $method, array $params = [], int $id = 1)
    {
        return $this->postJson('/api/v1/mcp', ['jsonrpc' => '2.0', 'id' => $id, 'method' => $method, 'params' => $params], [
            'Accept' => 'application/json, text/event-stream',
        ]);
    }

    private function tool(string $tool, array $arguments = [])
    {
        return $this->rpc('tools/call', ['name' => $tool, 'arguments' => (object) $arguments]);
    }

    /** @test */
    public function requires_authentication(): void
    {
        $this->rpc('tools/list')->assertUnauthorized();
    }

    /** @test */
    public function blocks_users_that_must_change_their_password(): void
    {
        Sanctum::actingAs($this->parentIn($this->group(), ['changePassword' => true]));

        $this->rpc('tools/list')->assertForbidden();
    }

    /** @test */
    public function every_tool_maps_to_an_existing_get_only_api_route(): void
    {
        foreach (ToolCatalog::tools() as $tool) {
            $path = (new \ReflectionProperty($tool, 'path'))->getValue($tool);
            $route = collect(Route::getRoutes()->getRoutes())->first(fn ($r) => $r->uri() === $path);

            $this->assertNotNull($route, "Route fehlt für {$tool->name()}: {$path}");
            $this->assertSame([], array_diff($route->methods(), ['GET', 'HEAD']), "{$tool->name()} ist nicht readonly");
        }
    }

    /** @test */
    public function lists_only_read_only_tools(): void
    {
        Sanctum::actingAs($this->parentIn($this->group()));

        $tools = $this->rpc('tools/list', ['per_page' => 100])->assertOk()->json('result.tools');

        $this->assertNotEmpty($tools);
        foreach ($tools as $tool) {
            $this->assertTrue($tool['annotations']['readOnlyHint'], $tool['name']);
        }
    }

    /** @test */
    public function posts_are_limited_to_the_users_groups(): void
    {
        $group = $this->group();
        $user = $this->parentIn($group);
        $visible = $this->postIn($group);
        $hidden = $this->postIn($this->group('Andere'));
        Sanctum::actingAs($user);

        $list = $this->tool('list_posts')->assertOk()->json('result');
        $this->assertFalse($list['isError'] ?? false);
        $ids = array_column(json_decode($list['content'][0]['text'], true)['data'], 'id');
        $this->assertSame([$visible->id], $ids);

        $denied = $this->tool('get_post', ['post' => $hidden->id])->json('result');
        $this->assertTrue($denied['isError']);
        $this->assertStringContainsString('403', $denied['content'][0]['text']);
    }

    /** @test */
    public function reading_does_not_modify_data_and_context_is_restored(): void
    {
        $group = $this->group();
        $user = $this->parentIn($group);
        $post = $this->postIn($group, ['read_receipt' => true]);
        Sanctum::actingAs($user);

        $this->tool('get_post', ['post' => $post->id])->assertOk();

        $this->assertDatabaseMissing('read_receipts', ['post_id' => $post->id, 'user_id' => $user->id]);
        $this->assertSame($user->id, auth('sanctum')->id());
    }

    /** @test */
    public function rejects_unknown_and_invalid_arguments(): void
    {
        Sanctum::actingAs($this->parentIn($this->group()));

        $this->assertTrue($this->tool('list_posts', ['foo' => 'bar'])->json('result.isError'));
        $this->assertTrue($this->tool('get_post', ['post' => '../../me'])->json('result.isError'));
        $this->assertTrue($this->tool('get_post')->json('result.isError'));
    }

    /** @test */
    public function child_data_is_limited_to_own_children(): void
    {
        $group = $this->group();
        $user = $this->parentIn($group);
        $own = $this->childOf($user, $group);
        $foreign = $this->childOf($this->parentIn($group), $group);
        Sanctum::actingAs($user);

        $this->assertFalse($this->tool('get_stundenplan', ['child' => $own->id])->json('result.isError') ?? false);
        $this->assertTrue($this->tool('get_stundenplan', ['child' => $foreign->id])->json('result.isError'));
    }

    /** @test */
    public function messenger_messages_are_readable_without_marking_them_as_read(): void
    {
        $group = $this->group();
        $user = $this->parentIn($group);
        $other = $this->parentIn($group);
        \Spatie\Permission\Models\Permission::findOrCreate('use messenger', 'web');
        $user->givePermissionTo('use messenger');

        $conversation = \App\Model\Conversation::create(['type' => 'direct', 'created_by' => $other->id, 'is_active' => true]);
        $conversation->users()->attach([$user->id => ['joined_at' => now()->subDay()], $other->id => ['joined_at' => now()->subDay()]]);
        $message = $conversation->messages()->create(['sender_id' => $other->id, 'body' => 'Hallo', 'type' => 'file']);
        $message->addMediaFromString('inhalt')->usingFileName('Elternbrief.pdf')->toMediaCollection('message-attachments');
        Sanctum::actingAs($user);

        $result = $this->tool('get_messenger_messages', ['conversation' => $conversation->id])->assertOk()->json('result');

        $this->assertFalse($result['isError'] ?? false);
        $data = json_decode($result['content'][0]['text'], true)['data'];
        $this->assertSame('Hallo', $data[0]['body']);
        $this->assertSame('Elternbrief.pdf', $data[0]['attachment']['filename']);
        $this->assertNull($conversation->users()->find($user->id)->pivot->last_read_at);

        $foreign = \App\Model\Conversation::create(['type' => 'direct', 'created_by' => $other->id, 'is_active' => true]);
        $foreign->users()->attach([$other->id => ['joined_at' => now()]]);
        $this->assertTrue($this->tool('get_messenger_messages', ['conversation' => $foreign->id])->json('result.isError'));
    }

    /** @test */
    public function post_attachment_names_are_included(): void
    {
        $group = $this->group();
        $user = $this->parentIn($group);
        $post = $this->postIn($group);
        $post->addMediaFromString('inhalt')->usingFileName('Plan.pdf')->toMediaCollection('files');
        Sanctum::actingAs($user);

        $result = $this->tool('get_post', ['post' => $post->id])->json('result');

        $this->assertFalse($result['isError'] ?? false);
        $this->assertStringContainsString('Plan.pdf', $result['content'][0]['text']);
    }
}
