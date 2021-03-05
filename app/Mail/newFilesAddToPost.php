<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Class newFilesAddToPost
 */
class newFilesAddToPost extends Mailable
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
     * newFilesAddToPost constructor.
     * @param $von
     * @param $Betreff
     */
    public function __construct($von, $Betreff)
    {
        $this->von = $von;
        $this->Betreff = $Betreff;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->subject('neuer Bild-Upload')
            ->view('emails.newImageToPost', [
                'von'   => $this->von,
                'betreff'   => $this->Betreff,
            ]);
    }
}
