<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;

use Illuminate\Mail\Mailables\Content;

class SendFeedback extends Mailable
{
    use Queueable, SerializesModels;

    public string $text;
    public string $von;

    protected string $betreff;

    public array $data;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $text, string $betreff, array $data = [])
    {
        $this->text = $text;
        $this->betreff = $betreff;
        $this->data = $data;
        $this->von = auth()->user()->name;
    }


    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            replyTo: [
                new Address(request()->user()->email, request()->user()->name),
            ],
            subject: $this->betreff,
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

        if (count($this->data) > 0 and array_key_exists('document', $this->data)) {
            $return = [];
            foreach ($this->data['document'] as $file) {
                $return[] = Attachment::fromPath($file->getRealPath())
                    ->as($file->getClientOriginalName());
            }

            return $return;
        }
        return [

        ];
    }
}
