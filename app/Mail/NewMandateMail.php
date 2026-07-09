<?php

namespace App\Mail;

use App\Model\ChildMandate;
use App\Model\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewMandateMail extends Mailable
{
    use Queueable, SerializesModels;

    public ChildMandate $mandate;

    public function __construct(ChildMandate $mandate)
    {
        $this->mandate = $mandate;
    }

    public function build(): static
    {
        $this->mandate->loadMissing('child');
        $creator = $this->mandate->created_by
            ? User::find($this->mandate->created_by)
            : null;

        return $this
            ->subject('Neue Abholvollmacht eingetragen – ' . $this->mandate->child->first_name . ' ' . $this->mandate->child->last_name)
            ->view('emails.newMandate', [
                'mandate' => $this->mandate,
                'child'   => $this->mandate->child,
                'creator' => $creator,
            ]);
    }
}
