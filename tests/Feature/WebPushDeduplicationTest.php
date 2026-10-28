<?php

namespace Tests\Feature;

use App\Model\User;
use App\Model\UserDevice;
use App\Services\Push\BrowserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebPushDeduplicationTest extends TestCase
{
    use RefreshDatabase;

    private function subscribe(User $user, string $endpoint, ?string $platform): void
    {
        $subscription = $user->updatePushSubscription($endpoint, 'key', 'token');
        $subscription->platform = $platform;
        $subscription->save();
    }

    private function routedEndpoints(User $user): array
    {
        return $user->fresh()->routeNotificationForWebPush()->pluck('endpoint')->sort()->values()->all();
    }

    /** @test */
    public function without_app_all_browser_subscriptions_receive_push(): void
    {
        $user = User::factory()->create();
        $this->subscribe($user, 'https://web.push.apple.com/a', BrowserDevice::IOS);
        $this->subscribe($user, 'https://fcm.googleapis.com/b', null);

        $this->assertSame(['https://fcm.googleapis.com/b', 'https://web.push.apple.com/a'], $this->routedEndpoints($user));
    }

    /** @test */
    public function browser_subscription_on_same_platform_as_app_is_skipped(): void
    {
        $user = User::factory()->create();
        UserDevice::create(['user_id' => $user->id, 'token' => 'apns-token', 'provider' => UserDevice::PROVIDER_APNS]);
        $this->subscribe($user, 'https://web.push.apple.com/iphone', BrowserDevice::IOS);
        $this->subscribe($user, 'https://fcm.googleapis.com/android', BrowserDevice::ANDROID);
        $this->subscribe($user, 'https://fcm.googleapis.com/desktop', BrowserDevice::DESKTOP);
        $this->subscribe($user, 'https://fcm.googleapis.com/legacy', null);

        $this->assertSame(
            ['https://fcm.googleapis.com/android', 'https://fcm.googleapis.com/desktop'],
            $this->routedEndpoints($user)
        );
    }

    /** @test */
    public function storing_a_subscription_records_the_platform(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 14; Pixel 8) Chrome/130.0 Mobile')
            ->postJson('/push', ['endpoint' => 'https://fcm.googleapis.com/x', 'keys' => ['auth' => 'a', 'p256dh' => 'p']])
            ->assertOk();
        $this->actingAs($user)
            ->postJson('/push', ['endpoint' => 'https://web.push.apple.com/y', 'keys' => ['auth' => 'a', 'p256dh' => 'p'], 'platform' => 'ios'])
            ->assertOk();

        $this->assertDatabaseHas('push_subscriptions', ['endpoint' => 'https://fcm.googleapis.com/x', 'platform' => 'android', 'device_name' => 'Chrome auf Android']);
        $this->assertDatabaseHas('push_subscriptions', ['endpoint' => 'https://web.push.apple.com/y', 'platform' => 'ios']);
    }

    /** @test */
    public function settings_list_devices_and_subscriptions_can_be_removed(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        UserDevice::create(['user_id' => $user->id, 'token' => 'apns-token', 'provider' => UserDevice::PROVIDER_APNS, 'device_name' => 'iPhone von Test']);
        $own = $user->updatePushSubscription('https://fcm.googleapis.com/own');
        $own->forceFill(['platform' => BrowserDevice::DESKTOP, 'device_name' => 'Firefox auf Windows'])->save();
        $foreign = $other->updatePushSubscription('https://fcm.googleapis.com/foreign');

        $this->withoutVite();
        $this->actingAs($user)->get('/einstellungen')
            ->assertOk()
            ->assertSee('Firefox auf Windows')
            ->assertSee('iPhone von Test');

        $this->actingAs($user)->delete('/push/'.$foreign->id)->assertNotFound();
        $this->actingAs($user)->delete('/push/'.$own->id)->assertRedirect();

        $this->assertDatabaseMissing('push_subscriptions', ['id' => $own->id]);
        $this->assertDatabaseHas('push_subscriptions', ['id' => $foreign->id]);
    }

    /** @test */
    public function platform_is_detected_from_hint_or_user_agent(): void
    {
        $this->assertSame('ios', BrowserDevice::platform(null, 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X)'));
        $this->assertSame('android', BrowserDevice::platform(null, 'Mozilla/5.0 (Linux; Android 14)'));
        $this->assertSame('desktop', BrowserDevice::platform(null, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'));
        $this->assertSame('ios', BrowserDevice::platform('ios', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)'));
        $this->assertSame('desktop', BrowserDevice::platform('bogus', null));

        $this->assertSame('Safari auf iPhone', BrowserDevice::name('Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 Version/18.0 Mobile/15E148 Safari/604.1', 'ios'));
        $this->assertSame('Chrome auf Android', BrowserDevice::name('Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 Chrome/130.0 Mobile Safari/537.36', 'android'));
        $this->assertSame('Edge auf Windows', BrowserDevice::name('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/130.0 Safari/537.36 Edg/130.0', 'desktop'));
        $this->assertSame('Safari auf iPad', BrowserDevice::name('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 Version/18.0 Safari/605.1.15', 'ios'));
    }
}
