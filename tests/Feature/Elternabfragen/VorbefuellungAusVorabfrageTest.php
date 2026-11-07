<?php

namespace Tests\Feature\Elternabfragen;

use App\Model\Inquiry;
use App\Model\InquiryAssignment;
use App\Services\Elternabfragen\InquiryService;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;

/**
 * Wiederkehrende Abfrage (neues Schuljahr): Angaben des Vorjahres werden vorbefüllt,
 * das Sekretariat sieht, was sich geändert hat.
 */
class VorbefuellungAusVorabfrageTest extends ElternabfragenTestCase
{
    /** Vorjahr beantwortet, Folgeabfrage per Kopie angelegt und gestartet. */
    private function folgeabfrage(): array
    {
        $vorjahr = $this->runningInquiry();
        $this->actingAs($this->mutter)
            ->post(route('elternabfragen.submit', $this->assignmentFor($vorjahr)), ['answers' => $this->validAnswers(), 'confirm' => 1])
            ->assertRedirect();

        $service = app(InquiryService::class);
        $this->actingAs($this->sekretariat);
        $neu = $service->duplicate($vorjahr, $this->sekretariat);
        $neu->update(['title' => 'Fahrraderlaubnis 2027', 'starts_at' => now()->subMinute()]);
        $service->submit($neu);
        $this->actingAs($this->schulleitung);
        $service->approve($neu, $this->schulleitung);

        return [$vorjahr, $neu->fresh()];
    }

    #[Test]
    public function kopie_uebernimmt_angaben_ohne_einwilligungen(): void
    {
        [$vorjahr, $neu] = $this->folgeabfrage();
        $this->assertSame($vorjahr->id, $neu->previous_inquiry_id);
        $assignment = $this->assignmentFor($neu);

        $this->actingAs($this->vater)->get(route('elternabfragen.show', $assignment))
            ->assertOk()
            ->assertSee('Angaben aus „Fahrraderlaubnis 2026“ übernommen')
            ->assertSee('0351 123456');

        $prefill = app(\App\Services\Elternabfragen\ResponseService::class)->prefill($assignment, $this->vater);
        $this->assertSame('yes', $prefill['fahrrad']);
        $this->assertArrayNotHasKey('foto', $prefill);

        Sanctum::actingAs($this->vater);
        $this->getJson("/api/v1/elternabfragen/{$assignment->id}")->assertOk()
            ->assertJsonPath('data.prefill.from_inquiry.id', $vorjahr->id)
            ->assertJsonPath('data.prefill.answers.telefon', '0351 123456');
    }

    #[Test]
    public function aenderungen_werden_fuer_das_sekretariat_aufgelistet(): void
    {
        [, $neu] = $this->folgeabfrage();
        $assignment = $this->assignmentFor($neu);

        $this->actingAs($this->vater)->post(route('elternabfragen.submit', $assignment), [
            'answers' => ['fahrrad' => 'yes', 'foto' => 'granted', 'telefon' => '0351 999999', 'hinweis' => 'Keine'],
            'confirm' => 1,
        ])->assertRedirect();

        $response = $assignment->fresh()->currentResponse;
        $this->assertSame(['foto', 'telefon'], collect($response->baselineChanges())->keys()->sort()->values()->all());
        $this->assertSame('0351 123456', $response->baselineChanges()['telefon']['old']);

        $this->actingAs($this->sekretariat)->get(route('elternabfragen.admin.inquiries.show', $neu))
            ->assertOk()->assertSee('Vorbefüllt aus')->assertSee('Änderungen anzeigen');
        $this->get(route('elternabfragen.admin.inquiries.changes', $neu))
            ->assertOk()->assertSee('Mia Muster')->assertSee('0351 123456')->assertSee('0351 999999')->assertSee('Einwilligung erteilt');
        $this->get(route('elternabfragen.admin.responses.show', $response))
            ->assertOk()->assertSee('Geändert gegenüber der Vorabfrage');

        $this->actingAs($this->vater)->get(route('elternabfragen.admin.inquiries.changes', $neu))->assertForbidden();
    }

    #[Test]
    public function unveraenderte_angaben_werden_als_solche_gezaehlt(): void
    {
        [, $neu] = $this->folgeabfrage();

        $this->actingAs($this->mutter)
            ->post(route('elternabfragen.submit', $this->assignmentFor($neu)), ['answers' => $this->validAnswers(), 'confirm' => 1])
            ->assertRedirect();

        $response = $this->assignmentFor($neu)->currentResponse;
        $this->assertTrue($response->hasBaseline());
        $this->assertSame([], $response->baselineChanges());

        $this->actingAs($this->sekretariat)->get(route('elternabfragen.admin.inquiries.changes', [$neu, 'anzeigen' => 'alle']))
            ->assertOk()->assertSee('Angaben unverändert übernommen');
    }

