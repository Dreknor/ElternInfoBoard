<?php

namespace Tests\Feature\Web;

use App\Mail\SendFeedback;
use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Web-Kontaktformular: Kopie (CC) nur bei „Kopie meiner Rückmeldungen“.
 */
class KontaktKopieTest extends TestCase
{
    use RefreshDatabase;

    private function sendAs(User $absender): void
    {
        $mitarbeiter = User::factory()->create(['email' => 'lehrkraft@example.org']);
        $mitarbeiter->assignRole(Role::findOrCreate('Mitarbeiter', 'web'));

        $this->actingAs($absender)->post('/feedback', [
            'mitarbeiter' => $mitarbeiter->id,
            'betreff' => 'Frage',
            'text' => 'Hallo',
        ])->assertRedirect();
    }

    /** @test */
    public function the_sender_receives_a_copy_when_send_copy_is_enabled(): void
    {
        Mail::fake();
        $this->sendAs(User::factory()->create(['email' => 'eltern@example.org', 'sendCopy' => 1]));

        Mail::assertSent(SendFeedback::class, fn (SendFeedback $mail) => $mail->hasTo('lehrkraft@example.org') && $mail->hasCc('eltern@example.org'));
    }

    /** @test */
    public function the_sender_receives_no_copy_when_send_copy_is_disabled(): void
    {
        Mail::fake();
        $this->sendAs(User::factory()->create(['email' => 'eltern@example.org', 'sendCopy' => 0]));

        Mail::assertSent(SendFeedback::class, fn (SendFeedback $mail) => $mail->hasTo('lehrkraft@example.org') && ! $mail->hasCc('eltern@example.org'));
    }
}
