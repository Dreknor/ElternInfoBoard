<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Class newUnveroeffentlichterBeitrag
 */
class newUnveroeffentlichterBeitrag extends Mailable
{
    use Queueable, SerializesModels;

    public string $von;

    public string $Betreff;

    /**
     * newUnveroeffentlichterBeitrag constructor.
     */
    public function __construct(string $von, string $Betreff)
    {
        $this->von = $von;
        $this->Betreff = $Betreff;
    }

    public function build(): newUnveroeffentlichterBeitrag
    {
        return $this
            ->subject('neuer unveröffentlichter Beitrag im '.config('app.name'))
            ->view('emails.neuerUnveroeffentlichterBeitrag', [
                'von' => $this->von,
                'betreff' => $this->Betreff,
            ]);
    }
}
