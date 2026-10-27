<?php

namespace App\Mail;

use App\Model\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Benachrichtigung eines bestehenden Kontos, dass es durch den Vereins-Import
 * in den Verein aufgenommen oder daraus entfernt wurde.
 */
class VereinMembershipChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public const ADDED = 'added';

    public const REMOVED = 'removed';

    public function __construct(public User $user, public string $change, public string $text) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->change === self::ADDED
                ? 'Ihr Zugang als Vereinsmitglied bei '.config('app.name')
                : 'Ihre Vereinsmitgliedschaft bei '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.vereinMembershipChanged');
    }
}