    #[Test]
    public function ohne_vorabfrage_keine_vorbefuellung(): void
    {
        $inquiry = $this->runningInquiry();
        $assignment = $this->assignmentFor($inquiry);

        $this->actingAs($this->mutter)->get(route('elternabfragen.show', $assignment))
            ->assertOk()->assertDontSee('übernommen');
        $this->actingAs($this->mutter)
            ->post(route('elternabfragen.submit', $assignment), ['answers' => $this->validAnswers(), 'confirm' => 1]);

        $this->assertFalse($assignment->fresh()->currentResponse->hasBaseline());
        $this->actingAs($this->sekretariat)->get(route('elternabfragen.admin.inquiries.changes', $inquiry))->assertNotFound();
    }

    #[Test]
    public function vorabfrage_muss_zur_selben_vorlage_gehoeren(): void
    {
        $vorjahr = $this->runningInquiry();
        $andereVorlage = $this->approvedVersion(['title' => 'Ausflug']);

        $this->expectException(ValidationException::class);
        app(InquiryService::class)->create([
            'template_version_id' => $andereVorlage->id,
            'previous_inquiry_id' => $vorjahr->id,
            'title' => 'Ausflug 2027',
            'starts_at' => now()->addDay(),
            'deadline_at' => now()->addDays(10),
            'target_type' => Inquiry::TARGET_ALL,
            'correction_window_days' => 7,
        ], $this->sekretariat);
    }

    #[Test]
    public function vorabfrage_kann_bei_laufender_abfrage_nachtraeglich_gesetzt_werden(): void
    {
        $vorjahr = $this->runningInquiry();
        $this->actingAs($this->mutter)
            ->post(route('elternabfragen.submit', $this->assignmentFor($vorjahr)), ['answers' => $this->validAnswers(), 'confirm' => 1]);

        // Neue Abfrage derselben Vorlage, ohne Verknüpfung angelegt und bereits beantwortet
        $service = app(InquiryService::class);
        $this->actingAs($this->sekretariat);
        $neu = $service->create([
            'template_version_id' => $vorjahr->template_version_id,
            'title' => 'Fahrraderlaubnis 2027',
            'starts_at' => now()->subMinute(),
            'deadline_at' => now()->addDays(14),
            'target_type' => Inquiry::TARGET_GROUPS,
            'target_group_ids' => [$this->klasse->id],
            'notify_mail' => true,
            'correction_window_days' => 7,
        ], $this->sekretariat);
        $service->submit($neu);
        $this->actingAs($this->schulleitung);
        $service->approve($neu, $this->schulleitung);
        $this->actingAs($this->vater)->post(route('elternabfragen.submit', $this->assignmentFor($neu)), [
            'answers' => ['fahrrad' => 'no', 'foto' => 'denied', 'telefon' => '0351 123456', 'hinweis' => 'Keine'],
            'confirm' => 1,
        ]);
        $this->assertFalse($this->assignmentFor($neu)->currentResponse->hasBaseline());

        $this->actingAs($this->sekretariat)->get(route('elternabfragen.admin.inquiries.show', $neu))
            ->assertOk()->assertSee('Vorabfrage (z. B. Vorjahr)')->assertSee('Fahrraderlaubnis 2026');
        $this->put(route('elternabfragen.admin.inquiries.previous', $neu), ['previous_inquiry_id' => $vorjahr->id])
            ->assertRedirect()->assertSessionHas('Meldung', 'Vorabfrage gesetzt. 1 bereits abgegebene Antwort wurde mit der Vorabfrage verglichen.');

        $response = $this->assignmentFor($neu)->currentResponse;
        $this->assertSame(['fahrrad'], array_keys($response->baselineChanges()));
        $this->assertSame('0351 123456', $response->answers['telefon']);
        $this->get(route('elternabfragen.admin.inquiries.changes', $neu))->assertOk()->assertSee('Darf Ihr Kind mit dem Fahrrad kommen?');

        // Entfernen setzt den Vergleich zurück
        $this->put(route('elternabfragen.admin.inquiries.previous', $neu), ['previous_inquiry_id' => ''])->assertRedirect();
        $this->assertNull($neu->fresh()->previous_inquiry_id);
        $this->assertFalse($this->assignmentFor($neu)->currentResponse->hasBaseline());

        // Nur mit Verwaltungsrecht
        $this->actingAs($this->vater)->put(route('elternabfragen.admin.inquiries.previous', $neu), ['previous_inquiry_id' => $vorjahr->id])->assertForbidden();
    }
}
