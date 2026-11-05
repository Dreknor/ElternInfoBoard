<?php

namespace Tests\Feature\Elternabfragen;

use App\Enums\InquiryAssignmentStatus;
use App\Enums\InquiryChangeRequestStatus;
use App\Enums\InquiryReviewStatus;
use App\Model\Inquiry;
use App\Model\InquiryAssignment;
use App\Model\InquiryChangeRequest;
use App\Model\Notification;
use App\Services\Elternabfragen\InquiryService;
use App\Services\Elternabfragen\RetentionService;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;

/**
 * Änderungsmeldungen: Eltern melden Änderungen zu einer bereits abgegebenen Antwort
 * (z. B. Umzug während des Schuljahres), das Sekretariat übernimmt oder lehnt ab.
 */
class AenderungsmeldungTest extends ElternabfragenTestCase
{
    /** Laufende Abfrage, Mutter hat geantwortet, Selbstkorrektur ist abgelaufen. */
    private function answered(array $inquiry = [], array $template = []): InquiryAssignment
    {
        $inquiry = $this->runningInquiry($inquiry + ['deadline_at' => now()->addDays(30)], $template);
        $assignment = $this->assignmentFor($inquiry);
        $this->actingAs($this->mutter)
            ->post(route('elternabfragen.submit', $assignment), ['answers' => $this->validAnswers(), 'confirm' => 1])
            ->assertRedirect(route('elternabfragen.show', $assignment));
        $this->travel(8)->days();

        return $assignment->fresh();
    }

    private function report(InquiryAssignment $assignment, array $answers = [], string $message = 'Neue Telefonnummer nach Umzug'): \Illuminate\Testing\TestResponse
    {
        return $this->post(route('elternabfragen.change', $assignment), [
            'answers' => $answers + ['telefon' => '0351 999999'] + $this->validAnswers(),
            'message' => $message,
            'confirm' => 1,
        ]);
    }

    #[Test]
    public function eltern_melden_aenderung_nach_ablauf_der_selbstkorrektur(): void
    {
        $assignment = $this->answered();

        $this->actingAs($this->mutter)->get(route('elternabfragen.show', $assignment))
            ->assertOk()->assertSee('Änderung melden')->assertDontSee('Angaben korrigieren');
        $this->get(route('elternabfragen.show', [$assignment, 'aendern' => 1]))
            ->assertOk()->assertSee(route('elternabfragen.change', $assignment), false)->assertSee('Was hat sich geändert?')->assertSee('0351 123456');

        $this->report($assignment)->assertRedirect(route('elternabfragen.show', $assignment))->assertSessionHas('type', 'success');

        $change = InquiryChangeRequest::firstOrFail();
        $this->assertSame(InquiryChangeRequestStatus::Pending, $change->status);
        $this->assertSame('0351 999999', $change->answers['telefon']);
        $this->assertSame($assignment->current_response_id, $change->base_response_id);
        // Noch nicht wirksam: gültig bleibt die bisherige Antwort
        $this->assertSame('0351 123456', $assignment->fresh()->currentResponse->answers['telefon']);
        $this->assertDatabaseHas('inquiry_events', ['assignment_id' => $assignment->id, 'action' => 'change.requested']);

        // Verantwortliche Person wird benachrichtigt (ohne Inhalte)
        $notice = Notification::where('user_id', $this->sekretariat->id)->where('title', 'Änderung zu einer Elternabfrage gemeldet')->firstOrFail();
        $this->assertStringNotContainsString('999999', $notice->message);

        $this->get(route('elternabfragen.show', $assignment))->assertOk()->assertSee('Änderung gemeldet – wird bearbeitet')->assertSee('Meldung zurückziehen');
    }

