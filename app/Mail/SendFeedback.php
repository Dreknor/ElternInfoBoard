<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendFeedback extends Mailable
{
    use Queueable, SerializesModels;

    public string $text;

    public string $von;

    protected string $betreff;

    public array $data;

    protected ?string $replyToEmail;

    protected ?string $replyToName;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $text, string $betreff, array $data = [], ?string $von = null, ?string $replyToEmail = null, ?string $replyToName = null)
    {
        $this->text = $text;
        $this->betreff = $betreff;
        $this->data = $data;
        $this->von = $von ?? auth()->user()?->name ?? '';
        $this->replyToEmail = $replyToEmail;
        $this->replyToName = $replyToName;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        $envelopeData = [
            'from' => new Address(config('mail.from.address'), config('mail.from.name')),
            'subject' => $this->betreff,
        ];

        // Only add replyTo if we have a valid email address
        if ($this->replyToEmail) {
            $envelopeData['replyTo'] = [
                new Address($this->replyToEmail, $this->replyToName ?? $this->von),
            ];
        }

        return new Envelope(...$envelopeData);
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            view: 'emails.feedback',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return \Illuminate\Mail\Mailables\Attachment[]
     */
    public function attachments()
    {
        $attachments = [];

        // Handle Spatie Media attachments (original implementation)
        if (count($this->data) > 0 && array_key_exists('document', $this->data)) {
            foreach ($this->data['document'] as $file) {
                // Use Storage-based attachment to avoid issues with absolute paths / permissions
                // $file is expected to be a Spatie Media model
                if (method_exists($file, 'getPathRelativeToRoot') && isset($file->disk)) {
                    $attachments[] = Attachment::fromStorageDisk($file->disk, $file->getPathRelativeToRoot())
                        ->as($file->file_name);
                } elseif (method_exists($file, 'getPath')) {
                    // Fallback to direct path if available
                    $attachments[] = Attachment::fromPath($file->getPath())
                        ->as($file->file_name ?? basename($file->getPath()));
                }
            }
        }

        // Handle direct file attachments from request
        if (count($this->data) > 0 && array_key_exists('attachments', $this->data)) {
            foreach ($this->data['attachments'] as $fileData) {
                if (isset($fileData['path']) && isset($fileData['name'])) {
                    $attachment = Attachment::fromPath($fileData['path'])
                        ->as($fileData['name']);

                    if (isset($fileData['mime'])) {
                        $attachment->withMime($fileData['mime']);
                    }

                    $attachments[] = $attachment;
                }
            }
        }

        return $attachments;
    }
}
