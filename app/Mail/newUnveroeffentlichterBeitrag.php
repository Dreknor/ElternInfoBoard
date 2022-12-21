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

    /**
     * @var string
     */
    public string $von;

    /**
     * @var string
     */
    public string $Betreff;

    /**
     * newUnveroeffentlichterBeitrag constructor.
     *
     * @param string $von
     * @param string $Betreff
     */
    public function __construct(string $von, string $Betreff)
    {
        $this->von = $von;
        $this->Betreff = $Betreff;
    }

    /**
     * @return newUnveroeffentlichterBeitrag
     */
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
