<?php

namespace Tests\Feature\Elternabfragen;

use App\Enums\InquiryAssignmentStatus;
use App\Mail\ElternabfrageMail;
use App\Model\InquiryAssignment;
use App\Model\InquiryConfirmation;
use App\Model\InquiryDelivery;
use App\Model\InquiryTemplateVersion;
use App\Model\Notification;
use App\Services\Elternabfragen\ReminderService;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;

/**
 * Vorlagen mit „Alle Sorgeberechtigten müssen bestätigen“: Mutter reicht ein,
 * Vater bestätigt; Oma (ohne Sorgerecht) zählt nicht.
 */
class BestaetigungAllerSorgeberechtigtenTest extends ElternabfragenTestCase
{
    private function submitAsMother(): InquiryAssignment
    {
        $inquiry = $this->runningInquiry([], ['requires_all_guardians' => true]);
        $assignment = $this->assignmentFor($inquiry);

        $this->actingAs($this->mutter)
            ->post(route('elternabfragen.submit', $assignment), ['answers' => $this->validAnswers(), 'confirm' => 1])
            ->assertRedirect(route('elternabfragen.show', $assignment));

        return $assignment->fresh();
    }

    #[Test]
    public function option_wird_in_der_vorlage_gespeichert(): void
    {
        $this->actingAs($this->sekretariat)->post(route('elternabfragen.admin.templates.store'), [
            'title' => 'Schulwechsel',
            'purpose' => 'Gemeinsame Erklärung beider Elternteile.',
            'default_deadline_days' => 10,
            'requires_all_guardians' => 1,
            'fields_json' => json_encode($this->fields()),
        ])->assertRedirect();

        $version = InquiryTemplateVersion::firstOrFail();
        $this->assertTrue($version->requires_all_guardians);

        $this->get(route('elternabfragen.admin.templates.show', $version))->assertOk()->assertSee('Bestätigung aller Sorgeberechtigten');
    }

    #[Test]
    public function antwort_bleibt_offen_bis_alle_sorgeberechtigten_bestaetigt_haben(): void
    {
        $assignment = $this->submitAsMother();

        $this->assertSame(InquiryAssignmentStatus::AwaitingConfirmation, $assignment->status);
        $this->assertFalse($assignment->isComplete());
        $this->assertTrue(Notification::where('user_id', $this->vater->id)->where('title', 'like', 'Bitte bestätigen%')->exists());
        $this->assertFalse(Notification::where('user_id', $this->oma->id)->where('title', 'like', 'Bitte bestätigen%')->exists());
        Mail::assertSent(ElternabfrageMail::class, fn ($m) => $m->kind === ElternabfrageMail::KIND_CONFIRMATION_REQUEST && $m->hasTo($this->vater->email));

        // Vater sieht die Bitte, Mutter nur den Hinweis
        $this->actingAs($this->vater)->get(route('elternabfragen.show', $assignment))
            ->assertOk()->assertSee('Ihre Bestätigung wird benötigt')->assertSee(route('elternabfragen.confirm', $assignment), false);
        $this->actingAs($this->mutter)->get(route('elternabfragen.show', $assignment))
            ->assertOk()->assertSee('Bestätigung ausstehend')->assertDontSee(route('elternabfragen.confirm', $assignment), false);
        $this->actingAs($this->vater)->get(route('elternabfragen.index'))->assertOk()->assertSee('bitte prüfen und bestätigen');

        // Mutter kann nicht selbst bestätigen, Oma nicht ohne Sorgerecht
        $this->actingAs($this->mutter)->post(route('elternabfragen.confirm', $assignment), ['confirm' => 1])->assertStatus(409);
        $this->actingAs($this->oma)->post(route('elternabfragen.confirm', $assignment), ['confirm' => 1])->assertStatus(409);

        $this->actingAs($this->vater)->post(route('elternabfragen.confirm', $assignment), ['confirm' => 1])
            ->assertRedirect(route('elternabfragen.show', $assignment));

        $assignment->refresh();
        $this->assertSame(InquiryAssignmentStatus::Submitted, $assignment->status);
        $this->assertTrue($assignment->isComplete());
        $this->assertSame(1, InquiryConfirmation::where('response_id', $assignment->current_response_id)->where('user_id', $this->vater->id)->count());
        $this->assertTrue(Notification::where('user_id', $this->mutter->id)->where('title', 'Abfrage vollständig bestätigt')->exists());

        // Doppelte Bestätigung nicht möglich
        $this->actingAs($this->vater)->post(route('elternabfragen.confirm', $assignment), ['confirm' => 1])->assertStatus(409);

        // Verwaltung sieht, wer bestätigt hat
        $this->actingAs($this->sekretariat)->get(route('elternabfragen.admin.assignments.show', $assignment))
            ->assertOk()->assertSee('Bestätigt von Vater Muster');
    }

