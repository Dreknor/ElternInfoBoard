<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AktuelleInformationen extends Mailable
{
    use Queueable, SerializesModels;

    protected $news;
    protected $name;
    protected $diskussionen;
    protected $termine;
    protected $files;
    protected $listen;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($news, $name, $diskussionen, $listen, $termine)
    {
        $this->news = $news;
        $this->name = $name;
        $this->diskussionen = $diskussionen;
        $this->listen = $listen;
        $this->termine = $termine;

    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Aktuelle Informationen')
            ->view('emails.nachrichten', [
                'nachrichten' => $this->news,
                'name'      => $this->name,
                'discussionen'  => $this->diskussionen,
                'listen'=>$this->listen,
                'termine'=>$this->termine
        ]);
    }
}
