<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class MessengerPushNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $title,
        private readonly string $body,
        private readonly string $url,
    ) {}

    public function via(mixed $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(mixed $notifiable, mixed $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title($this->title)
            ->icon(asset('img/' . config('app.favicon')))
            ->body($this->body)
            ->action('Öffnen', 'open')
            ->data(['url' => $this->url]);
    }

    public function toArray(mixed $notifiable): array
    {
        return [
            'title' => $this->title,
            'body'  => $this->body,
            'url'   => $this->url,
        ];
    }
}

