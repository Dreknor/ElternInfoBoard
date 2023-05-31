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
    protected Collection|array $news_external;

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
            $news = new Collection($news);


        $this->news = $news->filter(function ($post){
           return $post->external == 0;
        });

        $this->news_external = $news->filter(function ($post){
           return $post->external != 0;
        });


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
                'nachrichten_extern' => $this->news_external,
                'name' => $this->name,
                'discussionen' => $this->diskussionen,
                'listen' => $this->listen,
                'termine' => $this->termine,
            ]);
    }
}
