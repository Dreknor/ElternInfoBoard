<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestMailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Mail-Versand mit aktueller Konfiguration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        $this->info('=== Mail-Konfiguration ===');
        $this->info('Default Mailer: ' . (config('mail.default') ?? 'NICHT GESETZT'));
        $this->info('SMTP Host: ' . (config('mail.mailers.smtp.host') ?? 'NICHT GESETZT'));
        $this->info('SMTP Port: ' . (config('mail.mailers.smtp.port') ?? 'NICHT GESETZT'));
        $this->info('SMTP Username: ' . (config('mail.mailers.smtp.username') ?? 'NICHT GESETZT'));
        $this->info('SMTP Encryption: ' . (config('mail.mailers.smtp.encryption') ?? 'NICHT GESETZT'));
        $this->info('From Address: ' . (config('mail.from.address') ?? 'NICHT GESETZT'));
        $this->info('From Name: ' . (config('mail.from.name') ?? 'NICHT GESETZT'));
        $this->info('');

        $this->info('Sende Test-Mail an: ' . $email);

        try {
            Mail::raw('Dies ist eine Test-Mail von ElternInfoBoard.', function ($message) use ($email) {
                $message->to($email)
                    ->subject('Test-Mail von ElternInfoBoard');
            });

            $this->info('✓ Mail erfolgreich versendet!');
            return 0;
        } catch (\Exception $e) {
            $this->error('✗ Fehler beim Mail-Versand: ' . $e->getMessage());
            return 1;
        }
    }
}

