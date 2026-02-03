<?php

namespace App\Mail;

use App\Model\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewUserPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;

    public string $password;

    public string $welcomeText;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, string $password, string $welcomeText)
    {
        $this->user = $user;
        $this->password = $password;
        $this->welcomeText = $welcomeText;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: 'Ihr Zugang zu '.config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            view: 'emails.newUserPassword',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}
