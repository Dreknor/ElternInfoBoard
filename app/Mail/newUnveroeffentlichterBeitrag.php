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
     * @var
     */
    public $von;

    /**
     * @var
     */
    public $Betreff;

    /**
     * newUnveroeffentlichterBeitrag constructor.
     *
     * @param $von
     * @param $Betreff
     */
    public function __construct($von, $Betreff)
    {
        $this->von = $von;
        $this->Betreff = $Betreff;
    }

    /**
     * @return newUnveroeffentlichterBeitrag
     */
    public function build()
    {
        return $this
            ->subject('neuer unveröffentlichter Beitrag im '.config('app.name'))
            ->view('emails.neuerUnveroeffentlichterBeitrag', [
                'von' => $this->von,
                'betreff' => $this->Betreff,
            ]);
    }
}
