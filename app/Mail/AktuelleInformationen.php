<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class AktuelleInformationen extends Mailable
{
    use Queueable, SerializesModels;

    protected $news;
    protected $name;
    protected $diskussionen;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($news, $name, $diskussionen)
    {
        $this->news = $news;
        $this->name = $name;
        $this->diskussionen = $diskussionen;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Aktuelle Informationen')
            ->view('emails.nachrichten',[
            "nachrichten" => $this->news,
            "name"      => $this->name,
            "discussionen"  => $this->diskussionen
        ]);
    }
}
