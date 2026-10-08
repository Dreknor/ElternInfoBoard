<?php

namespace App\Jobs;

use App\Model\User;
use App\Notifications\Push;
use App\Services\Notifications\NotificationCategory;
use App\Services\Notifications\NotificationPreferences;
use App\Services\Push\NativePushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Push zu einer Benachrichtigung: App-Geräte und Browser, jeweils nur bei gewähltem Kanal.
 */
class SendPushNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        public array $userIds,
        public string $title,
        public string $message,
        public ?string $url,
        public string $type,
    ) {}

    public function handle(NativePushService $native): void
    {
        $native->send($this->userIds, $this->title, $this->message, $this->url, $this->type);
        $this->sendWebPush();
    }

    private function sendWebPush(): void
    {
        $category = NotificationCategory::resolve($this->type, $this->url);
        $userIds = NotificationPreferences::filter($this->userIds, $category, 'web');
        if ($userIds === []) {
            return;
        }

        [$title, $body] = NativePushService::texts($this->title, $this->message, $this->url);
        User::whereIn('id', $userIds)->whereHas('pushSubscriptions')->each(function (User $user) use ($title, $body) {
            try {
                $user->notify(new Push($title, $body));
            } catch (\Throwable $e) {
                Log::warning("Web-Push an Benutzer {$user->id} fehlgeschlagen: ".$e->getMessage());
            }
        });
    }
}
