<?php

namespace Tests\Feature\Elternabfragen;

use App\Enums\InquiryReceiptKind;
use App\Events\Elternabfragen\ReceiptIssued;
use App\Model\InquiryAssignment;
use App\Model\InquiryChangeRequest;
use App\Model\InquiryReceipt;
use App\Model\InquiryResponse;
use App\Services\Elternabfragen\ReceiptService;
use App\Services\Elternabfragen\RetentionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use LogicException;
use PHPUnit\Framework\Attributes\Test;

/**
 * Erklärungsquittungen (§ 126b BGB): unveränderlicher Datensatz je Erklärung mit Prüfsummen,
 * Hash-Kette je Abfrage und deterministisch erzeugtem PDF (nur dessen SHA-256 wird gespeichert).
 */
class ErklaerungsquittungTest extends ElternabfragenTestCase
{
    private function service(): ReceiptService
    {
        return app(ReceiptService::class);
    }

    private function fullTemplate(array $overrides = []): array
    {
        return $overrides + [
            'parent_info' => 'Bitte beachten Sie die Hausordnung.',
            'responsible_office' => 'Sekretariat der Grundschule',
            'retention_note' => 'Löschung zum Schuljahresende.',
            'fields' => array_merge($this->fields(), [
                ['key' => 'weg', 'type' => 'radio', 'label' => 'Schulweg', 'required' => false, 'help' => 'Wie kommt Ihr Kind meist zur Schule?', 'options' => ['Zu Fuß', 'Bus']],
            ]),
        ];
    }

    private function submit(InquiryAssignment $assignment, array $answers = [], array $extra = []): void
    {
        $this->actingAs($this->mutter)
            ->post(route('elternabfragen.submit', $assignment), ['answers' => $answers + $this->validAnswers(), 'confirm' => 1] + $extra)
            ->assertRedirect(route('elternabfragen.show', $assignment));
    }

    private function answered(array $template = []): InquiryAssignment
    {
        $assignment = $this->assignmentFor($this->runningInquiry(['deadline_at' => now()->addDays(30)], $this->fullTemplate($template)));
        $this->submit($assignment, ['weg' => 'Bus']);

        return $assignment->fresh();
    }

    #[Test]
    public function quittungen_bei_abgabe_und_korrektur_bilden_eine_kette(): void
    {
        Event::fake([ReceiptIssued::class]);
        $assignment = $this->answered();
        $this->submit($assignment, ['fahrrad' => 'no'], ['correction_reason' => 'Doch nicht']);

        $receipts = InquiryReceipt::orderBy('sequence')->get();
        $this->assertCount(2, $receipts);
        [$first, $second] = $receipts;

        $this->assertSame(InquiryReceiptKind::Submission, $first->kind);
        $this->assertSame(InquiryReceiptKind::Correction, $second->kind);
        $this->assertSame([1, 2], [$first->sequence, $second->sequence]);
        $this->assertNull($first->prev_hash);
        $this->assertSame($first->content_hash, $second->prev_hash);
        $this->assertSame('Mutter Muster', $first->declarant_name);
        $this->assertSame('Mia Muster', $first->child_name);
        $this->assertSame($this->mutter->id, $first->user_id);
        $this->assertSame('web', $first->channel);
        $this->assertFalse($first->backfilled);
        $this->assertSame(36, strlen($first->uuid));

        $versions = InquiryResponse::where('assignment_id', $assignment->id)->orderBy('version')->get();
        $this->assertSame($versions[0]->id, $first->response_id);
        $this->assertSame($versions[1]->answer_hash, $second->answer_hash);
        $this->assertSame($versions[1]->form_hash, $second->form_hash);
        $this->assertSame($second->id, $versions[1]->receipt->id);

        // PDF-Prüfsumme nach dem Commit ermittelt, Ereignis für Phase 2 (Mailversand)
        $this->assertNotNull($first->pdf_sha256);
        $this->assertNotNull($second->pdf_sha256);
        Event::assertDispatchedTimes(ReceiptIssued::class, 2);
        Event::assertDispatched(ReceiptIssued::class, fn (ReceiptIssued $event) => $event->receipt->is($second));

        $result = $this->service()->verifyChain($assignment->inquiry);
        $this->assertTrue($result['valid'], implode("\n", $result['errors']));
        $this->assertSame(2, $result['count']);
    }

