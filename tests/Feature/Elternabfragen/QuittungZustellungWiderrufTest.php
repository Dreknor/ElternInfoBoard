<?php

namespace Tests\Feature\Elternabfragen;

use App\Enums\InquiryAssignmentStatus;
use App\Enums\InquiryReceiptKind;
use App\Mail\ElternabfrageMail;
use App\Model\InquiryAssignment;
use App\Model\InquiryChangeRequest;
use App\Model\InquiryDelivery;
use App\Model\InquiryReceipt;
use App\Model\InquiryResponse;
use App\Model\Notification;
use App\Services\Elternabfragen\InquiryService;
use App\Services\Elternabfragen\ReceiptService;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;

/**
 * Phase 2A der Erklärungsquittungen: Zustellung per E-Mail (eine Mail je Erklärung),
 * Widerruf von Einwilligungen (Art. 7 Abs. 3 DSGVO) in Web und App-API, Quittungsliste und
 * Download sowie unveränderte Darstellung älterer Quittungen (Layout 1).
 */
class QuittungZustellungWiderrufTest extends ElternabfragenTestCase
{
    private function service(): ReceiptService
    {
        return app(ReceiptService::class);
    }

    private function fieldsWithTwoConsents(): array
    {
        return array_merge($this->fields(), [
            ['key' => 'video', 'type' => 'consent', 'label' => 'Videos', 'required' => false, 'consent_text' => 'Ich willige in Videoaufnahmen ein.'],
        ]);
    }

    /** Laufende Abfrage, Mutter hat mit erteilten Einwilligungen geantwortet. */
    private function answered(array $inquiry = [], array $template = []): InquiryAssignment
    {
        $assignment = $this->assignmentFor($this->runningInquiry($inquiry + ['deadline_at' => now()->addDays(30)], $template + ['fields' => $this->fieldsWithTwoConsents()]));
        $this->actingAs($this->mutter)
            ->post(route('elternabfragen.submit', $assignment), ['answers' => ['foto' => 'granted', 'video' => 'granted'] + $this->validAnswers(), 'confirm' => 1])
            ->assertRedirect(route('elternabfragen.show', $assignment));

        return $assignment->fresh();
    }

    /** @return \Illuminate\Support\Collection<int, ElternabfrageMail> */
    private function mailsTo(string $email, ?string $kind = null)
    {
        return Mail::sent(ElternabfrageMail::class, fn (ElternabfrageMail $m) => $m->hasTo($email) && ($kind === null || $m->kind === $kind));
    }

    private function attachmentBytes(ElternabfrageMail $mail): ?string
    {
        $attachments = $mail->attachments();
        if ($attachments === []) {
            return null;
        }
        /** @var Attachment $attachment */
        $attachment = $attachments[0];

        return $attachment->attachWith(fn () => null, fn ($data) => $data());
    }

    // ── Zustellung ────────────────────────────────────────────────────────────

