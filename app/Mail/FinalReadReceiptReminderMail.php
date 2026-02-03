<?php

namespace App\Mail;

use App\Settings\GeneralSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FinalReadReceiptReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $email;

    public string $name;

    public string $thema;

    public string $content;

    public string $ende;

    public int $theme_id;

    public ?string $authorEmail;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $Email, string $Name, string $thema, string $content, string $ende, int $theme_id, ?string $authorEmail = null)
    {
        $this->email = $Email;
        $this->name = $Name;
        $this->thema = $thema;
        $this->content = $content;
        $this->ende = $ende;
        $this->theme_id = $theme_id;
        $this->authorEmail = $authorEmail;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): static
    {
        $mailable = $this
            ->subject('WICHTIG: Lesebestätigung erforderlich - '.$this->thema)
            ->view('emails.FinalReadReceiptReminder')
            ->with([
                'name' => $this->name,
                'thema' => $this->thema,
                'content' => $this->content,
                'ende' => $this->ende,
                'theme_id' => $this->theme_id,
                'BoardName' => (new GeneralSetting)->app_name,
            ]);

        if ($this->authorEmail) {
            $mailable->withSwiftMessage(function ($message) {
                $message->getHeaders()
                    ->addTextHeader('Disposition-Notification-To', $this->authorEmail)
                    ->addTextHeader('Return-Receipt-To', $this->authorEmail);
            });
        }

        return $mailable;
    }
}
