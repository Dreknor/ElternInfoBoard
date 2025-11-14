<?php

namespace App\Listeners;

use App\Settings\EmailSetting;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Log;

class LogEmailSent
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(MessageSent $event): void
    {
        // Prüfen, ob E-Mail-Logging aktiviert ist
        $emailSettings = app(EmailSetting::class);

        if (!isset($emailSettings->log_sent_emails) || !$emailSettings->log_sent_emails) {
            return;
        }

        // Empfänger sammeln
        $recipients = [];

        if ($event->message->getTo()) {
            foreach ($event->message->getTo() as $address) {
                $recipients[] = $address->getName() ?
                    $address->getName() . ' <' . $address->getAddress() . '>' :
                    $address->getAddress();
            }
        }

        // CC-Empfänger hinzufügen
        if ($event->message->getCc()) {
            foreach ($event->message->getCc() as $address) {
                $recipients[] = 'CC: ' . ($address->getName() ?
                    $address->getName() . ' <' . $address->getAddress() . '>' :
                    $address->getAddress());
            }
        }

        // BCC-Empfänger hinzufügen
        if ($event->message->getBcc()) {
            foreach ($event->message->getBcc() as $address) {
                $recipients[] = 'BCC: ' . ($address->getName() ?
                    $address->getName() . ' <' . $address->getAddress() . '>' :
                    $address->getAddress());
            }
        }

        // Betreff holen
        $subject = $event->message->getSubject() ?? '(Kein Betreff)';

        // Absender ermitteln
        $from = '';
        if ($event->message->getFrom()) {
            $fromAddresses = $event->message->getFrom();
            if (count($fromAddresses) > 0) {
                $fromAddress = $fromAddresses[0];
                $from = $fromAddress->getName() ?
                    $fromAddress->getName() . ' <' . $fromAddress->getAddress() . '>' :
                    $fromAddress->getAddress();
            }
        }
        if (empty($from)) {
            $from = config('mail.from.address');
        }

        Log::debug('E-Mail gesendet:' , [
            'Betreff' => $subject,
            'Empfänger' => $recipients,
            'Absender' => $from,
            'Zeitpunkt' => now()->format('d.m.Y H:i:s')
        ]);

        Log::debug(
            'E-Mail gesendet: ' . $subject . ' an ' . implode(', ', $recipients) . ' von ' . $from);

    }
}