    #[Test]
    public function bestaetigung_weiterer_sorgeberechtigter_erhaelt_eigene_quittung(): void
    {
        $assignment = $this->answered(['requires_all_guardians' => true]);

        $this->actingAs($this->vater)->post(route('elternabfragen.confirm', $assignment), ['confirm' => 1])
            ->assertRedirect(route('elternabfragen.show', $assignment));

        $receipt = InquiryReceipt::where('kind', InquiryReceiptKind::Confirmation->value)->firstOrFail();
        $submission = InquiryReceipt::where('kind', InquiryReceiptKind::Submission->value)->firstOrFail();
        $this->assertSame('Vater Muster', $receipt->declarant_name);
        $this->assertNotNull($receipt->confirmation_id);
        $this->assertSame($receipt->id, $receipt->confirmation->receipt->id);
        $this->assertSame($submission->response_id, $receipt->response_id);
        $this->assertSame($submission->answer_hash, $receipt->answer_hash);
        $this->assertSame($submission->content_hash, $receipt->prev_hash);
        $this->assertTrue($this->service()->verifyChain($assignment->inquiry)['valid']);

        // Jede Person erhält ihre eigene Quittung als PDF
        $download = $this->actingAs($this->vater)->get(route('elternabfragen.pdf', $assignment))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString($receipt->fileName(), $download->headers->get('content-disposition'));
        $this->assertSame($receipt->pdf_sha256, hash('sha256', $download->getContent()));

        $download = $this->actingAs($this->mutter)->get(route('elternabfragen.pdf', $assignment))->assertOk();
        $this->assertStringContainsString($submission->fileName(), $download->headers->get('content-disposition'));
    }

    #[Test]
    public function uebernommene_aenderungsmeldung_wird_quittiert(): void
    {
        $assignment = $this->answered();
        $this->travel(8)->days();
        $this->actingAs($this->mutter)->post(route('elternabfragen.change', $assignment), [
            'answers' => ['telefon' => '0351 999999'] + $this->validAnswers(),
            'message' => 'Umzug',
            'confirm' => 1,
        ])->assertSessionHas('type', 'success');

        $this->actingAs($this->sekretariat)
            ->post(route('elternabfragen.admin.change-requests.accept', InquiryChangeRequest::firstOrFail()))
            ->assertRedirect();

        $receipt = InquiryReceipt::orderByDesc('sequence')->firstOrFail();
        $this->assertSame(InquiryReceiptKind::ChangeAccepted, $receipt->kind);
        $this->assertSame('Mutter Muster', $receipt->declarant_name);
        $this->assertSame(2, $receipt->response_version);
        $this->assertSame(2, $receipt->sequence);
        $this->assertTrue($this->service()->verifyChain($assignment->inquiry)['valid']);
    }

    #[Test]
    public function manipulationen_werden_von_der_kettenpruefung_erkannt(): void
    {
        $assignment = $this->answered();
        $this->submit($assignment, ['fahrrad' => 'no'], ['correction_reason' => 'Doch nicht']);
        $this->submit($assignment, ['telefon' => '0351 777777'], ['correction_reason' => 'Nummer']);
        $inquiry = $assignment->inquiry;
        $this->assertTrue($this->service()->verifyChain($inquiry)['valid']);

        // Antwort nachträglich per Datenbank geändert
        $response = InquiryResponse::where('assignment_id', $assignment->id)->orderBy('version')->first();
        $answers = $response->answers;
        $answers['foto'] = 'granted';
        DB::table('inquiry_responses')->where('id', $response->id)->update(['answers' => json_encode($answers)]);
        $result = $this->service()->verifyChain($inquiry);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Antworten wurden verändert', implode("\n", $result['errors']));
        DB::table('inquiry_responses')->where('id', $response->id)->update(['answers' => json_encode($response->answers, JSON_UNESCAPED_UNICODE)]);
        $this->assertTrue($this->service()->verifyChain($inquiry)['valid']);

        // Quittung per Datenbank geändert
        $second = InquiryReceipt::where('sequence', 2)->firstOrFail();
        DB::table('inquiry_receipts')->where('id', $second->id)->update(['declarant_name' => 'Jemand anderes']);
        $result = $this->service()->verifyChain($inquiry);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Quittungsdaten wurden verändert', implode("\n", $result['errors']));
        DB::table('inquiry_receipts')->where('id', $second->id)->update(['declarant_name' => $second->declarant_name]);

        // Mittlere Quittung entfernt
        DB::table('inquiry_receipts')->where('id', $second->id)->delete();
        $errors = implode("\n", $this->service()->verifyChain($inquiry)['errors']);
        $this->assertStringContainsString('Lücke in der Kette', $errors);
        $this->assertStringContainsString('Verkettung', $errors);
    }

