<?php

namespace App\Mail;

use App\Settings\GeneralSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReinigungReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $userName;

    public string $aufgabe;

    public string $woche;

    public string $bereich;

    public string $boardName;

    public function __construct(string $userName, ?string $aufgabe, string $woche, string $bereich)
    {
        $this->userName = $userName;
        $this->aufgabe = $aufgabe ?: 'Reinigungsdienst';
        $this->woche = $woche;
        $this->bereich = $bereich;
        $this->boardName = (new GeneralSetting)->app_name;
    }

    public function build(): static
    {
        return $this
            ->subject('Erinnerung: Reinigungsdienst in der kommenden Woche')
            ->view('emails.reinigung-reminder')
            ->with([
                'userName' => $this->userName,
                'aufgabe' => $this->aufgabe,
                'woche' => $this->woche,
                'bereich' => $this->bereich,
                'boardName' => $this->boardName,
            ]);
    }
}