    #[Test]
    public function sekretariat_uebernimmt_aenderung_als_neue_geprueft_version(): void
    {
        $assignment = $this->answered();
        $this->actingAs($this->mutter);
        $this->report($assignment);
        $change = InquiryChangeRequest::firstOrFail();

        $this->actingAs($this->sekretariat)->get(route('elternabfragen.admin.change-requests.index'))
            ->assertOk()->assertSee('Mia')->assertSee('Neue Telefonnummer nach Umzug');
        $this->get(route('elternabfragen.admin.change-requests.show', $change))
            ->assertOk()->assertSee('0351 123456')->assertSee('0351 999999')->assertSee('1 Angabe geändert');
        $this->get(route('elternabfragen.admin.assignments.show', $assignment))->assertOk()->assertSee('Änderungsmeldungen der Eltern');
        $this->get(route('elternabfragen.admin.inquiries.show', $assignment->inquiry))->assertOk()->assertSee('1 Änderungsmeldung offen');

        $this->post(route('elternabfragen.admin.change-requests.accept', $change), ['handled_note' => 'übertragen'])
            ->assertRedirect(route('elternabfragen.admin.change-requests.index'));

        $assignment->refresh();
        $current = $assignment->currentResponse;
        $this->assertSame(2, $current->version);
        $this->assertSame('0351 999999', $current->answers['telefon']);
        $this->assertSame($this->mutter->id, $current->user_id);
        $this->assertSame(InquiryReviewStatus::Approved, $current->review_status);
        $this->assertSame($this->sekretariat->id, $current->reviewed_by);
        $this->assertStringContainsString('Neue Telefonnummer nach Umzug', $current->correction_reason);
        $this->assertSame(InquiryAssignmentStatus::Corrected, $assignment->status);

        $change->refresh();
        $this->assertSame(InquiryChangeRequestStatus::Accepted, $change->status);
        $this->assertSame($current->id, $change->response_id);
        $this->assertTrue(Notification::where('user_id', $this->mutter->id)->where('title', 'Änderung übernommen')->exists());
        $this->assertTrue(Notification::where('user_id', $this->vater->id)->where('title', 'Angaben geändert')->exists());

        $this->actingAs($this->mutter)->get(route('elternabfragen.show', $assignment))->assertOk()->assertSee('Änderung übernommen')->assertSee('0351 999999');

        // Zweite Bearbeitung nicht möglich
        $this->actingAs($this->sekretariat)->post(route('elternabfragen.admin.change-requests.accept', $change))->assertStatus(409);
    }

    #[Test]
    public function nach_abschluss_kann_gemeldet_und_mit_begruendung_abgelehnt_werden(): void
    {
        $assignment = $this->answered();
        $this->actingAs($this->sekretariat)->post(route('elternabfragen.admin.inquiries.close', $assignment->inquiry))->assertSessionHasNoErrors();

        $this->actingAs($this->mutter)->get(route('elternabfragen.show', $assignment))->assertOk()->assertSee('Änderung melden');
        $this->report($assignment)->assertSessionHas('type', 'success');
        $change = InquiryChangeRequest::firstOrFail();

        $this->actingAs($this->sekretariat)->post(route('elternabfragen.admin.change-requests.reject', $change), [])
            ->assertSessionHasErrors('handled_note');
        $this->post(route('elternabfragen.admin.change-requests.reject', $change), ['handled_note' => 'Bitte Meldebescheinigung im Sekretariat vorlegen.'])
            ->assertRedirect();

        $this->assertSame(InquiryChangeRequestStatus::Rejected, $change->fresh()->status);
        $this->assertSame(1, $assignment->fresh()->currentResponse->version);
        $this->assertTrue(Notification::where('user_id', $this->mutter->id)->where('title', 'Änderung nicht übernommen')->exists());

        $this->actingAs($this->mutter)->get(route('elternabfragen.show', $assignment))
            ->assertOk()->assertSee('Änderung nicht übernommen')->assertSee('Bitte Meldebescheinigung im Sekretariat vorlegen.');
    }