    #[Test]
    public function abgabe_erzeugt_genau_eine_mail_mit_quittung_im_anhang(): void
    {
        $assignment = $this->answered();
        $receipt = InquiryReceipt::where('assignment_id', $assignment->id)->sole();

        $mails = $this->mailsTo($this->mutter->email, ElternabfrageMail::KIND_CONFIRMATION);
        $this->assertCount(1, $mails, 'Eingangsbestätigung und Quittung kommen in einer Mail');
        $mail = $mails->first();
        $this->assertSame($receipt->uuid, $mail->receiptNumber);
        $this->assertSame($receipt->fileName(), $mail->attachments()[0]->as);

        $bytes = $this->attachmentBytes($mail);
        $this->assertSame($this->service()->pdf($receipt->fresh()), $bytes);
        $this->assertSame($receipt->fresh()->pdf_sha256, hash('sha256', $bytes));
        $this->assertSame('Quittung_'.$receipt->uuid.'.pdf', $receipt->fileName());

        // Mailtext ohne Antwortinhalte, mit Hinweis auf den Anhang
        $html = $mail->render();
        $this->assertStringContainsString('im Anhang dieser E-Mail', $html);
        $this->assertStringNotContainsString('0351 123456', $html);

        // Protokoll: genau eine Eingangsbestätigung per Mail
        $this->assertSame(1, InquiryDelivery::where('user_id', $this->mutter->id)->where('kind', InquiryDelivery::KIND_CONFIRMATION)
            ->where('channel', InquiryDelivery::CHANNEL_MAIL)->where('success', true)->count());

        // Korrektur: wieder genau eine Mail (mit neuer Quittung)
        $this->post(route('elternabfragen.submit', $assignment), ['answers' => ['foto' => 'granted', 'fahrrad' => 'no'] + $this->validAnswers(), 'confirm' => 1, 'correction_reason' => 'x']);
        $this->assertCount(2, $this->mailsTo($this->mutter->email, ElternabfrageMail::KIND_CONFIRMATION));
        $correction = InquiryReceipt::where('kind', InquiryReceiptKind::Correction->value)->sole();
        $this->assertSame($correction->uuid, $this->mailsTo($this->mutter->email, ElternabfrageMail::KIND_CONFIRMATION)->last()->receiptNumber);
    }

    #[Test]
    public function sensible_abfrage_mail_ohne_anhang_nur_hinweis(): void
    {
        $assignment = $this->answered([], ['is_sensitive' => true]);
        $receipt = InquiryReceipt::where('assignment_id', $assignment->id)->sole();

        $mails = $this->mailsTo($this->mutter->email, ElternabfrageMail::KIND_CONFIRMATION);
        $this->assertCount(1, $mails);
        $mail = $mails->first();
        $this->assertNull($mail->receiptPdf);
        $this->assertSame([], $mail->attachments());
        $this->assertSame($receipt->uuid, $mail->receiptNumber);
        $html = $mail->render();
        $this->assertStringContainsString('zum Download bereit', $html);
        $this->assertStringNotContainsString('0351 123456', $html);

        $this->get(route('elternabfragen.show', $assignment))->assertOk()->assertSee('Ihre Quittung steht hier zum Download bereit.');
    }

    #[Test]
    public function nachtraeglich_erstellte_quittung_wird_nie_verschickt(): void
    {
        $assignment = $this->answered();
        $before = Mail::sent(ElternabfrageMail::class)->count();

        $response = InquiryResponse::findOrFail($assignment->current_response_id);
        $backfilled = DB::transaction(fn () => $this->service()->issue($response, InquiryReceiptKind::Submission, null, ['backfilled' => true]));

        $this->assertTrue($backfilled->backfilled);
        $this->assertSame($before, Mail::sent(ElternabfrageMail::class)->count());
        $this->assertSame('download', app(\App\Services\Elternabfragen\DeliveryService::class)->receiptDelivery($backfilled));
    }

    #[Test]
    public function bestaetigung_und_uebernommene_aenderung_je_eine_mail_mit_quittung(): void
    {
        $assignment = $this->answered([], ['requires_all_guardians' => true]);
        $this->assertCount(1, $this->mailsTo($this->vater->email, ElternabfrageMail::KIND_CONFIRMATION_REQUEST));

        $this->actingAs($this->vater)->post(route('elternabfragen.confirm', $assignment), ['confirm' => 1])
            ->assertSessionHas('receipt_delivery', 'mail');
        $confirmation = InquiryReceipt::where('kind', InquiryReceiptKind::Confirmation->value)->sole();
        $mails = $this->mailsTo($this->vater->email, ElternabfrageMail::KIND_CONFIRMED);
        $this->assertCount(1, $mails);
        $this->assertSame($confirmation->fresh()->pdf_sha256, hash('sha256', $this->attachmentBytes($mails->first())));

        // Änderungsmeldung nach Ablauf der Selbstkorrektur: Rückmeldung „übernommen“ trägt die Quittung
        $this->travel(8)->days();
        $this->actingAs($this->mutter)->post(route('elternabfragen.change', $assignment), [
            'answers' => ['foto' => 'granted', 'video' => 'granted', 'telefon' => '0351 999999'] + $this->validAnswers(),
            'message' => 'Umzug', 'confirm' => 1,
        ])->assertSessionHas('type', 'success');
        $this->actingAs($this->sekretariat)->post(route('elternabfragen.admin.change-requests.accept', InquiryChangeRequest::firstOrFail()));

        $accepted = $this->mailsTo($this->mutter->email, ElternabfrageMail::KIND_CHANGE_ACCEPTED);
        $this->assertCount(1, $accepted);
        $receipt = InquiryReceipt::where('kind', InquiryReceiptKind::ChangeAccepted->value)->sole();
        $this->assertSame($receipt->uuid, $accepted->first()->receiptNumber);
        $this->assertNotNull($this->attachmentBytes($accepted->first()));
    }

