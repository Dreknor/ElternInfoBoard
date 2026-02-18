<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class AttendanceQueryNotification extends Notification
{
    use Queueable;

    public $title;
    public $body;
    public $url;

    /**
     * Erstelle eine neue Notification-Instanz.
     *
     * @param string $title
     * @param string $body
     * @param string $url
     */
    public function __construct(string $title, string $body, string $url = null)
    {
        $this->title = $title;
        $this->body = $body;
        $this->url = $url ?? url('schickzeiten');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable): array
    {
        return [WebPushChannel::class, 'database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
            'created' => Carbon::now()->toIso8601String(),
        ];
    }

    /**
     * Get the web push representation of the notification.
     *
     * @param mixed $notifiable
     * @param mixed $notification
     * @return WebPushMessage
     */
    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title($this->title)
            ->icon(asset('img/'.config('app.favicon')))
            ->body($this->body)
            ->data(['url' => $this->url]);
    }
}

