<?php

namespace App\Notifications;

use App\Model\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class ReadReceiptReminderNotification extends Notification
{
    use Queueable;

    public Post $post;

    public string $deadline;

    public function __construct(Post $post, string $deadline)
    {
        $this->post = $post;
        $this->deadline = $deadline;
    }

    public function via($notifiable)
    {
        return ['database', WebPushChannel::class];
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
            'post_id' => $this->post->id,
            'post_header' => $this->post->header,
            'deadline' => $this->deadline,
            'action_url' => url('#'.$this->post->id),
            'created' => Carbon::now()->toIso8601String(),
        ];
    }

    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title('Lesebestätigung fehlt')
            ->icon(asset('img/'.config('app.favicon')))
            ->body('Bitte bestätigen Sie die Nachricht "'.$this->post->header.'" bis zum '.$this->deadline)
            ->action('Zur Nachricht', url('#'.$this->post->id));
    }
}