    // ── Widerruf ──────────────────────────────────────────────────────────────

    #[Test]
    public function widerruf_ueber_die_elternansicht(): void
    {
        $assignment = $this->answered();

        $this->get(route('elternabfragen.show', $assignment))->assertOk()
            ->assertSee('Quittungen')->assertSee('Abgabe der Erklärung')
            ->assertSee('Einwilligung widerrufen')->assertSee(route('elternabfragen.withdraw', $assignment), false)
            ->assertSee('Eine Quittung wurde Ihnen per E-Mail geschickt.');

        $this->post(route('elternabfragen.withdraw', $assignment), ['fields' => ['foto'], 'reason' => 'Umzug ins Ausland', 'confirm' => 1])
            ->assertRedirect(route('elternabfragen.show', $assignment))
            ->assertSessionHas('type', 'success')
            ->assertSessionHas('receipt_delivery', 'mail');

        $assignment->refresh();
        $this->assertSame('Einwilligung widerrufen', $assignment->displayLabel());
        $this->get(route('elternabfragen.show', $assignment))->assertSee('Einwilligung widerrufen');
        $current = $assignment->currentResponse;
        $this->assertSame(2, $current->version);
        $this->assertSame('denied', $current->answers['foto']);
        $this->assertSame('granted', $current->answers['video']);
        $this->assertSame('web', $current->channel);
        $this->assertSame('Widerruf der Einwilligung: Umzug ins Ausland', $current->correction_reason);

        // Genau eine Quittung zur neuen Version – Art „Widerruf“, keine zusätzliche Korrektur
        $receipts = InquiryReceipt::where('response_id', $current->id)->get();
        $this->assertCount(1, $receipts);
        $this->assertSame(InquiryReceiptKind::Withdrawal, $receipts[0]->kind);
        $this->assertSame(2, $receipts[0]->layout_version);
        $this->assertSame(0, InquiryReceipt::where('kind', InquiryReceiptKind::Correction->value)->count());
        $this->assertTrue($this->service()->verifyChain($assignment->inquiry)['valid']);

        // PDF mit eigenem Absatz, Mail mit Anhang
        $html = $this->service()->html($receipts[0]);
        $this->assertStringContainsString('Widerruf der Einwilligung (Art. 7 Abs. 3 DSGVO)', $html);
        $this->assertStringContainsString('„Fotos“', $html);
        $this->assertStringNotContainsString('„Videos“', $html);
        $mails = $this->mailsTo($this->mutter->email, ElternabfrageMail::KIND_WITHDRAWAL);
        $this->assertCount(1, $mails);
        $this->assertSame($receipts[0]->fresh()->pdf_sha256, hash('sha256', $this->attachmentBytes($mails->first())));

        // Protokoll und Hinweis an die verantwortliche Person (ohne Antwortinhalte)
        $this->assertDatabaseHas('inquiry_events', ['assignment_id' => $assignment->id, 'action' => 'response.withdrawn']);
        $notice = Notification::where('user_id', $this->sekretariat->id)->where('title', 'Einwilligung widerrufen')->firstOrFail();
        $this->assertStringNotContainsString('Fotos', $notice->message);
        $this->assertTrue(InquiryDelivery::where('kind', InquiryDelivery::KIND_WITHDRAWAL_NOTICE)->where('user_id', $this->sekretariat->id)->exists());

        // Liste zeigt beide Quittungen, Widerruf nur noch für „Videos“
        $page = $this->get(route('elternabfragen.show', $assignment))->assertOk()->assertSee('Widerruf der Erklärung')->assertSee('Videos');
        $this->assertStringContainsString(route('elternabfragen.receipts.pdf', [$assignment, $receipts[0]->uuid]), $page->getContent());

        // Ohne Auswahl → Fehlermeldung
        $this->post(route('elternabfragen.withdraw', $assignment), ['confirm' => 1])->assertSessionHasErrors('fields');
    }

