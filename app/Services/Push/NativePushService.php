<?php

namespace App\Services\Push;

use App\Model\UserDevice;
use App\Services\Notifications\NotificationCategory;
use App\Services\Notifications\NotificationPreferences;
use App\Services\Notifications\PushDispatcher;

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

    /** @deprecated Über PushDispatcher::dispatch() versenden (App und Browser). */
    public static function dispatch(array $userIds, string $title, string $message, ?string $url, string $type = 'info'): void
    {
        PushDispatcher::dispatch($userIds, $title, $message, $url, $type);
    }

    /**
     * Titel und Text für Push-Mitteilungen (Sperrbildschirm): bei Hort-/Kinderbezug neutral.
     *
     * @return array{0: string, 1: string}
     */
    public static function texts(string $title, string $message, ?string $url): array
    {
        if (PushTarget::isSensitive(PushTarget::fromUrl($url))) {
            return ['Hort & Betreuung', 'Es gibt eine neue Information zu Ihrem Kind.'];
        }

        return [$title, mb_strimwidth(strip_tags($message), 0, 180, '…')];
    }

    /** Nur an Nutzer, die App-Push für die Kategorie zulassen (Einstellungen → Benachrichtigungen). */
    public function send(array $userIds, string $title, string $message, ?string $url, string $type): void
    {
        $category = NotificationCategory::resolve($type, $url);
        $userIds = NotificationPreferences::filter($userIds, $category, 'app');
        if ($userIds === []) {
            return;
        }

        $target = PushTarget::fromUrl($url);
        [$title, $body] = self::texts($title, $message, $url);
        $data = ['type' => $target['type'] ?? $type, 'id' => $target['id'] ?? null];

        foreach (UserDevice::whereIn('user_id', $userIds)->get() as $device) {
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
}
