<?php

namespace Tests\Feature\API;

use App\Mail\SendFeedback;
use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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
}
