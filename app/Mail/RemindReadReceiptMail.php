<?php

namespace App\Mail;

use App\Settings\GeneralSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;


class RemindReadReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $email;

    public string $name;

    public string $thema;
    public string $ende;
    public int $theme_id;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $Email, string $Name, string $thema, string $ende, int $theme_id)
    {
        $this->email = $Email;
        $this->name = $Name;
        $this->thema = $thema;
        $this->ende = $ende;
        $this->theme_id = $theme_id;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): static
    {
        return $this
            ->subject('Rückmeldung fehlt: ' . $this->thema)
            ->view('emails.ReadReceiptFehlt')->with([
                'name' => $this->name,
                'thema' => $this->thema,
                'ende' => $this->ende,
                'theme_id' => $this->theme_id,
                'BoardName' => (new GeneralSetting())->app_name,
            ]);
    }
}