    #[Test]
    public function pdf_ist_deterministisch_und_entspricht_der_gespeicherten_pruefsumme(): void
    {
        $this->answered();
        $receipt = InquiryReceipt::firstOrFail();

        $first = $this->service()->renderPdf($receipt);
        $this->travel(3)->days();
        $second = $this->service()->renderPdf($receipt->fresh());

        $this->assertStringStartsWith('%PDF-', $first);
        $this->assertSame(hash('sha256', $first), hash('sha256', $second));
        $this->assertSame($receipt->pdf_sha256, hash('sha256', $first));
        $this->assertTrue($this->service()->matchesPdf($second)?->is($receipt));
        $this->assertNull($this->service()->matchesPdf($first.' '));

        // Endgültige Löschung des Kontos: Name bleibt eingefroren, Quittung und PDF unverändert
        $this->mutter->forceDelete();
        $receipt = $receipt->fresh();
        $this->assertNull($receipt->user_id);
        $this->assertSame($receipt->pdf_sha256, hash('sha256', $this->service()->renderPdf($receipt)));
        $this->assertTrue($this->service()->verifyChain($receipt->inquiry)['valid']);
    }

    #[Test]
    public function quittung_enthaelt_den_vollstaendigen_wortlaut_und_die_antworten(): void
    {
        $this->answered();
        $receipt = InquiryReceipt::firstOrFail();
        $html = $this->service()->html($receipt);

        foreach ([
            'Fahrraderlaubnis 2026', 'Bitte ausfüllen.', 'Wir benötigen die Erlaubnis für den Schulweg.',
            'Bitte beachten Sie die Hausordnung.', 'Sekretariat der Grundschule', 'Löschung zum Schuljahresende.',
            'Darf Ihr Kind mit dem Fahrrad kommen?', 'Ich willige ein, dass Fotos veröffentlicht werden.',
            'Wie kommt Ihr Kind meist zur Schule?', 'Zu Fuß · Bus', 'Einwilligung nicht erteilt', '0351 123456',
            'Mutter Muster', 'Mia Muster', 'Abgabe der Erklärung', 'Webseite',
            $receipt->uuid, $receipt->content_hash, $receipt->wording_hash, $receipt->answer_hash,
        ] as $text) {
            $this->assertStringContainsString(e($text), $html, $text);
        }
        $this->assertStringContainsString('<strong class="pre">Ja</strong>', $html);
        $this->assertStringContainsString('<strong class="pre">Bus</strong>', $html);
        $this->assertStringNotContainsString('nachträglich erstellt', $html);

        // Nachträglich erstellte Quittung (Phase 2: Nacherfassung des Altbestands)
        $backfilled = DB::transaction(fn () => $this->service()->issue($receipt->response, InquiryReceiptKind::Submission, null, ['backfilled' => true]));
        $this->assertStringContainsString('nachträglich erstellt', $this->service()->html($backfilled));
        $this->assertSame($receipt->content_hash, $backfilled->prev_hash);
    }

    #[Test]
    public function quittungen_sind_unveraenderlich(): void
    {
        $this->answered();
        $receipt = InquiryReceipt::firstOrFail();

        try {
            $receipt->update(['declarant_name' => 'Jemand anderes']);
            $this->fail('Änderung wurde nicht verhindert.');
        } catch (LogicException) {
        }
        try {
            $receipt->fresh()->update(['pdf_sha256' => str_repeat('0', 64)]);
            $this->fail('Überschreiben der PDF-Prüfsumme wurde nicht verhindert.');
        } catch (LogicException) {
        }
        $this->expectException(LogicException::class);
        $receipt->fresh()->delete();
    }

    #[Test]
    public function loeschung_der_antworten_entfernt_auch_die_quittungen(): void
    {
        $assignment = $this->answered();
        $this->assertSame(1, InquiryReceipt::count());

        app(RetentionService::class)->purge($assignment->inquiry);

        $this->assertSame(0, InquiryReceipt::count());
        $this->assertTrue($this->service()->verifyChain($assignment->inquiry)['valid']);
    }

    #[Test]
    public function app_api_liefert_das_quittungs_pdf(): void
    {
        $assignment = $this->answered();
        $receipt = InquiryReceipt::firstOrFail();

        Sanctum::actingAs($this->mutter);
        $download = $this->get("/api/v1/elternabfragen/{$assignment->id}/pdf")->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString($receipt->fileName(), $download->headers->get('content-disposition'));
        $this->assertSame($receipt->pdf_sha256, hash('sha256', $download->getContent()));
    }
}
