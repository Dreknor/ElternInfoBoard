<?php

namespace App\Notifications;

use App\Model\Notification as NotificationModel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

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
     * Wir verwenden einen benutzerdefinierten Channel, der direkt das Notification-Model erstellt.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable): array
    {
        // Erstelle direkt eine Notification im Model, das dann per Event WebPush auslöst
        NotificationModel::create([
            'user_id' => $notifiable->id,
            'type' => 'Anwesenheitsabfrage',
            'title' => $this->title,
            'message' => $this->body,
            'url' => $this->url,
            'read' => false,
            'important' => false,
        ]);

        // Gebe leeres Array zurück, da wir die Notification bereits erstellt haben
        return [];
    }
}

