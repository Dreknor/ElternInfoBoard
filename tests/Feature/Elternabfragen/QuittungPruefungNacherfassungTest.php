<?php

namespace Tests\Feature\Elternabfragen;

use App\Enums\InquiryReceiptKind;
use App\Model\InquiryAssignment;
use App\Model\InquiryChangeRequest;
use App\Model\InquiryConfirmation;
use App\Model\InquiryEvent;
use App\Model\InquiryReceipt;
use App\Model\InquiryResponse;
use App\Services\Elternabfragen\ReceiptBackfill;
use App\Services\Elternabfragen\ReceiptService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;

/**
 * Erklärungsquittungen, Phase 2B: Kettenprüfung per Befehl, Nacherfassung des Bestands,
 * Prüfseite für vorgelegte PDFs und Quittungsbereich auf der Detailseite der Abfrage.
 */
class QuittungPruefungNacherfassungTest extends ElternabfragenTestCase
{
    private function submit(InquiryAssignment $assignment, array $answers = [], array $extra = []): void
    {
        $this->actingAs($this->mutter)
            ->post(route('elternabfragen.submit', $assignment), ['answers' => $answers + $this->validAnswers(), 'confirm' => 1] + $extra)
            ->assertRedirect(route('elternabfragen.show', $assignment));
    }

    private function confirmAsFather(InquiryAssignment $assignment): void
    {
        $this->actingAs($this->vater)->post(route('elternabfragen.confirm', $assignment), ['confirm' => 1])
            ->assertRedirect(route('elternabfragen.show', $assignment));
    }

    private function answered(array $template = []): InquiryAssignment
    {
        $assignment = $this->assignmentFor($this->runningInquiry(['deadline_at' => now()->addDays(30)], $template));
        $this->submit($assignment);

        return $assignment->fresh();
    }

    /**
     * Bestand wie vor den Quittungen: Abgabe, Bestätigung, Korrektur, Bestätigung,
     * übernommene Änderungsmeldung – danach alle Quittungen entfernt.
     */
    private function legacyHistory(): InquiryAssignment
    {
        $assignment = $this->answered(['requires_all_guardians' => true]);
        $this->travel(1)->hours();
        $this->confirmAsFather($assignment);
        $this->travel(1)->hours();
        $this->submit($assignment, ['fahrrad' => 'no'], ['correction_reason' => 'Doch nicht']);
        $this->travel(1)->hours();
        $this->confirmAsFather($assignment);
        $this->travel(8)->days();
        $this->actingAs($this->mutter)->post(route('elternabfragen.change', $assignment), [
            'answers' => ['telefon' => '0351 999999', 'fahrrad' => 'no'] + $this->validAnswers(),
            'message' => 'Umzug',
            'confirm' => 1,
        ])->assertSessionHas('type', 'success');
        $this->actingAs($this->sekretariat)
            ->post(route('elternabfragen.admin.change-requests.accept', InquiryChangeRequest::firstOrFail()))
            ->assertRedirect();
        $this->travel(1)->hours();

        $this->assertSame(5, InquiryReceipt::count());
        $this->wipeReceipts();

        return $assignment->fresh();
    }

    /** Quittungen am Modell vorbei entfernen (Altbestand simulieren). */
    private function wipeReceipts(): void
    {
        DB::table('inquiry_receipts')->delete();
    }

    #[Test]
    public function verify_befehl_meldet_ok_und_erkennt_manipulationen(): void
    {
        $assignment = $this->answered();
        $inquiry = $assignment->inquiry;

        $this->artisan('elternabfragen:verify', ['inquiry' => $inquiry->id])
            ->expectsOutputToContain('OK')
            ->assertExitCode(0);
        $this->artisan('elternabfragen:verify', ['--all' => true])->assertExitCode(0);
        $this->artisan('elternabfragen:verify')->assertExitCode(2);
        $this->artisan('elternabfragen:verify', ['inquiry' => 999999])->assertExitCode(1);

        Log::spy();
        DB::table('inquiry_receipts')->update(['declarant_name' => 'Jemand anderes']);

        $this->artisan('elternabfragen:verify', ['inquiry' => $inquiry->id])
            ->expectsOutputToContain('FEHLER')
            ->expectsOutputToContain('Quittungsdaten wurden verändert')
            ->assertExitCode(1);
        $this->artisan('elternabfragen:verify', ['--all' => true])->assertExitCode(1);

        Log::shouldHaveReceived('warning')->withArgs(fn (string $message, array $context) => $context['inquiry_id'] === $inquiry->id
            && ! str_contains(json_encode($context), 'Jemand') && ! str_contains(json_encode($context), 'Mia'));
    }