    #[Test]
    public function unveraenderte_angaben_und_fehlende_nachricht_werden_abgelehnt(): void
    {
        $assignment = $this->answered();
        $this->actingAs($this->mutter);

        $this->post(route('elternabfragen.change', $assignment), ['answers' => $this->validAnswers(), 'message' => 'nichts', 'confirm' => 1])
            ->assertSessionHasErrors('answers');
        $this->report($assignment, [], '')->assertSessionHasErrors('message');
        $this->report($assignment, ['telefon' => 'abc'])->assertSessionHasErrors('answers.telefon');

        $this->assertSame(0, InquiryChangeRequest::count());
    }

    #[Test]
    public function solange_selbstkorrektur_moeglich_ist_keine_meldung(): void
    {
        $inquiry = $this->runningInquiry();
        $assignment = $this->assignmentFor($inquiry);
        $this->actingAs($this->mutter)->post(route('elternabfragen.submit', $assignment), ['answers' => $this->validAnswers(), 'confirm' => 1]);

        $this->get(route('elternabfragen.show', $assignment))->assertOk()->assertSee('Angaben korrigieren')->assertDontSee('Änderung melden');
        $this->report($assignment)->assertForbidden();
    }

    #[Test]
    public function nur_sorgeberechtigte_und_nur_wenn_die_abfrage_es_erlaubt(): void
    {
        $assignment = $this->answered();

        $this->actingAs($this->oma);
        $this->report($assignment)->assertForbidden();

        $this->actingAs($this->sekretariat)->put(route('elternabfragen.admin.inquiries.settings', $assignment->inquiry), [
            'correction_window_days' => 7, 'notify_mail' => 1, 'notify_push' => 1,
        ])->assertSessionHasNoErrors();
        $this->assertFalse($assignment->inquiry->fresh()->allow_change_requests);

        $this->actingAs($this->mutter)->get(route('elternabfragen.show', $assignment))->assertOk()->assertDontSee('Änderung melden');
        $this->report($assignment)->assertForbidden();
    }

    #[Test]
    public function offene_meldung_wird_ersetzt_und_blockiert_den_anderen_elternteil(): void
    {
        $assignment = $this->answered();
        $this->actingAs($this->mutter);
        $this->report($assignment);
        $this->report($assignment, ['telefon' => '0351 888888'], 'Doch andere Nummer');

        $this->assertSame(1, InquiryChangeRequest::pending()->count());
        $this->assertSame('0351 888888', InquiryChangeRequest::pending()->first()->answers['telefon']);
        $this->assertSame(1, InquiryChangeRequest::where('status', InquiryChangeRequestStatus::Withdrawn->value)->count());

        $this->actingAs($this->vater)->get(route('elternabfragen.show', $assignment))
            ->assertOk()->assertSee('bereits eine Änderungsmeldung von Mutter Muster');
        $this->report($assignment)->assertStatus(409);

        $this->actingAs($this->mutter)->delete(route('elternabfragen.change.withdraw', $assignment))->assertRedirect();
        $this->assertSame(0, InquiryChangeRequest::pending()->count());
        $this->actingAs($this->vater);
        $this->report($assignment)->assertSessionHas('type', 'success');
    }

    #[Test]
    public function bei_neuerer_abfrage_wird_auf_diese_verwiesen(): void
    {
        $assignment = $this->answered();
        $this->actingAs($this->sekretariat)->post(route('elternabfragen.admin.inquiries.close', $assignment->inquiry));

        $service = app(InquiryService::class);
        $next = $service->duplicate($assignment->inquiry, $this->sekretariat);
        $next->update(['starts_at' => now()->subMinute(), 'deadline_at' => now()->addDays(14)]);
        $service->submit($next);
        $this->actingAs($this->schulleitung);
        $service->approve($next->fresh(), $this->schulleitung);
        $this->assertTrue($next->fresh()->isRunning());

        $this->actingAs($this->mutter)->get(route('elternabfragen.show', $assignment))
            ->assertOk()->assertSee('inzwischen mit')->assertSee(route('elternabfragen.show', $this->assignmentFor($next->fresh())), false);
        $this->report($assignment)->assertStatus(409);
    }

