<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DringendeInformationen extends Mailable
{
    use Queueable, SerializesModels;

    public string $header;

    public string $text;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $header, string $text)
    {
        $this->header = $header;
        $this->text = $text;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): static
    {
        return $this->subject($this->header)
            ->view('emails.dringendeNachricht', [
                'nachricht' => $this->text,
                'header' => $this->header,
            ]);
    }
}
