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
    public string $userName;
    public string $deadline;
    public string $type;
    public string $boardName;

    public function __construct(
        string $authorName,
        string $postTitle,
        int $postId,
        string $userName,
        string $deadline,
        string $type = 'rueckmeldung'
    ) {
        $this->authorName = $authorName;
        $this->postTitle = $postTitle;
        $this->postId = $postId;
        $this->userName = $userName;
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

        return $this
            ->subject('Eskalation: ' . $typeLabel . ' für ' . $this->postTitle . ' überfällig')
            ->view('emails.reminder-escalation')
            ->with([
                'authorName' => $this->authorName,
                'postTitle' => $this->postTitle,
                'postId' => $this->postId,
                'userName' => $this->userName,
                'deadline' => $this->deadline,
                'type' => $this->type,
                'typeLabel' => $typeLabel,
                'boardName' => $this->boardName,
            ]);
    }
}


