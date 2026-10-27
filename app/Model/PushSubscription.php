<?php

namespace App\Model;

use NotificationChannels\WebPush\PushSubscription as BasePushSubscription;

/**
 * Browser-Abo für Web-Push, ergänzt um das Gerät (Plattform, Name, letzte Nutzung).
 */
class PushSubscription extends BasePushSubscription
{
    protected $hidden = ['endpoint', 'public_key', 'auth_token'];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
        ];
    }
}
