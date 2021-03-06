<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class PushTerminAbsage extends Notification
{
    use Queueable;

    public $body;

    public function __construct($body)
    {
        $this->body = $body;
    }

    public function via($notifiable)
    {
        return [WebPushChannel::class];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'created' => Carbon::now()->toIso8601String(),
        ];
    }

    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title('Termin abgesagt')
            ->icon(asset('img/logo-small.png'))
            ->body($this->body);
    }
}
