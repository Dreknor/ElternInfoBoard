<?php

namespace Tests\Feature\Elternabfragen;

use App\Enums\InquiryAssignmentStatus;
use App\Enums\InquiryStatus;
use App\Model\InquiryAssignment;
use App\Model\InquiryResponse;
use App\Model\InquiryTemplateVersion;
use App\Model\Notification;
use App\Services\Elternabfragen\RetentionService;
use PHPUnit\Framework\Attributes\Test;

/**
 * Aktenrelevante Vorlagen: PDF je Kind bzw. Klasse für die Schülerakte, Löschschutz
 * für noch nicht gedruckte Antworten und Neustart einzelner Fälle.
 */
class AktenablageUndNeustartTest extends ElternabfragenTestCase
{
    private function submitAsMother(InquiryAssignment $assignment): void
    {
        $this->actingAs($this->mutter)
            ->post(route('elternabfragen.submit', $assignment), ['answers' => $this->validAnswers(), 'confirm' => 1])
            ->assertRedirect(route('elternabfragen.show', $assignment));
    }

    #[Test]
    public function aktenrelevant_wird_in_der_vorlage_gespeichert(): void
    {
        $this->actingAs($this->sekretariat)->post(route('elternabfragen.admin.templates.store'), [
            'title' => 'Fotoeinwilligung',
            'purpose' => 'Veröffentlichung von Fotos auf der Website.',
            'default_deadline_days' => 14,
            'record_relevant' => 1,
            'fields_json' => json_encode($this->fields()),
        ])->assertRedirect();

        $version = InquiryTemplateVersion::firstOrFail();
        $this->assertTrue($version->record_relevant);
        $this->get(route('elternabfragen.admin.templates.show', $version))->assertOk()->assertSee('aktenrelevant');
    }

    #[Test]
    public function verwaltung_druckt_einzelnes_pdf_und_vermerkt_die_ablage(): void
    {
        $inquiry = $this->runningInquiry([], ['record_relevant' => true]);
        $assignment = $this->assignmentFor($inquiry);
        $this->submitAsMother($assignment);

        $this->actingAs($this->sekretariat)->get(route('elternabfragen.admin.assignments.show', $assignment))
            ->assertOk()->assertSee('Noch nicht für die Schülerakte gedruckt')->assertSee(route('elternabfragen.admin.records.assignment', $assignment), false);
        $this->get(route('elternabfragen.admin.inquiries.show', $inquiry))->assertOk()->assertSee('noch nicht für die Schülerakte gedruckt');

        $this->get(route('elternabfragen.admin.records.assignment', $assignment))
            ->assertOk()->assertHeader('content-type', 'application/pdf');

        $assignment->refresh();
        $this->assertTrue($assignment->isFiled());
        $this->assertSame($this->sekretariat->id, $assignment->filed_by);
        $this->assertDatabaseHas('inquiry_events', ['assignment_id' => $assignment->id, 'action' => 'assignment.filed']);

        $this->get(route('elternabfragen.admin.assignments.show', $assignment))->assertOk()->assertSee('Für die Schülerakte gedruckt am');

        // Eltern und Konten ohne Einsichtsrecht erhalten kein Akten-PDF
        $this->actingAs($this->mutter)->get(route('elternabfragen.admin.records.assignment', $assignment))->assertForbidden();
    }

    #[Test]
    public function sammel_pdf_je_klasse_und_nur_noch_nicht_gedruckte(): void
    {
        $inquiry = $this->runningInquiry([], ['record_relevant' => true]);
        $mia = $this->assignmentFor($inquiry);
        $this->submitAsMother($mia);

        $this->actingAs($this->sekretariat)
            ->get(route('elternabfragen.admin.records.inquiry', [$inquiry, 'klasse' => $this->klasse->id, 'nur_neue' => 1]))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertTrue($mia->fresh()->isFiled());

        // Alles gedruckt: nichts mehr zu tun
        $this->from(route('elternabfragen.admin.inquiries.show', $inquiry))
            ->get(route('elternabfragen.admin.records.inquiry', [$inquiry, 'nur_neue' => 1]))
            ->assertRedirect(route('elternabfragen.admin.inquiries.show', $inquiry))
            ->assertSessionHas('type', 'warning');

        // Eine Korrektur muss erneut gedruckt werden
        $this->actingAs($this->mutter)->post(route('elternabfragen.submit', $mia), [
            'answers' => ['telefon' => '0351 999999'] + $this->validAnswers(), 'confirm' => 1, 'correction_reason' => 'Neue Nummer',
        ])->assertRedirect();
        $this->assertFalse($mia->fresh()->isFiled());
    }

    #[Test]
    public function loeschung_wird_zurueckgehalten_bis_alles_gedruckt_ist(): void
    {
        $inquiry = $this->runningInquiry([], ['record_relevant' => true, 'retention_days' => 30]);
        $assignment = $this->assignmentFor($inquiry);
        $this->submitAsMother($assignment);
        $this->actingAs($this->sekretariat)->post(route('elternabfragen.admin.inquiries.close', $inquiry))->assertSessionHasNoErrors();

        $this->travel(31)->days();
        $retention = app(RetentionService::class);
        $this->assertSame(0, $retention->run());
        $this->assertCount(1, $retention->held());
        $this->assertSame(1, $retention->held()[0]['unfiled']);
        $this->artisan('elternabfragen:process', ['--dry-run' => true])->expectsOutputToContain('Zurückgehalten')->assertSuccessful();

        $this->actingAs($this->sekretariat)->get(route('elternabfragen.admin.records.assignment', $assignment))->assertOk();

        $this->assertSame(1, $retention->run());
        $this->assertNotNull($inquiry->fresh()->purged_at);
    }

