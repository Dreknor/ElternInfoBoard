<?php

namespace Tests\Feature;

use App\Mail\SendFeedback;
use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Kontaktformular mit mehreren Empfängern (Web und API); einzelne ID bleibt
 * für ältere App-Versionen gültig.
 */
class KontaktMehrereEmpfaengerTest extends TestCase
{
    use RefreshDatabase;

    private function contact(string $email): User
    {
        $user = User::factory()->create(['email' => $email]);
        $user->assignRole(Role::findOrCreate('Mitarbeiter', 'web')
            ->givePermissionTo(Permission::findOrCreate('show in contact form', 'web')));

        return $user;
    }

    private function parent(): User
    {
        return User::factory()->create(['changePassword' => false, 'sendCopy' => false]);
    }

    /** @test */
    public function web_form_sends_one_mail_to_all_selected_people(): void
    {
        Mail::fake();
        $a = $this->contact('a@schule.de');
        $b = $this->contact('b@schule.de');

        $this->actingAs($this->parent())->post('/feedback', [
            'mitarbeiter' => [$a->id, $b->id],
            'betreff' => 'Frage',
            'text' => 'Hallo',
        ])->assertSessionHas('Meldung', 'Nachricht wurde versandt');

        Mail::assertSent(SendFeedback::class, fn (SendFeedback $m) => $m->hasTo('a@schule.de') && $m->hasTo('b@schule.de'));
        Mail::assertSentCount(1);
    }

    /** @test */
    public function web_form_without_selection_goes_to_the_office(): void
    {
        Mail::fake();
        config(['mail.from.address' => 'sekretariat@schule.de']);

        $this->actingAs($this->parent())->post('/feedback', [
            'betreff' => 'Frage',
            'text' => 'Hallo',
        ])->assertSessionHas('Meldung', 'Nachricht wurde versandt');

        Mail::assertSent(SendFeedback::class, fn (SendFeedback $m) => $m->hasTo('sekretariat@schule.de'));
    }

    /** @test */
    public function a_non_contact_in_the_list_rejects_the_whole_message(): void
    {
        Mail::fake();
        $a = $this->contact('a@schule.de');
        $stranger = User::factory()->create(['email' => 'fremd@example.org']);

        $this->actingAs($this->parent())->post('/feedback', [
            'mitarbeiter' => [$a->id, $stranger->id],
            'betreff' => 'Frage',
            'text' => 'Hallo',
        ])->assertSessionHas('Meldung', 'Empfänger nicht gefunden.');

        Mail::assertNothingSent();
    }

    /** @test */
    public function more_than_ten_recipients_are_rejected(): void
    {
        $ids = collect(range(1, 11))->map(fn ($i) => $this->contact("p{$i}@schule.de")->id)->all();

        $this->actingAs($this->parent())->post('/feedback', [
            'mitarbeiter' => $ids,
            'betreff' => 'Frage',
            'text' => 'Hallo',
        ])->assertSessionHasErrors('mitarbeiter');
    }

    /** @test */
    public function api_accepts_a_list_and_still_a_single_id(): void
    {
        Mail::fake();
        $a = $this->contact('a@schule.de');
        $b = $this->contact('b@schule.de');
        Sanctum::actingAs($this->parent());

        $this->postJson('/api/contact', ['mitarbeiter' => [$a->id, $b->id], 'betreff' => 'X', 'text' => 'Y'])->assertOk();
        Mail::assertSent(SendFeedback::class, fn (SendFeedback $m) => $m->hasTo('a@schule.de') && $m->hasTo('b@schule.de'));

        // Ältere App-Versionen senden eine einzelne ID
        $this->postJson('/api/contact', ['mitarbeiter' => $b->id, 'betreff' => 'X', 'text' => 'Y'])->assertOk();
        Mail::assertSent(SendFeedback::class, fn (SendFeedback $m) => $m->hasTo('b@schule.de') && ! $m->hasTo('a@schule.de'));
    }

    /** @test */
    public function api_contact_list_announces_multiple_recipients(): void
    {
        $this->contact('a@schule.de');
        Sanctum::actingAs($this->parent());

        $this->getJson('/api/contact')
            ->assertOk()
            ->assertJsonPath('meta.multiple', true)
            ->assertJsonPath('meta.max', 10)
            ->assertJsonPath('data.0.id', 0);
    }
}