    #[Test]
    public function widerruf_auch_nach_fristende_und_abschluss(): void
    {
        $assignment = $this->answered();
        $this->travel(40)->days();
        app(InquiryService::class)->close($assignment->inquiry->fresh());
        $assignment = $assignment->fresh();
        $this->assertFalse($assignment->acceptsAnswers());

        Sanctum::actingAs($this->mutter);
        $this->getJson(route('api.v1.elternabfragen.show', $assignment))->assertOk()
            ->assertJsonPath('data.can_correct', false)
            ->assertJsonPath('data.withdrawal.possible', true);

        $this->postJson(route('api.v1.elternabfragen.withdraw', $assignment), ['confirm' => 'accepted'])
            ->assertOk()
            ->assertJsonPath('receipt.kind', 'withdrawal')
            ->assertJsonPath('data.withdrawal.possible', false)
            ->assertJsonPath('data.withdrawal.fields', []);

        $current = $assignment->fresh()->currentResponse;
        $this->assertSame(['denied', 'denied'], [$current->answers['foto'], $current->answers['video']]);
        $this->assertSame('app', $current->channel);
        $this->assertSame('Widerruf der Einwilligung', $current->correction_reason);
        $this->assertSame(1, InquiryReceipt::where('kind', InquiryReceiptKind::Withdrawal->value)->count());
    }

