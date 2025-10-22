<?php

namespace App\Mail;

use App\Model\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DringendeInformationen extends Mailable
{
    use Queueable, SerializesModels;

    public Post $post;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): static
    {
        $mail = $this->subject($this->post->header)
            ->view('emails.dringendeNachricht', [
                'post' => $this->post,
                'header' => $this->post->header,
                'nachricht' => $this->post->news,
            ]);

        // Anhänge hinzufügen
        foreach ($this->post->getMedia('images') as $media) {
            $mail->attach($media->getPath(), [
                'as' => $media->file_name,
                'mime' => $media->mime_type,
            ]);
        }

        return $mail;
    }
}
