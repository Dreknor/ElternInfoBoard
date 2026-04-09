<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class ReminderPushNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $body,
        public string $actionUrl,
    ) {}

    public function via($notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title($this->title)
            ->icon(asset('img/' . config('app.favicon')))
            ->body($this->body)
            ->action('Öffnen', $this->actionUrl);
    }
}

