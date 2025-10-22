<?php

namespace App\Console\Commands;

use App\Mail\DringendeInformationen;
use App\Model\Group;
use App\Model\Post;
use App\Model\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTestUrgentMessageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'message:test-urgent {email : Die E-Mail-Adresse, an die gesendet werden soll}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sendet eine Test-Mail für dringende Nachrichten mit Demo-Daten und Anhängen';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('❌ Ungültige E-Mail-Adresse!');
            return self::FAILURE;
        }

        $this->info('📧 Erstelle Test-Nachricht für dringende Informationen...');

        // Erstelle einen temporären Test-Post
        $testPost = new Post([
            'header' => 'Test: Dringende Information - Schulausfall morgen',
            'news' => '<h2>Wichtige Mitteilung</h2>
                <p>Sehr geehrte Eltern,</p>
                <p>aufgrund der aktuellen Wetterlage fällt der Unterricht <strong>morgen (22.10.2025)</strong> aus.</p>
                <p>Weitere Informationen:</p>
                <ul>
                    <li>Die Notbetreuung findet statt</li>
                    <li>Bitte informieren Sie Ihre Kinder</li>
                    <li>Der Unterricht wird am Mittwoch regulär fortgesetzt</li>
                </ul>
                <p>Bei Fragen erreichen Sie uns unter der bekannten Telefonnummer.</p>
                <p>Mit freundlichen Grüßen<br>Die Schulleitung</p>',
            'released' => 1,
            'author' => 1,
            'archiv_ab' => Carbon::now()->addWeeks(2),
            'type' => 'news',
            'reactable' => false,
            'external' => false,
            'send_at' => Carbon::now(),
            'read_receipt' => false,
            'no_header' => false,
        ]);

        // Setze zusätzliche Attribute ohne sie zu speichern
        $testPost->id = 9999;
        $testPost->created_at = Carbon::now()->subHours(1);
        $testPost->updated_at = Carbon::now()->subHours(1);

        // Erstelle einen Test-Autor
        $testAutor = new User([
            'name' => 'Max Mustermann (Test)',
            'email' => 'test@example.com',
        ]);
        $testAutor->id = 1;

        // Füge den Autor zum Post hinzu
        $testPost->setRelation('autor', $testAutor);

        // Simuliere die Media Collection (leer für den Test)
        $testPost->setRelation('media', collect([]));

        $this->info('✅ Test-Post erstellt');
        $this->line('   Titel: ' . $testPost->header);
        $this->line('   Autor: ' . $testAutor->name);

        // Versende die Test-Mail
        try {
            $this->info('📤 Versende Test-Mail an: ' . $email);

            Mail::to($email)->send(new DringendeInformationen($testPost));

            $this->newLine();
            $this->info('✅ Test-Mail erfolgreich versendet!');
            $this->line('');
            $this->line('📋 Details:');
            $this->line('   Empfänger: ' . $email);
            $this->line('   Betreff: ' . $testPost->header);
            $this->line('   Zeitpunkt: ' . Carbon::now()->format('d.m.Y H:i:s'));
            $this->newLine();
            $this->comment('💡 Tipp: Überprüfen Sie Ihren Posteingang und ggf. den Spam-Ordner.');

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ Fehler beim Versenden der Test-Mail:');
            $this->error('   ' . $e->getMessage());
            $this->newLine();
            $this->comment('💡 Überprüfen Sie die Mail-Konfiguration in der .env Datei');

            return self::FAILURE;
        }
    }
}