    #[Test]
    public function widerruf_ueber_die_api_nach_vertrag(): void
    {
        $assignment = $this->answered();
        Sanctum::actingAs($this->mutter);

        $show = $this->getJson(route('api.v1.elternabfragen.show', $assignment))->assertOk()
            ->assertJsonPath('data.withdrawal.possible', true)
            ->assertJsonPath('data.withdrawal.fields', [['key' => 'foto', 'label' => 'Fotos'], ['key' => 'video', 'label' => 'Videos']])
            ->assertJsonCount(1, 'data.receipts');
        $first = InquiryReceipt::sole();
        $show->assertJsonPath('data.receipts.0', [
            'uuid' => $first->uuid,
            'kind' => 'submission',
            'kind_label' => InquiryReceiptKind::Submission->label(),
            'declared_at' => $first->declared_at->toIso8601String(),
            'declarant_name' => 'Mutter Muster',
            'child_name' => 'Mia Muster',
            'backfilled' => false,
            'content_hash' => $first->content_hash,
            'pdf_url' => route('api.v1.elternabfragen.receipts.pdf', [$assignment, $first->uuid]),
        ]);

        // Nicht widerrufbar: kein Einwilligungsfeld bzw. unbekannt → 422, nichts gespeichert
        $this->postJson(route('api.v1.elternabfragen.withdraw', $assignment), ['fields' => ['fahrrad'], 'confirm' => 'accepted'])
            ->assertStatus(422)->assertJsonValidationErrors('fields');
        $this->postJson(route('api.v1.elternabfragen.withdraw', $assignment), ['fields' => ['gibtsnicht'], 'confirm' => 'accepted'])
            ->assertStatus(422);
        $this->postJson(route('api.v1.elternabfragen.withdraw', $assignment), ['fields' => ['foto']])
            ->assertStatus(422)->assertJsonValidationErrors('confirm');
        $this->postJson(route('api.v1.elternabfragen.withdraw', $assignment), ['fields' => ['foto'], 'confirm' => 'accepted', 'reason' => str_repeat('x', 501)])
            ->assertStatus(422)->assertJsonValidationErrors('reason');
        $this->assertSame(1, InquiryResponse::count());

        $result = $this->postJson(route('api.v1.elternabfragen.withdraw', $assignment), ['fields' => ['video'], 'confirm' => 'accepted', 'reason' => 'Bitte keine Videos'])
            ->assertOk()
            ->assertJsonPath('receipt_delivery', 'mail')
            ->assertJsonPath('receipt.kind', 'withdrawal')
            ->assertJsonPath('receipt.kind_label', 'Widerruf der Erklärung')
            ->assertJsonPath('data.id', $assignment->id)
            ->assertJsonPath('data.response.version', 2)
            ->assertJsonPath('data.response.answers.video', 'denied')
            ->assertJsonPath('data.withdrawal.fields', [['key' => 'foto', 'label' => 'Fotos']])
            ->assertJsonCount(2, 'data.receipts')
            ->assertJsonPath('data.receipts.0.kind', 'withdrawal')
            ->assertJsonPath('data.status', 'corrected') // Statuswert bleibt (ältere App-Versionen)
            ->assertJsonPath('data.status_label', 'Einwilligung widerrufen');
        $withdrawal = InquiryReceipt::where('kind', 'withdrawal')->sole();
        $result->assertJsonPath('receipt.uuid', $withdrawal->uuid);

        // Bereits widerrufenes Feld → 422
        $this->postJson(route('api.v1.elternabfragen.withdraw', $assignment), ['fields' => ['video'], 'confirm' => 'accepted'])->assertStatus(422);

        // PDF-Download
        $pdf = $this->get(route('api.v1.elternabfragen.receipts.pdf', [$assignment, $withdrawal->uuid]))->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('Quittung_'.$withdrawal->uuid.'.pdf', $pdf->headers->get('Content-Disposition'));
        $this->assertSame($withdrawal->fresh()->pdf_sha256, hash('sha256', $pdf->getContent()));
    }

    #[Test]
    public function abgabe_und_bestaetigung_liefern_quittung_in_der_api(): void
    {
        $assignment = $this->assignmentFor($this->runningInquiry([], ['requires_all_guardians' => true, 'is_sensitive' => true]));

        Sanctum::actingAs($this->mutter);
        $submit = $this->postJson(route('api.v1.elternabfragen.submit', $assignment), ['answers' => $this->validAnswers(), 'confirm' => true])
            ->assertCreated()
            ->assertJsonPath('receipt.kind', 'submission')
            ->assertJsonPath('receipt.backfilled', false)
            ->assertJsonPath('receipt_delivery', 'download');
        $this->assertSame(InquiryReceipt::sole()->uuid, $submit->json('receipt.uuid'));

        Sanctum::actingAs($this->vater);
        $this->postJson(route('api.v1.elternabfragen.confirm', $assignment), ['confirm' => true])->assertOk()
            ->assertJsonPath('receipt.kind', 'confirmation')
            ->assertJsonPath('receipt.declarant_name', 'Vater Muster')
            ->assertJsonPath('receipt_delivery', 'download');

        // Ohne E-Mail-Adresse: nur Download
        $assignment2 = $this->assignmentFor($this->runningInquiry());
        $this->mutter->forceFill(['email' => ''])->saveQuietly();
        Sanctum::actingAs($this->mutter->fresh());
        $this->postJson(route('api.v1.elternabfragen.submit', $assignment2), ['answers' => $this->validAnswers(), 'confirm' => true])
            ->assertCreated()->assertJsonPath('receipt_delivery', 'download');
    }

