<?php

namespace App\Mail;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AktuelleInformationen extends Mailable
{
    use Queueable, SerializesModels;

    protected Collection|array $news;

    protected string $name;

    protected Collection|array $diskussionen;

    protected Collection|array $termine;

    //protected $files;

    protected Collection|array $listen;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Collection|array $news, string $name, Collection|array $diskussionen, Collection|array $listen, Collection|array $termine)
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
    public function build(): static
    {
        return $this->subject('Aktuelle Informationen')
            ->view('emails.nachrichten', [
                'nachrichten' => $this->news,
                'name' => $this->name,
                'discussionen' => $this->diskussionen,
                'listen' => $this->listen,
                'termine' => $this->termine,
            ]);
    }
}