    #[Test]
    public function gemeinsame_erklaerung_muss_nach_uebernahme_bestaetigt_werden(): void
    {
        $assignment = $this->answered([], ['requires_all_guardians' => true]);
        $this->actingAs($this->vater)->post(route('elternabfragen.confirm', $assignment), ['confirm' => 1])->assertRedirect();
        $this->actingAs($this->sekretariat)->post(route('elternabfragen.admin.inquiries.close', $assignment->inquiry));

        $this->actingAs($this->mutter);
        $this->report($assignment);
        $this->actingAs($this->sekretariat)->post(route('elternabfragen.admin.change-requests.accept', InquiryChangeRequest::firstOrFail()));

        $assignment->refresh();
        $this->assertSame(InquiryAssignmentStatus::AwaitingConfirmation, $assignment->status);
        $this->assertTrue(Notification::where('user_id', $this->vater->id)->where('title', 'Bitte bestätigen: Elternabfrage')->exists());

        $this->actingAs($this->vater)->post(route('elternabfragen.confirm', $assignment), ['confirm' => 1])->assertRedirect();
        $this->assertSame(InquiryAssignmentStatus::Corrected, $assignment->fresh()->status);
    }

    #[Test]
    public function loeschung_entfernt_auch_aenderungsmeldungen(): void
    {
        $assignment = $this->answered([], ['retention_days' => 30]);
        $this->actingAs($this->sekretariat)->post(route('elternabfragen.admin.inquiries.close', $assignment->inquiry));
        $this->actingAs($this->mutter);
        $this->report($assignment);
        $this->assertSame(1, InquiryChangeRequest::count());

        $this->travel(31)->days();
        app(RetentionService::class)->run();

        $this->assertSame(0, InquiryChangeRequest::count());
        $this->assertNotNull(Inquiry::find($assignment->inquiry_id)->purged_at);
    }

    #[Test]
    public function app_api_meldet_und_zieht_zurueck(): void
    {
        $assignment = $this->answered();
        Sanctum::actingAs($this->mutter);

        $this->getJson(route('api.v1.elternabfragen.show', $assignment))
            ->assertOk()->assertJsonPath('data.can_request_change', true)->assertJsonPath('data.change_request', null);

        $this->postJson(route('api.v1.elternabfragen.change', $assignment), [
            'answers' => ['telefon' => '0351 777777'] + $this->validAnswers(),
            'message' => 'Umzug',
            'confirm' => true,
        ])->assertCreated()->assertJsonPath('data.change_pending', true)->assertJsonPath('data.change_request.status', 'pending');

        $this->assertSame('app', InquiryChangeRequest::firstOrFail()->channel);

        $this->deleteJson(route('api.v1.elternabfragen.change.withdraw', $assignment))->assertOk();
        $this->assertSame(0, InquiryChangeRequest::pending()->count());

        Sanctum::actingAs($this->oma);
        $this->postJson(route('api.v1.elternabfragen.change', $assignment), ['answers' => $this->validAnswers(), 'message' => 'x', 'confirm' => true])
            ->assertForbidden();
    }

    #[Test]
    public function verwaltung_ohne_einsichtsrecht_sieht_keine_meldungen(): void
    {
        $assignment = $this->answered();
        $this->actingAs($this->mutter);
        $this->report($assignment);

        $nurVerwaltung = $this->makeParent(['name' => 'Nur Verwaltung']);
        $nurVerwaltung->givePermissionTo('manage elternabfragen');
        $this->actingAs($nurVerwaltung)->get(route('elternabfragen.admin.change-requests.index'))->assertForbidden();
        $this->actingAs($this->mutter)->get(route('elternabfragen.admin.change-requests.show', InquiryChangeRequest::firstOrFail()))->assertForbidden();
    }
}
