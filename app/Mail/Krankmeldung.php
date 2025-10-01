<?php

namespace App\Mail;

use App\Model\Disease;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class Krankmeldung extends Mailable
{
    use Queueable, SerializesModels;

    public string $email;

    public string $name;

    public string $NameDesKindes;

    public string $krankVon;

    public string $krankBis;

    public string $bemerkung;

    public ?string $disease;

    /**
     * media attachments (array of Spatie\MediaLibrary\Models\Media)
     * @var array
     */
    public array $attachments = [];

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $Email, string $Name, string $NameDesKindes, string $krankVon, string $krankBis, string $bemerkung, ?string $disease, array $attachments = [])
    {
        $this->email = $Email;
        $this->name = $Name;
        $this->NameDesKindes = $NameDesKindes;
        $this->krankVon = $krankVon;
        $this->krankBis = $krankBis;
        $this->bemerkung = $bemerkung;
        $this->disease = $disease;
        $this->attachments = $attachments;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): static
    {
        return $this
            ->subject('Krankmeldung '.$this->NameDesKindes.': '.$this->krankVon.' - '.$this->krankBis)
            ->view('emails.krankmeldung');
    }

    /**
     * Attachments for the message.
     * This will be called by the mailer to get attachments for this mailable.
     *
     * @return \Illuminate\Mail\Mailables\Attachment[]
     */
    public function attachments(): array
    {
        $files = [];

        if (count($this->attachments) > 0) {
            foreach ($this->attachments as $media) {
                // $media expected to be a Spatie Media model
                if (method_exists($media, 'getPathRelativeToRoot') && isset($media->disk)) {
                    $files[] = Attachment::fromStorageDisk($media->disk, $media->getPathRelativeToRoot())
                        ->as($media->file_name);
                } elseif (method_exists($media, 'getPath')) {
                    $files[] = Attachment::fromPath($media->getPath())
                        ->as($media->file_name);
                }
            }
        }

        return $files;
    }
}
