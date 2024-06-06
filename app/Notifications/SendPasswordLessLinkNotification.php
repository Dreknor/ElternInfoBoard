<?php

namespace App\Notifications;

use Grosv\LaravelPasswordlessLogin\LoginUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendPasswordLessLinkNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $generator = new LoginUrl($notifiable);
        $generator->setRedirectUrl('/home');
        $url = $generator->generate();

        return (new MailMessage)
            ->subject('Login-Link für ' . config('app.name'))
            ->line('Um sich anzumelden, klicken Sie bitte auf den folgenden Link.')
            ->action('Login', $url)
            ->line('Wenn Sie sich nicht anmelden wollten, können Sie diese E-Mail ignorieren.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
