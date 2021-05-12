<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SchickzeitenReminder extends Mailable
{
    use Queueable, SerializesModels;

    private $name;
    private $schickzeiten;
    private $kinder;
    private $weekdays = [
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
    public function __construct($name, $schickzeiten)
    {
        $this->schickzeiten = $schickzeiten;
        $this->name = $name;
        $this->kinder =  $schickzeiten->pluck('child_name')->unique();

    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->subject('Übersicht Schickzeiten')
            ->view('emails.schickzeitenReminder',[
                'name' => $this->name,
                'schickzeiten' => $this->schickzeiten,
                'kinder' => $this->kinder,
                'weekdays' => $this->weekdays
            ]);
    }
}
