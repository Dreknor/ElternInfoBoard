<?php

namespace Tests\Feature\API;

use App\Jobs\SendKontaktMail;
use App\Mail\SendFeedback;
use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ContactControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_keeps_line_breaks_of_plain_text_from_the_app(): void
    {
        Mail::fake();
        Sanctum::actingAs(User::factory()->create());
        $mitarbeiter = User::factory()->create();
        $mitarbeiter->assignRole(Role::findOrCreate('Mitarbeiter', 'web'));

        $this->postJson('/api/contact', [
            'mitarbeiter' => $mitarbeiter->id,
            'betreff' => 'Frage',
            'text' => "Hallo,\n\nerste Zeile\nzweite Zeile & mehr",
        ])->assertOk();

        Mail::assertSent(SendFeedback::class, function (SendFeedback $mail) {
            $html = $mail->render();
            $this->assertStringContainsString("Hallo,<br>\n<br>\nerste Zeile<br>\nzweite Zeile &amp; mehr", $html);

            return true;
        });
    }

    /** @test */
    public function it_answers_immediately_and_sends_the_mail_in_the_background(): void
    {
        Queue::fake();
        Storage::fake('local');
        Sanctum::actingAs(User::factory()->create());
        $mitarbeiter = User::factory()->create();
        $mitarbeiter->assignRole(Role::findOrCreate('Mitarbeiter', 'web'));

        $this->post('/api/contact', [
            'mitarbeiter' => $mitarbeiter->id,
            'betreff' => 'Krankmeldung',
            'text' => 'Anbei der Krankenschein.',
            'files' => [UploadedFile::fake()->create('krankenschein.pdf', 500, 'application/pdf')],
        ], ['Accept' => 'application/json'])->assertOk();

        Queue::assertPushed(SendKontaktMail::class);
        $this->assertCount(1, Storage::disk('local')->allFiles(SendKontaktMail::DIRECTORY));
    }

    /** @test */
    public function it_attaches_the_files_and_removes_them_after_sending(): void
    {
        Mail::fake();
        Storage::fake('local');
        Sanctum::actingAs(User::factory()->create());
        $mitarbeiter = User::factory()->create();
        $mitarbeiter->assignRole(Role::findOrCreate('Mitarbeiter', 'web'));

        $this->post('/api/contact', [
            'mitarbeiter' => $mitarbeiter->id,
            'betreff' => 'Krankmeldung',
            'text' => 'Anbei der Krankenschein.',
            'files' => [
                UploadedFile::fake()->create('krankenschein.pdf', 500, 'application/pdf'),
                UploadedFile::fake()->image('foto.jpg'),
            ],
        ], ['Accept' => 'application/json'])->assertOk();

        Mail::assertSent(SendFeedback::class, function (SendFeedback $mail) {
            $names = array_map(fn ($a) => $a->as, $mail->attachments());
            $this->assertSame(['krankenschein.pdf', 'foto.jpg'], $names);

            return true;
        });
        $this->assertSame([], Storage::disk('local')->allFiles(SendKontaktMail::DIRECTORY));
    }
}