    #[Test]
    public function rechte_bei_widerruf_und_quittungsdownload(): void
    {
        $assignment = $this->answered();
        $receipt = InquiryReceipt::sole();
        $stranger = $this->makeParent();

        // Fremde: Auftrag nicht sichtbar → 404 (verrät keine Existenz)
        Sanctum::actingAs($stranger);
        $this->postJson(route('api.v1.elternabfragen.withdraw', $assignment), ['confirm' => 'accepted'])->assertNotFound();
        $this->get(route('api.v1.elternabfragen.receipts.pdf', [$assignment, $receipt->uuid]))->assertNotFound();

        // Ohne Sorgerecht (nur Information): sieht den Auftrag, aber keine Quittungen, kein Widerruf → 403
        Sanctum::actingAs($this->oma);
        $this->getJson(route('api.v1.elternabfragen.show', $assignment))->assertOk()
            ->assertJsonPath('data.receipts', [])
            ->assertJsonPath('data.withdrawal', ['possible' => false, 'fields' => []]);
        $this->postJson(route('api.v1.elternabfragen.withdraw', $assignment), ['confirm' => 'accepted'])->assertForbidden();
        $this->get(route('api.v1.elternabfragen.receipts.pdf', [$assignment, $receipt->uuid]))->assertForbidden();
        $this->actingAs($this->oma)->post(route('elternabfragen.withdraw', $assignment), ['fields' => ['foto'], 'confirm' => 1])->assertForbidden();
        $this->get(route('elternabfragen.receipts.pdf', [$assignment, $receipt->uuid]))->assertForbidden();
        $this->get(route('elternabfragen.show', $assignment))->assertOk()->assertDontSee('Quittungen')->assertDontSee('Einwilligung widerrufen');

        // Quittung eines anderen Auftrags bzw. unbekannte UUID → 404
        $other = $this->answeredOther();
        $foreign = InquiryReceipt::where('assignment_id', $other->id)->firstOrFail();
        Sanctum::actingAs($this->mutter);
        $this->get(route('api.v1.elternabfragen.receipts.pdf', [$assignment, $foreign->uuid]))->assertNotFound();
        $this->get(route('api.v1.elternabfragen.receipts.pdf', [$assignment, '00000000-0000-4000-8000-000000000000']))->assertNotFound();
        $this->actingAs($this->mutter)->get(route('elternabfragen.receipts.pdf', [$assignment, $foreign->uuid]))->assertNotFound();

        // Vater (sorgeberechtigt) darf die Quittung der Mutter laden
        $this->actingAs($this->vater)->get(route('elternabfragen.receipts.pdf', [$assignment, $receipt->uuid]))->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        // Nach der Löschung: kein Widerruf mehr
        $assignment->inquiry->forceFill(['purged_at' => now()])->save();
        Sanctum::actingAs($this->mutter);
        $this->postJson(route('api.v1.elternabfragen.withdraw', $assignment), ['confirm' => 'accepted'])->assertNotFound();
        $this->assertSame(0, InquiryReceipt::where('kind', 'withdrawal')->count());
    }

    private function answeredOther(): InquiryAssignment
    {
        $inquiry = $this->runningInquiry(['title' => 'Andere Abfrage']);
        $other = $this->assignmentFor($inquiry);
        $this->actingAs($this->mutter)->post(route('elternabfragen.submit', $other), ['answers' => $this->validAnswers(), 'confirm' => 1]);

        return $other;
    }

