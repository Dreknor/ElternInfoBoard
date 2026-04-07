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

    public string $von;

    public string $Betreff;

    public int $postId;

    /**
     * newFilesAddToPost constructor.
     */
    public function __construct(string $von, string $Betreff, int $postId)
    {
        $this->von = $von;
        $this->Betreff = $Betreff;
        $this->postId = $postId;
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
                'postId' => $this->postId,
            ]);
    }
}
