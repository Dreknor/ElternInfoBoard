<?php

namespace App\Mail;

use App\Settings\GeneralSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $userName;
    public string $postTitle;
    public int $postId;
    public string $deadline;
    public int $level;
    public string $type; // 'rueckmeldung', 'lesebestaetigung', 'anwesenheit'
    public string $boardName;

    public function __construct(
        string $userName,
        string $postTitle,
        int $postId,
        string $deadline,
        int $level,
        string $type = 'rueckmeldung'
    ) {
        $this->userName = $userName;
        $this->postTitle = $postTitle;
        $this->postId = $postId;
        $this->deadline = $deadline;
        $this->level = $level;
        $this->type = $type;
        $this->boardName = (new GeneralSetting)->app_name;
    }

    public function build(): static
    {
        $subject = match ($this->level) {
            1 => $this->getSubjectLevel1(),
            2 => $this->getSubjectLevel2(),
            3 => $this->getSubjectLevel3(),
            default => 'Erinnerung: Rückmeldung ausstehend',
        };

        return $this
            ->subject($subject)
            ->view('emails.reminder')
            ->with([
                'userName' => $this->userName,
                'postTitle' => $this->postTitle,
                'postId' => $this->postId,
                'deadline' => $this->deadline,
                'level' => $this->level,
                'type' => $this->type,
                'boardName' => $this->boardName,
            ]);
    }

    private function getSubjectLevel1(): string
    {
        return match ($this->type) {
            'lesebestaetigung' => 'Erinnerung: Lesebestätigung für ' . $this->postTitle,
            'anwesenheit' => 'Erinnerung: Anwesenheitsabfrage ausstehend',
            default => 'Erinnerung: Rückmeldung für ' . $this->postTitle,
        };
    }

    private function getSubjectLevel2(): string
    {
        return match ($this->type) {
            'lesebestaetigung' => 'Dringend: Lesebestätigung für ' . $this->postTitle . ' fehlt',
            'anwesenheit' => 'Dringend: Anwesenheitsabfrage läuft bald ab',
            default => 'Dringend: Rückmeldung für ' . $this->postTitle . ' fehlt',
        };
    }

    private function getSubjectLevel3(): string
    {
        return match ($this->type) {
            'lesebestaetigung' => 'WICHTIG: Lesebestätigung für ' . $this->postTitle . ' - Frist abgelaufen',
            'anwesenheit' => 'WICHTIG: Anwesenheitsabfrage - Frist abgelaufen',
            default => 'WICHTIG: Rückmeldung für ' . $this->postTitle . ' - Frist abgelaufen',
        };
    }
}