    #[Test]
    public function nacherfassung_legt_fehlende_quittungen_chronologisch_an(): void
    {
        $assignment = $this->legacyHistory();
        $inquiry = $assignment->inquiry;
        $backfill = app(ReceiptBackfill::class);
        $this->assertTrue($backfill->hasPending());
        Mail::fake();

        // Probelauf ändert nichts
        $this->artisan('elternabfragen:quittungen-nacherfassen', ['--dry-run' => true])
            ->expectsOutputToContain('5 Quittungen in 1 Abfrage würden nacherfasst')
            ->assertExitCode(0);
        $this->assertSame(0, InquiryReceipt::count());

        $this->artisan('elternabfragen:quittungen-nacherfassen')->assertExitCode(0);

        $receipts = InquiryReceipt::orderBy('sequence')->get();
        $this->assertSame([
            InquiryReceiptKind::Submission, InquiryReceiptKind::Confirmation, InquiryReceiptKind::Correction,
            InquiryReceiptKind::Confirmation, InquiryReceiptKind::ChangeAccepted,
        ], $receipts->pluck('kind')->all());
        $this->assertSame([1, 2, 3, 4, 5], $receipts->pluck('sequence')->all());
        $this->assertTrue($receipts->every(fn (InquiryReceipt $r) => $r->backfilled));
        $this->assertTrue($receipts->every(fn (InquiryReceipt $r) => $r->pdf_sha256 !== null));

        // Ursprüngliche Zeitpunkte, erklärende Personen und Versionen
        $versions = InquiryResponse::where('assignment_id', $assignment->id)->orderBy('version')->get();
        $confirmations = InquiryConfirmation::orderBy('id')->get();
        $this->assertEquals($versions[0]->submitted_at, $receipts[0]->declared_at);
        $this->assertEquals($confirmations[0]->confirmed_at, $receipts[1]->declared_at);
        $this->assertEquals($versions[2]->submitted_at, $receipts[4]->declared_at);
        $this->assertSame('Mutter Muster', $receipts[0]->declarant_name);
        $this->assertSame('Vater Muster', $receipts[1]->declarant_name);
        $this->assertSame($confirmations[1]->id, $receipts[3]->confirmation_id);
        $this->assertSame([1, 1, 2, 2, 3], $receipts->pluck('response_version')->all());
        $this->assertSame($versions[0]->answer_hash, $receipts[0]->answer_hash);

        $result = app(ReceiptService::class)->verifyChain($inquiry);
        $this->assertTrue($result['valid'], implode("\n", $result['errors']));
        $this->assertFalse($backfill->hasPending());
        $this->assertTrue(InquiryEvent::where('inquiry_id', $inquiry->id)->where('action', 'receipts.backfilled')->exists());

        // Idempotent: zweiter Lauf legt nichts an
        $this->artisan('elternabfragen:quittungen-nacherfassen')
            ->expectsOutputToContain('0 Quittungen in 0 Abfragen nacherfasst')
            ->assertExitCode(0);
        $this->assertSame(5, InquiryReceipt::count());

        // Keine E-Mails für nacherfasste Quittungen
        Mail::assertNothingOutgoing();
    }

    #[Test]
    public function fehlende_quittungen_werden_an_eine_bestehende_kette_angehaengt(): void
    {
        $assignment = $this->legacyHistory();
        // Nach dem Update: Vater bestätigt die übernommene Änderung (neue Quittung, Nr. 1)
        $this->confirmAsFather($assignment);
        $live = InquiryReceipt::sole();
        $this->assertFalse($live->backfilled);

        $stats = app(ReceiptBackfill::class)->run();
        $this->assertSame(['inquiries' => 1, 'receipts' => 5, 'appended' => 1, 'failed' => 0], $stats);

        $receipts = InquiryReceipt::orderBy('sequence')->get();
        $this->assertSame($live->id, $receipts[0]->id);
        $this->assertSame([false, true, true, true, true, true], $receipts->pluck('backfilled')->all());
        $this->assertSame(InquiryReceiptKind::Submission, $receipts[1]->kind);
        $this->assertSame($live->content_hash, $receipts[1]->prev_hash);
        $this->assertTrue(app(ReceiptService::class)->verifyChain($assignment->inquiry)['valid']);
    }

