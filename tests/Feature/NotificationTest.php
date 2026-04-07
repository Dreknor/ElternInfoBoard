<?php

namespace Tests\Feature;

use App\Model\Notification;
use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-Tests für Benachrichtigungssystem
 */
class NotificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function user_can_receive_notification(): void
    {
        $user = User::factory()->create();

        $notification = Notification::factory()->create([
            'user_id' => $user->id,
            'title' => 'Test Benachrichtigung',
            'message' => 'Dies ist eine Test-Nachricht',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'title' => 'Test Benachrichtigung',
        ]);
    }

    /**
     * @test
     */
    public function notification_can_be_marked_as_read(): void
    {
        $notification = Notification::factory()->unread()->create();

        $notification->update(['read' => true]);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'read' => true,
        ]);
    }

    /**
     * @test
     */
    public function user_can_get_unread_notifications(): void
    {
        $user = User::factory()->create();

        Notification::factory()->count(3)->unread()->create(['user_id' => $user->id]);
        Notification::factory()->count(2)->read()->create(['user_id' => $user->id]);

        $unreadCount = Notification::where('user_id', $user->id)
            ->where('read', false)
            ->count();

        $this->assertEquals(3, $unreadCount);
    }

    /**
     * @test
     */
    public function notification_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $notification->user);
        $this->assertEquals($user->id, $notification->user->id);
    }

    /**
     * @test
     */
    public function user_with_notification_setting_receives_notifications(): void
    {
        $userWithNotifications = User::factory()->create(['benachrichtigung' => true]);
        $userWithoutNotifications = User::factory()->create(['benachrichtigung' => false]);

        $this->assertTrue($userWithNotifications->benachrichtigung);
        $this->assertFalse($userWithoutNotifications->benachrichtigung);
    }
}
