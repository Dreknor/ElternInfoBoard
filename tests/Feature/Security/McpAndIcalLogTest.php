<?php

namespace Tests\Feature\Security;

use App\Model\SecurityEvent;
use App\Model\User;
use Illuminate\Support\Facades\Cache;
use Tests\Feature\API\V1\AppApiTestCase;

class McpAndIcalLogTest extends AppApiTestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    /** @test */
    public function mcp_tool_call_is_logged_after_the_gateway_rolled_back_its_transaction(): void
    {
        config(['mcp_server.enabled' => true]);
        $group = $this->group();
        $user = $this->parentIn($group);
        $this->postIn($group);
        $token = $user->createToken('Chat');

        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson('/api/v1/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => ['name' => 'list_posts', 'arguments' => (object) ['per_page' => 7]]], ['Accept' => 'application/json, text/event-stream'])
            ->assertOk();

        $event = SecurityEvent::where('event', 'mcp.tool_call')->firstOrFail();
        $this->assertSame($user->id, $event->user_id);
        $this->assertSame(['tool' => 'list_posts', 'status' => 200, 'pat_id' => $token->accessToken->id], $event->context);
        $this->assertStringNotContainsString('7', json_encode(array_diff_key($event->context, ['status' => 1, 'pat_id' => 1])));
    }

    /** @test */
    public function ical_fetch_is_logged_once_per_user_ip_and_day(): void
    {
        Cache::flush();
        $owner = User::factory()->create(['releaseCalendar' => true]);

        $this->get('/'.$owner->uuid.'/ical')->assertOk();
        $this->get('/'.$owner->uuid.'/ical')->assertOk();
        $this->assertSame(1, SecurityEvent::where('event', 'ical.fetch')->count());

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])->get('/'.$owner->uuid.'/ical')->assertOk();
        $events = SecurityEvent::where('event', 'ical.fetch')->get();
        $this->assertCount(2, $events);
        $this->assertSame($owner->id, $events->first()->user_id);
    }

    /** @test */
    public function ical_fetch_with_unknown_uuid_or_unreleased_calendar_is_not_logged(): void
    {
        $this->get('/00000000-0000-0000-0000-000000000000/ical')->assertNotFound();
        $user = User::factory()->create(['releaseCalendar' => false]);
        $this->get('/'.$user->uuid.'/ical')->assertForbidden();

        $this->assertSame(0, SecurityEvent::where('event', 'ical.fetch')->count());
    }
}
