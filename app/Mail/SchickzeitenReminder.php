<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class SchickzeitenReminder extends Mailable
{
    use Queueable, SerializesModels;

    private string $name;

    private Collection $schickzeiten;

    private Collection $kinder;

    private array $weekdays = [
        '1' => 'Montag',
        '2' => 'Dienstag',
        '3' => 'Mittwoch',
        '4' => 'Donnerstag',
        '5' => 'Freitag',
    ];

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $name, Collection $schickzeiten)
    {
        $this->schickzeiten = $schickzeiten;
        $this->name = $name;
        $this->kinder = $schickzeiten->pluck('child_id')->unique();
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): static
    {
        return $this
            ->subject('Übersicht Schickzeiten')
            ->view('emails.schickzeitenReminder', [
                'name' => $this->name,
                'schickzeiten' => $this->schickzeiten,
                'kinder' => $this->kinder,
                'weekdays' => $this->weekdays,
            ]);
    }
}
