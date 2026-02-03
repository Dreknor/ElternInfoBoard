<?php

namespace App\Traits;

use App\Model\Notification;
use Illuminate\Database\Eloquent\Collection;

trait NotificationTrait
{
    public function notify(Collection $users, string $title, string $message, bool $important = false, ?string $url = null, string $type = 'info', string $icon = ''): void
    {
        $notifications = [];
        $users->each(function ($user) use ($title, $message, $url, $type, $icon, $important, &$notifications) {
            $notifications[] = [
                'user_id' => $user->id,
                'title' => $title,
                'message' => $message,
                'url' => $url,
                'type' => $type,
                'icon' => $icon,
                'read' => false,
                'important' => $important,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        });

        if (! empty($notifications)) {
            Notification::insert($notifications);
        }
    }
}
