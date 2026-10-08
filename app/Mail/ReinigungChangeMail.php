<?php

namespace App\Mail;

use App\Settings\GeneralSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Information an eine Familie über eine sie betreffende Änderung im Reinigungsplan
 * (neuer Einsatz, entfernter Einsatz, automatisch erstellter Plan).
 */
class ReinigungChangeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $boardName;

    /**
     * @param  array<int, array{woche: string, aufgabe: string, bemerkungen: string[]}>  $einsaetze
     */
    public function __construct(
        public string $userName,
        public string $betreff,
        public string $einleitung,
        public array $einsaetze,
    ) {
        $this->boardName = (new GeneralSetting)->app_name;
    }

    public function build(): static
    {
        return $this
            ->subject($this->betreff)
            ->view('emails.reinigung-change');
    }
}
