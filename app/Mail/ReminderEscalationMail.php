<?php

namespace App\Mail;

use App\Settings\GeneralSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReminderEscalationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $authorName;
    public string $postTitle;
    public int $postId;
    /** @var string[] */
    public array $userNames;
    public string $deadline;
    public string $type;
    public string $boardName;

    /**
     * @param string[] $userNames  Alle Personen, die noch nicht geantwortet haben
     */
    public function __construct(
        string $authorName,
        string $postTitle,
        int $postId,
        array $userNames,
        string $deadline,
        string $type = 'rueckmeldung'
    ) {
        $this->authorName = $authorName;
        $this->postTitle = $postTitle;
        $this->postId = $postId;
        $this->userNames = $userNames;
        $this->deadline = $deadline;
        $this->type = $type;
        $this->boardName = (new GeneralSetting)->app_name;
    }

    public function build(): static
    {
        $typeLabel = match ($this->type) {
            'lesebestaetigung' => 'Lesebestätigung',
            'anwesenheit' => 'Anwesenheitsabfrage',
            default => 'Rückmeldung',
        };

        $count = count($this->userNames);

        return $this
            ->subject('Eskalation: ' . $count . ' ausstehende ' . $typeLabel . ($count !== 1 ? 'en' : '') . ' – ' . $this->postTitle)
            ->view('emails.reminder-escalation')
            ->with([
                'authorName' => $this->authorName,
                'postTitle' => $this->postTitle,
                'postId' => $this->postId,
                'userNames' => $this->userNames,
                'deadline' => $this->deadline,
                'type' => $this->type,
                'typeLabel' => $typeLabel,
                'boardName' => $this->boardName,
            ]);
    }
}