    #[Test]
    public function pruefseite_erkennt_echte_und_veraenderte_pdfs(): void
    {
        $assignment = $this->answered();
        $receipt = InquiryReceipt::sole();
        $bytes = app(ReceiptService::class)->pdf($receipt);

        $this->actingAs($this->sekretariat)->get(route('elternabfragen.admin.receipts.check'))
            ->assertOk()->assertSee('Quittung prüfen')->assertSee('Was wird geprüft?');

        $this->actingAs($this->sekretariat)
            ->post(route('elternabfragen.admin.receipts.verify'), ['pdf' => UploadedFile::fake()->createWithContent('quittung.pdf', $bytes)])
            ->assertOk()
            ->assertSee('Echt und unverändert')
            ->assertSee($receipt->uuid)
            ->assertSee('Fahrraderlaubnis 2026')
            ->assertSee('Mia Muster')
            ->assertSee('Mutter Muster')
            ->assertSee('Abgabe der Erklärung')
            ->assertSee('Quittungskette der Abfrage ist vollständig');

        // Ein einziges verändertes Byte
        $position = intdiv(strlen($bytes), 2);
        $changed = substr_replace($bytes, chr((ord($bytes[$position]) + 1) % 256), $position, 1);
        $this->actingAs($this->sekretariat)
            ->post(route('elternabfragen.admin.receipts.verify'), ['pdf' => UploadedFile::fake()->createWithContent('quittung.pdf', $changed)])
            ->assertOk()
            ->assertSee('Nicht bekannt oder verändert')
            ->assertDontSee($receipt->uuid);

        // Keine Ablage der hochgeladenen Datei
        $this->assertSame(1, InquiryReceipt::count());

        // Nur PDF
        $this->actingAs($this->sekretariat)
            ->post(route('elternabfragen.admin.receipts.verify'), ['pdf' => UploadedFile::fake()->create('bild.png', 10, 'image/png')])
            ->assertSessionHasErrors('pdf');

        // Zweitschrift ist byteidentisch und wird protokolliert
        $download = $this->actingAs($this->sekretariat)->get(route('elternabfragen.admin.receipts.download', $receipt))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertSame($receipt->pdf_sha256, hash('sha256', $download->getContent()));
        $this->assertTrue(InquiryEvent::where('assignment_id', $assignment->id)->where('action', 'receipt.downloaded')->exists());
    }

    #[Test]
    public function pruefseite_und_zweitschrift_nur_mit_recht_auf_antworten(): void
    {
        $this->answered();
        $receipt = InquiryReceipt::sole();
        $pdf = UploadedFile::fake()->createWithContent('quittung.pdf', app(ReceiptService::class)->pdf($receipt));

        $staff = $this->makeParent(['name' => 'Lehrkraft']);
        $staff->givePermissionTo('read elternabfragen');

        foreach ([$this->mutter, $staff] as $user) {
            $this->actingAs($user)->get(route('elternabfragen.admin.receipts.check'))->assertForbidden();
            $this->actingAs($user)->post(route('elternabfragen.admin.receipts.verify'), ['pdf' => $pdf])->assertForbidden();
            $this->actingAs($user)->get(route('elternabfragen.admin.receipts.download', $receipt))->assertForbidden();
        }
    }

    #[Test]
    public function detailseite_zeigt_kettenstatus_und_quittungen_je_kind(): void
    {
        $assignment = $this->answered();
        $this->submit($assignment, ['fahrrad' => 'no'], ['correction_reason' => 'Doch nicht']);
        $inquiry = $assignment->inquiry;
        $receipts = InquiryReceipt::orderBy('sequence')->get();

        $this->actingAs($this->sekretariat)->get(route('elternabfragen.admin.inquiries.show', $inquiry))
            ->assertOk()
            ->assertSee('Quittungen')
            ->assertSee('Kette in Ordnung')
            ->assertSee('Korrektur der Erklärung')
            ->assertSee(route('elternabfragen.admin.receipts.download', $receipts[1]), false);

        // Ohne Recht auf Antwortinhalte kein Quittungsbereich
        $manager = $this->makeParent(['name' => 'Nur Rücklauf']);
        $manager->givePermissionTo('export elternabfragen');
        $this->actingAs($manager)->get(route('elternabfragen.admin.inquiries.show', $inquiry))
            ->assertOk()->assertDontSee('Kette in Ordnung');

        DB::table('inquiry_receipts')->where('id', $receipts[0]->id)->update(['child_name' => 'Manipuliert']);
        $this->actingAs($this->sekretariat)->get(route('elternabfragen.admin.inquiries.show', $inquiry))
            ->assertOk()
            ->assertSee('Kette fehlerhaft')
            ->assertSee('Quittungsdaten wurden verändert');
    }
}
