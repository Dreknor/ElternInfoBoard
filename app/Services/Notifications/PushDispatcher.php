<?php

namespace App\Services\Notifications;

use App\Jobs\SendPushNotifications;
use App\Model\User;
use App\Model\UserDevice;
use NotificationChannels\WebPush\PushSubscription;

/**
 * Einziger Weg für Push-Mitteilungen zu einer Benachrichtigung: App (APNs/FCM) und Browser (Web-Push).
 * Versand asynchron über die Queue, gefiltert nach den Kanälen, die der Nutzer gewählt hat.
 */
class PushDispatcher
{
    /** @param int[] $userIds */
    public static function dispatch(array $userIds, string $title, string $message, ?string $url, string $type = 'info'): void
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        if ($userIds === [] || ! self::hasReceivers($userIds)) {
            return;
        }

        SendPushNotifications::dispatch($userIds, $title, $message, $url, $type);
    }

    private static function hasReceivers(array $userIds): bool
    {
        return UserDevice::whereIn('user_id', $userIds)->exists()
            || PushSubscription::where('subscribable_type', (new User)->getMorphClass())
                ->whereIn('subscribable_id', $userIds)
                ->exists();
    }
}
