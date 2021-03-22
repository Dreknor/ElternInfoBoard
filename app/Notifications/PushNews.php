<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class PushNews extends Notification
{
    use Queueable;

    public $post;

    public function __construct($post)
    {
        $this->post = $post;
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
            'action_url' => url('#'.$this->post->id),
            'created' => Carbon::now()->toIso8601String(),
        ];
    }

    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title('Neue Mitteilung im '.config('app.name'))
            ->icon(asset('img/'.config('app.favicon')))
            ->body('Neue Mitteilung:'.$this->post->header)
            //->action('Zeige Nachricht', url("#".$this->post->id))
;
    }
}
