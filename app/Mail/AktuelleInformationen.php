<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
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

    protected Collection|array $gta;

    protected ?array $pflichtstunden;

    // protected $files;

    protected Collection|array $listen;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    /**
     * Abschnitte dürfen als Eloquent- oder einfache Collection bzw. Array kommen
     * (z. B. collect() bei fehlendem Modulrecht). Intern Eloquent-Collections, damit
     * die Warteschlange Modelle nur als ID serialisiert.
     */
    public function __construct(BaseCollection|array $news, string $name, BaseCollection|array $diskussionen, BaseCollection|array $listen, BaseCollection|array $termine, BaseCollection|array $gta, ?array $pflichtstunden = null)
    {
        $news = self::models($news);
        $diskussionen = self::models($diskussionen);
        $listen = self::models($listen);
        $termine = self::models($termine);
        $gta = self::models($gta);

        $this->news = $news->filter(function ($post) {
            return $post->external == 0;
        });

        $this->news_external = $news->filter(function ($post) {
            return $post->external != 0;
        });

        $this->name = $name;
        $this->diskussionen = $diskussionen;
        $this->listen = $listen;
        $this->termine = $termine;
        $this->gta = $gta;
        $this->pflichtstunden = $pflichtstunden;

    }

    private static function models(BaseCollection|array $items): Collection
    {
        return $items instanceof Collection ? $items : new Collection($items instanceof BaseCollection ? $items->all() : $items);
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
                'gta' => $this->gta,
                'pflichtstunden' => $this->pflichtstunden,
            ]);
    }
}
