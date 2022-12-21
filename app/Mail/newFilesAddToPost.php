<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Class newFilesAddToPost
 */
class newFilesAddToPost extends Mailable
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
     * newFilesAddToPost constructor.
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
     * Build the message.
     *
     * @return $this
     */
    public function build(): static
    {
        return $this
            ->subject('neuer Bild-Upload')
            ->view('emails.newImageToPost', [
                'von' => $this->von,
                'betreff' => $this->Betreff,
            ]);
    }
}