    #[Test]
    public function korrektur_setzt_bestaetigungen_zurueck(): void
    {
        $assignment = $this->submitAsMother();
        $this->actingAs($this->vater)->post(route('elternabfragen.confirm', $assignment), ['confirm' => 1]);

        // Mutter korrigiert innerhalb des Korrekturfensters → Vater muss neu bestätigen
        $this->actingAs($this->mutter)->post(route('elternabfragen.submit', $assignment), [
            'answers' => ['fahrrad' => 'no'] + $this->validAnswers(), 'confirm' => 1,
        ])->assertRedirect();

        $assignment->refresh();
        $this->assertSame(InquiryAssignmentStatus::AwaitingConfirmation, $assignment->status);
        $this->assertSame(2, $assignment->currentResponse->version);
    }

    #[Test]
    public function nicht_einverstandener_elternteil_kann_korrigieren(): void
    {
        $inquiry = $this->runningInquiry(['correction_window_days' => 0], ['requires_all_guardians' => true]);
        $assignment = $this->assignmentFor($inquiry);
        $this->actingAs($this->mutter)->post(route('elternabfragen.submit', $assignment), ['answers' => $this->validAnswers(), 'confirm' => 1]);

        // ohne Korrekturfenster darf der Vater korrigieren, solange seine Bestätigung aussteht
        $this->actingAs($this->vater)->post(route('elternabfragen.submit', $assignment), [
            'answers' => ['fahrrad' => 'no'] + $this->validAnswers(), 'confirm' => 1,
        ])->assertRedirect();

        $assignment->refresh();
        $this->assertSame(InquiryAssignmentStatus::AwaitingConfirmation, $assignment->status);
        $this->assertSame($this->vater->id, $assignment->currentResponse->user_id);

        // jetzt muss die Mutter bestätigen
        $this->actingAs($this->mutter)->post(route('elternabfragen.confirm', $assignment), ['confirm' => 1])->assertRedirect();
        $this->assertSame(InquiryAssignmentStatus::Corrected, $assignment->fresh()->status);
    }

    #[Test]
    public function erinnerung_geht_nur_an_fehlende_sorgeberechtigte(): void
    {
        $assignment = $this->submitAsMother();
        $assignment->forceFill(['notified_at' => now()->subDays(5)])->save();
        Notification::query()->delete();

        $result = app(ReminderService::class)->remindManually($assignment->inquiry, null, $this->sekretariat->id);

        $this->assertSame(1, $result['sent']);
        $this->assertTrue(Notification::where('user_id', $this->vater->id)->where('title', 'like', 'Bitte bestätigen%')->exists());
        $this->assertFalse(Notification::where('user_id', $this->mutter->id)->exists());
        $this->assertTrue(InquiryDelivery::where('assignment_id', $assignment->id)->where('kind', InquiryDelivery::KIND_CONFIRMATION_REQUEST)->where('trigger', 'manual')->exists());
    }

    #[Test]
    public function api_liefert_bestaetigungsstatus_und_nimmt_bestaetigung_an(): void
    {
        $assignment = $this->submitAsMother();

        $this->actingAs($this->vater, 'sanctum')->getJson(route('api.v1.elternabfragen.index', ['filter' => 'open']))
            ->assertOk()
            ->assertJsonPath('data.0.can_confirm', true)
            ->assertJsonPath('data.0.status', 'awaiting_confirmation');

        $this->actingAs($this->vater, 'sanctum')->getJson(route('api.v1.elternabfragen.show', $assignment))
            ->assertOk()
            ->assertJsonPath('data.requires_all_guardians', true)
            ->assertJsonFragment(['name' => 'Vater Muster', 'state' => 'pending']);

        $this->actingAs($this->vater, 'sanctum')->postJson(route('api.v1.elternabfragen.confirm', $assignment), ['confirm' => true])
            ->assertOk()
            ->assertJsonPath('data.can_confirm', false)
            ->assertJsonPath('data.status', 'submitted');
    }

    #[Test]
    public function ohne_option_genuegt_eine_antwort(): void
    {
        $inquiry = $this->runningInquiry();
        $assignment = $this->assignmentFor($inquiry);
        $this->actingAs($this->mutter)->post(route('elternabfragen.submit', $assignment), ['answers' => $this->validAnswers(), 'confirm' => 1]);

        $this->assertSame(InquiryAssignmentStatus::Submitted, $assignment->fresh()->status);
        $this->assertFalse(Notification::where('user_id', $this->vater->id)->where('title', 'like', 'Bitte bestätigen%')->exists());
    }
}