    #[Test]
    public function nicht_aktenrelevante_abfragen_werden_wie_bisher_geloescht(): void
    {
        $inquiry = $this->runningInquiry([], ['retention_days' => 30]);
        $this->submitAsMother($this->assignmentFor($inquiry));
        $this->actingAs($this->sekretariat)->post(route('elternabfragen.admin.inquiries.close', $inquiry));

        $this->travel(31)->days();
        $this->assertSame(1, app(RetentionService::class)->run());
    }

    #[Test]
    public function abfrage_wird_fuer_eine_familie_neu_gestartet(): void
    {
        $inquiry = $this->runningInquiry();
        $assignment = $this->assignmentFor($inquiry);
        $this->submitAsMother($assignment);
        $first = $assignment->fresh()->current_response_id;
        $notifications = Notification::where('user_id', $this->mutter->id)->where('title', 'Neue Elternabfrage')->count();

        $this->actingAs($this->sekretariat)->post(route('elternabfragen.admin.assignments.restart', $assignment), [
            'restart_until' => now()->addDays(10)->format('Y-m-d H:i'),
            'reason' => 'Sorgerecht geändert',
        ])->assertSessionHasNoErrors()->assertSessionHas('type', 'success');

        $assignment->refresh();
        $this->assertSame(InquiryAssignmentStatus::Open, $assignment->status);
        $this->assertNull($assignment->current_response_id);
        $this->assertNotNull($assignment->reopened_at);
        $this->assertFalse(InquiryResponse::find($first)->is_current);
        $this->assertSame($notifications + 1, Notification::where('user_id', $this->mutter->id)->where('title', 'Neue Elternabfrage')->count());
        $this->assertDatabaseHas('inquiry_events', ['assignment_id' => $assignment->id, 'action' => 'assignment.restarted', 'note' => 'Sorgerecht geändert']);

        // Die letzte Antwort ist vorbefüllt; die neue Abgabe zählt als Eingang, nicht als Korrektur
        $this->actingAs($this->mutter)->get(route('elternabfragen.show', $assignment))->assertOk()->assertSee('0351 123456');
        $this->submitAsMother($assignment);
        $assignment->refresh();
        $this->assertSame(InquiryAssignmentStatus::Submitted, $assignment->status);
        $this->assertSame(2, $assignment->currentResponse->version);
    }

    #[Test]
    public function neustart_nach_abschluss_oeffnet_nur_diese_familie(): void
    {
        $inquiry = $this->runningInquiry([], ['retention_days' => 30]);
        $assignment = $this->assignmentFor($inquiry);
        $this->actingAs($this->sekretariat)->post(route('elternabfragen.admin.inquiries.close', $inquiry));
        $this->assertSame(InquiryStatus::Closed, $inquiry->fresh()->status);

        $this->actingAs($this->mutter)->post(route('elternabfragen.submit', $assignment), ['answers' => $this->validAnswers(), 'confirm' => 1])
            ->assertForbidden();

        $until = now()->addDays(10);
        $this->actingAs($this->sekretariat)->post(route('elternabfragen.admin.assignments.restart', $assignment), [
            'restart_until' => $until->format('Y-m-d H:i'),
            'reason' => 'Nachzügler',
        ])->assertSessionHasNoErrors();

        $assignment->refresh();
        $this->assertTrue($assignment->isReopened());
        $this->actingAs($this->mutter)->get(route('elternabfragen.index'))->assertOk()->assertSee($inquiry->title);
        $this->submitAsMother($assignment);
        $this->assertSame(InquiryAssignmentStatus::Submitted, $assignment->fresh()->status);
        $this->assertSame(InquiryStatus::Closed, $inquiry->fresh()->status);

        // Die Aufbewahrungsfrist beginnt erst mit dem Ende der individuellen Frist
        $this->travel(35)->days();
        $this->assertSame(0, app(RetentionService::class)->run());
        $this->travel(10)->days();
        $this->assertSame(1, app(RetentionService::class)->run());
    }

    #[Test]
    public function neustart_braucht_eine_kuenftige_frist_und_das_recht_zur_verwaltung(): void
    {
        $inquiry = $this->runningInquiry();
        $assignment = $this->assignmentFor($inquiry);

        $this->actingAs($this->sekretariat)->post(route('elternabfragen.admin.assignments.restart', $assignment), [
            'restart_until' => now()->subDay()->format('Y-m-d H:i'), 'reason' => 'Test',
        ])->assertSessionHasErrors('restart_until');

        $this->actingAs($this->mutter)->post(route('elternabfragen.admin.assignments.restart', $assignment), [
            'restart_until' => now()->addDay()->format('Y-m-d H:i'), 'reason' => 'Test',
        ])->assertForbidden();
    }
}
