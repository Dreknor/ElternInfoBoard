<?php

namespace App\Services\Push;

use App\Jobs\SendNativePush;
use App\Model\UserDevice;
use App\Model\UserAppSettings;

/**
 * Versand nativer Push-Mitteilungen an die Eltern-App.
 * Inhalte mit Kinder-/Gesundheitsbezug werden neutral formuliert (Datenschutz).
 */
class NativePushService
{
    public function __construct(
        private readonly ApnsSender $apns,
        private readonly FcmSender $fcm,
    ) {}

    /** Asynchron über die Queue einplanen – nur wenn überhaupt Geräte existieren. */
    public static function dispatch(array $userIds, string $title, string $message, ?string $url, string $type = 'info'): void
    {
        if (empty($userIds) || ! UserDevice::whereIn('user_id', $userIds)->exists()) {
            return;
        }
        SendNativePush::dispatch(array_values(array_unique($userIds)), $title, $message, $url, $type);
    }

    public function send(array $userIds, string $title, string $message, ?string $url, string $type): void
    {
        $target = PushTarget::fromUrl($url);
        if (PushTarget::isSensitive($target)) {
            $title = 'Hort & Betreuung';
            $message = 'Es gibt eine neue Information zu Ihrem Kind.';
        }
        $body = mb_strimwidth(strip_tags($message), 0, 180, '…');
        $data = ['type' => $target['type'] ?? $type, 'id' => $target['id'] ?? null];

        $devices = UserDevice::whereIn('user_id', $userIds)->get();
        $muted = $this->mutedUsers($devices->pluck('user_id')->unique()->all(), $data['type']);

        foreach ($devices as $device) {
            if (in_array($device->user_id, $muted, true)) {
                continue;
            }
            $result = match ($device->provider) {
                UserDevice::PROVIDER_APNS => $this->apns->isConfigured()
                    ? $this->apns->send($device->token, [
                        'aps' => ['alert' => ['title' => $title, 'body' => $body], 'sound' => 'default'],
                        'type' => $data['type'],
                        'id' => $data['id'],
                    ])
                    : null,
                UserDevice::PROVIDER_FCM => $this->fcm->isConfigured()
                    ? $this->fcm->send($device->token, $title, $body, $data)
                    : null,
                default => null,
            };
            if ($result === false) {
                $device->delete();
            }
        }
    }

    /**
     * Nutzer, die diese Kategorie in der App abgeschaltet haben (`user/settings` Pfad `push.<kategorie>` = false).
     */
    private function mutedUsers(array $userIds, string $type): array
    {
        $category = match ($type) {
            'conversation' => 'messenger',
            'attendance', 'child', 'krankmeldung' => 'hort',
            'termin', 'liste' => 'termine',
            default => 'nachrichten',
        };

        return UserAppSettings::whereIn('user_id', $userIds)->get()
            ->filter(fn ($s) => data_get($s->settings, "push.{$category}") === false)
            ->pluck('user_id')
            ->all();
    }
}