    #[Test]
    public function widerruf_bei_gemeinsamer_erklaerung_wirkt_sofort(): void
    {
        // Mutter reicht ein, Vater bestätigt → vollständig
        $assignment = $this->answered([], ['requires_all_guardians' => true]);
        $this->actingAs($this->vater)->post(route('elternabfragen.confirm', $assignment), ['confirm' => 1]);
        $this->assertSame(InquiryAssignmentStatus::Submitted, $assignment->fresh()->status);

        // Vater widerruft – keine erneute Bestätigung durch die Mutter nötig
        $this->actingAs($this->vater)->post(route('elternabfragen.withdraw', $assignment), ['fields' => ['foto'], 'confirm' => 1])
            ->assertSessionHas('type', 'success');
        $assignment->refresh();
        $this->assertSame('denied', $assignment->currentResponse->answers['foto']);
        $this->assertNotSame(InquiryAssignmentStatus::AwaitingConfirmation, $assignment->status);
        $this->assertTrue($assignment->isComplete());
        $this->assertSame(1, InquiryReceipt::where('kind', 'withdrawal')->count());
        $this->assertFalse(app(\App\Services\Elternabfragen\GuardianConfirmation::class)->isPending($assignment));

        $overview = collect(app(\App\Services\Elternabfragen\GuardianConfirmation::class)->overview($assignment))->keyBy('name');
        $this->assertSame('submitted', $overview['Vater Muster']['state']);
        $this->assertSame('submitted', $overview['Mutter Muster']['state']);

        // Andere Sorgeberechtigte werden informiert, nicht um Bestätigung gebeten
        $this->assertTrue(Notification::where('user_id', $this->mutter->id)->where('title', 'Einwilligung widerrufen')->exists());
        $this->assertCount(0, $this->mailsTo($this->mutter->email, ElternabfrageMail::KIND_CONFIRMATION_REQUEST));
    }

    #[Test]
    public function widerruf_vor_vollstaendiger_bestaetigung_laesst_offene_bestaetigung_bestehen(): void
    {
        // Mutter reicht ein, Vater hat noch nicht bestätigt; Mutter widerruft selbst
        $assignment = $this->answered([], ['requires_all_guardians' => true]);
        $this->actingAs($this->mutter)->post(route('elternabfragen.withdraw', $assignment), ['fields' => ['video'], 'confirm' => 1]);
        $assignment->refresh();
        $this->assertSame(InquiryAssignmentStatus::AwaitingConfirmation, $assignment->status);
        $this->assertCount(2, $this->mailsTo($this->vater->email, ElternabfrageMail::KIND_CONFIRMATION_REQUEST));

        // Vater widerruft ebenfalls → sofort vollständig
        $this->actingAs($this->vater)->post(route('elternabfragen.withdraw', $assignment), ['fields' => ['foto'], 'confirm' => 1]);
        $assignment->refresh();
        $this->assertTrue($assignment->isComplete());
        $this->assertSame(['denied', 'denied'], [$assignment->currentResponse->answers['foto'], $assignment->currentResponse->answers['video']]);
    }

    // ── Layout ────────────────────────────────────────────────────────────────

    #[Test]
    public function layout_1_quittungen_rendern_unveraendert(): void
    {
        $assignment = $this->answered();
        $this->post(route('elternabfragen.submit', $assignment), ['answers' => ['foto' => 'granted', 'fahrrad' => 'no'] + $this->validAnswers(), 'confirm' => 1, 'correction_reason' => 'x']);

        foreach (InquiryReceipt::orderBy('sequence')->get() as $receipt) {
            // Quittung so, wie sie vor Layout 2 ausgestellt wurde (Datensatz unveränderlich → direkt in der DB)
            DB::table('inquiry_receipts')->where('id', $receipt->id)->update(['layout_version' => 1, 'pdf_sha256' => null]);
            $old = $receipt->fresh();
            $current[$old->id] = hash('sha256', $this->service()->renderPdf($old));
            $this->assertStringContainsString('Layout 1', $this->service()->html($old));
        }

        // Referenz: unveränderte View aus Phase 1
        View::getFinder()->prependLocation(__DIR__.'/fixtures/views');
        View::getFinder()->flush();
        $this->assertStringContainsString('fixtures', View::getFinder()->find('elternabfragen.pdf.receipt'));
        foreach (InquiryReceipt::orderBy('sequence')->get() as $old) {
            $this->assertSame(hash('sha256', $this->service()->renderPdf($old)), $current[$old->id], 'Layout-1-Quittung '.$old->kind->value.' rendert anders');
        }
    }
}
