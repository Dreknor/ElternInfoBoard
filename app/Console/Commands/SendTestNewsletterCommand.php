<?php

namespace App\Console\Commands;

use App\Mail\AktuelleInformationen;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class SendTestNewsletterCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'newsletter:test {email : Die E-Mail-Adresse, an die gesendet werden soll}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sendet eine Test-Mail mit Demo-Daten für die Newsletter-Benachrichtigung';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Ungültige E-Mail-Adresse!');
            return self::FAILURE;
        }

        $this->info('Erstelle Demo-Daten...');

        // Demo-Nachrichten (intern)
        $news = new Collection([
            (object)[
                'header' => 'Wichtige Information zur Schulveranstaltung',
                'external' => 0,
                'created_at' => now()->subHours(2),
            ],
            (object)[
                'header' => 'Neues aus dem Schulvorstand',
                'external' => 0,
                'created_at' => now()->subHours(5),
            ],
            (object)[
                'header' => 'Elternabend: Termine für das kommende Halbjahr',
                'external' => 0,
                'created_at' => now()->subDay(),
            ],
        ]);

        // Demo-Nachrichten (extern)
        $newsExternal = new Collection([
            (object)[
                'header' => 'Ferienangebot: Sommercamp 2025',
                'external' => 1,
                'created_at' => now()->subHours(3),
            ],
            (object)[
                'header' => 'Kulturelles Angebot: Theaterworkshop für Kinder',
                'external' => 1,
                'created_at' => now()->subHours(6),
            ],
        ]);

        // Alle Nachrichten zusammenführen
        $allNews = $news->merge($newsExternal);

        // Demo-Diskussionen
        $diskussionen = new Collection([
            (object)[
                'header' => 'Diskussion: Schulhofgestaltung',
            ],
            (object)[
                'header' => 'Austausch: Digitalisierung im Unterricht',
            ],
        ]);

        // Demo-Listen
        $listen = new Collection([
            (object)[
                'listenname' => 'Klassenliste 5a - Aktualisiert',
            ],
            (object)[
                'listenname' => 'Teilnehmerliste Schulausflug',
            ],
        ]);

        // Demo-Termine
        $termine = new Collection([
            (object)[
                'terminname' => 'Elternsprechtag',
                'start' => now()->addDays(14)->setTime(15, 0),
                'ende' => now()->addDays(14)->setTime(19, 0),
            ],
            (object)[
                'terminname' => 'Sommerfest',
                'start' => now()->addDays(30)->setTime(14, 0),
                'ende' => now()->addDays(30)->setTime(18, 0),
            ],
            (object)[
                'terminname' => 'Projektwoche',
                'start' => now()->addDays(45)->setTime(8, 0),
                'ende' => now()->addDays(49)->setTime(13, 0),
            ],
        ]);

        // Demo-GTA
        $gta = new Collection([
            (object)[
                'name' => 'Fußball AG',
            ],
            (object)[
                'name' => 'Chor',
            ],
            (object)[
                'name' => 'Robotik AG',
            ],
        ]);

        $this->info('Sende Test-Mail an: ' . $email);

        try {
            Mail::to($email)->send(
                new AktuelleInformationen(
                    news: $allNews->toArray(),
                    name: 'Max Mustermann',
                    diskussionen: $diskussionen->toArray(),
                    listen: $listen->toArray(),
                    termine: $termine->toArray(),
                    gta: $gta->toArray()
                )
            );

            $this->newLine();
            $this->info('✓ Test-Mail erfolgreich versendet!');
            $this->newLine();
            $this->line('Empfänger: ' . $email);
            $this->line('Nachrichten (intern): ' . $news->count());
            $this->line('Nachrichten (extern): ' . $newsExternal->count());
            $this->line('Diskussionen: ' . $diskussionen->count());
            $this->line('Listen: ' . $listen->count());
            $this->line('Termine: ' . $termine->count());
            $this->line('GTA: ' . $gta->count());

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Fehler beim Versenden der E-Mail:');
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}

