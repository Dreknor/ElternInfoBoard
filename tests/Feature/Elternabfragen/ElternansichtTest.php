<?php

namespace Tests\Feature\Elternabfragen;

use App\Enums\InquiryAssignmentStatus;
use App\Model\InquiryResponse;
use App\Model\Notification;
use App\Services\Elternabfragen\AssignmentAdminService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;

/**
 * Prozess C und Kap. 7: Mandat je Kind, Entwurf, Absenden, Versionen und Korrekturen.
 */
class ElternansichtTest extends ElternabfragenTestCase
{
    #[Test]
    public function eltern_sehen_offene_abfrage_und_formular(): void
    {
        $inquiry = $this->runningInquiry();
        $assignment = $this->assignmentFor($inquiry);

        $this->actingAs($this->mutter)->get(route('elternabfragen.index'))->assertOk()->assertSee('Fahrraderlaubnis 2026')->assertSee('Mia Muster');
        $this->get(route('elternabfragen.show', $assignment))->assertOk()
            ->assertSee('Wir benötigen die Erlaubnis für den Schulweg.')
            ->assertSee('Ich willige ein, dass Fotos veröffentlicht werden.')
            ->assertSee('Verbindlich absenden');
        $this->get(route('elternabfragen.open', $inquiry))->assertRedirect(route('elternabfragen.show', $assignment));
    }

    #[Test]
    public function fremde_und_manipulierte_ids_liefern_404(): void
    {
        $assignment = $this->assignmentFor($this->runningInquiry());
        $fremd = $this->makeParent();

        $this->actingAs($fremd)->get(route('elternabfragen.show', $assignment))->assertNotFound();
        $this->post(route('elternabfragen.submit', $assignment), ['answers' => $this->validAnswers(), 'confirm' => 1])->assertNotFound();
        $this->assertSame(0, InquiryResponse::count());
    }

    #[Test]
    public function nur_leseberechtigte_sehen_aber_koennen_nicht_abgeben(): void
    {
        $assignment = $this->assignmentFor($this->runningInquiry());

        $this->actingAs($this->oma)->get(route('elternabfragen.show', $assignment))->assertOk()
            ->assertSee('nur sorgeberechtigte Personen')
            ->assertDontSee('Verbindlich absenden');
        $this->post(route('elternabfragen.submit', $assignment), ['answers' => $this->validAnswers(), 'confirm' => 1])->assertForbidden();
    }

    #[Test]
    public function abgelaufenes_mandat_gibt_keinen_zugriff(): void
    {
        $assignment = $this->assignmentFor($this->runningInquiry());
        $this->mia->parents()->updateExistingPivot($this->vater->id, ['valid_until' => now()->subDays(2)->toDateString()]);

        $this->actingAs($this->vater)->get(route('elternabfragen.show', $assignment))->assertNotFound();
    }

    #[Test]
    public function pflichtfelder_und_formate_werden_serverseitig_geprueft(): void
    {
        $assignment = $this->assignmentFor($this->runningInquiry());

        $this->actingAs($this->mutter)
            ->post(route('elternabfragen.submit', $assignment), ['answers' => ['telefon' => 'abc'], 'confirm' => 1])
            ->assertSessionHasErrors(['answers.fahrrad', 'answers.foto', 'answers.telefon']);

        $this->post(route('elternabfragen.submit', $assignment), ['answers' => $this->validAnswers()])->assertSessionHasErrors('confirm');
        $this->assertSame(0, InquiryResponse::count());
    }

    #[Test]
    public function entwurf_gilt_nicht_als_rueckmeldung(): void
    {
        $assignment = $this->assignmentFor($this->runningInquiry());

        $this->actingAs($this->mutter)
            ->post(route('elternabfragen.draft', $assignment), ['answers' => ['fahrrad' => 'no']])
            ->assertSessionHasNoErrors();

        $assignment->refresh();
        $this->assertSame(InquiryAssignmentStatus::InProgress, $assignment->status);
        $this->assertNull($assignment->current_response_id);
        $this->get(route('elternabfragen.show', $assignment))->assertSee('gespeicherter Entwurf');
    }

    #[Test]
    public function absenden_erzeugt_unveraenderliche_version_mit_nachweis(): void
    {
        $assignment = $this->assignmentFor($this->runningInquiry());

        $this->actingAs($this->mutter)
            ->post(route('elternabfragen.submit', $assignment), ['answers' => $this->validAnswers(), 'confirm' => 1])
            ->assertRedirect(route('elternabfragen.show', $assignment))
            ->assertSessionHasNoErrors();

        $response = InquiryResponse::firstOrFail();
        $this->assertSame(1, $response->version);
        $this->assertSame($this->mutter->id, $response->user_id);
        $this->assertSame('yes', $response->answers['fahrrad']);
        $this->assertSame(64, strlen($response->form_hash));
        $this->assertSame(64, strlen($response->answer_hash));
        $this->assertSame(InquiryAssignmentStatus::Submitted, $assignment->fresh()->status);

        // anderer Elternteil wird informiert, Absender erhält Bestätigung
        $this->assertTrue(Notification::where('user_id', $this->vater->id)->where('title', 'Abfrage beantwortet')->exists());
        $this->assertTrue(Notification::where('user_id', $this->mutter->id)->where('title', 'Abfrage eingereicht')->exists());

        $this->get(route('elternabfragen.show', $assignment))->assertSee('Eingereichte Angaben')->assertSee('Einwilligung nicht erteilt');
        $this->get(route('elternabfragen.pdf', $assignment))->assertOk()->assertHeader('content-type', 'application/pdf');

        $this->expectException(\LogicException::class);
        $response->update(['answers' => ['fahrrad' => 'no']]);
    }

    #[Test]
    public function korrektur_im_zeitfenster_erzeugt_neue_version(): void
    {
        $assignment = $this->assignmentFor($this->runningInquiry());
        $this->actingAs($this->mutter)->post(route('elternabfragen.submit', $assignment), ['answers' => $this->validAnswers(), 'confirm' => 1]);

        // zweiter Elternteil korrigiert im Zeitfenster
        $this->actingAs($this->vater)
            ->post(route('elternabfragen.submit', $assignment), [
                'answers' => ['fahrrad' => 'no'] + $this->validAnswers(), 'confirm' => 1, 'correction_reason' => 'Doch nicht',
            ])
            ->assertSessionHasNoErrors();

        $versions = InquiryResponse::orderBy('version')->get();
        $this->assertCount(2, $versions);
        $this->assertFalse($versions[0]->is_current);
        $this->assertTrue($versions[1]->is_current);
        $this->assertSame($versions[0]->id, $versions[1]->previous_response_id);
        $this->assertSame('Doch nicht', $versions[1]->correction_reason);
        $this->assertSame(InquiryAssignmentStatus::Corrected, $assignment->fresh()->status);
    }

    #[Test]
    public function nach_dem_zeitfenster_nur_ueber_das_sekretariat(): void
    {
        $assignment = $this->assignmentFor($this->runningInquiry());
        $this->actingAs($this->mutter)->post(route('elternabfragen.submit', $assignment), ['answers' => $this->validAnswers(), 'confirm' => 1]);

        $this->travel(8)->days();
        $this->post(route('elternabfragen.submit', $assignment), ['answers' => $this->validAnswers(), 'confirm' => 1])->assertStatus(409);

        $this->actingAs($this->sekretariat);
        app(AssignmentAdminService::class)->unlockCorrection($assignment->fresh(), now()->addDays(2), 'Adresse falsch');

        $this->actingAs($this->mutter)
            ->post(route('elternabfragen.submit', $assignment), ['answers' => $this->validAnswers(), 'confirm' => 1])
            ->assertSessionHasNoErrors();
        $this->assertSame(2, InquiryResponse::count());
    }

    #[Test]
    public function nach_fristende_keine_abgabe_ausser_mit_nachfrist(): void
    {
        $assignment = $this->assignmentFor($this->runningInquiry());
        $this->travel(15)->days();

        $this->actingAs($this->mutter)
            ->post(route('elternabfragen.submit', $assignment), ['answers' => $this->validAnswers(), 'confirm' => 1])
            ->assertForbidden();

        $this->actingAs($this->sekretariat);
        app(AssignmentAdminService::class)->extend($assignment->fresh(), now()->addDays(3), 'Krankheit');

        $this->actingAs($this->mutter)
            ->post(route('elternabfragen.submit', $assignment), ['answers' => $this->validAnswers(), 'confirm' => 1])
            ->assertSessionHasNoErrors();
    }

    #[Test]
    public function datei_upload_bleibt_bei_korrektur_erhalten(): void
    {
        Storage::fake('local');
        $inquiry = $this->runningInquiryWithFile();
        $assignment = $this->assignmentFor($inquiry);

        $this->actingAs($this->mutter)
            ->post(route('elternabfragen.submit', $assignment), [
                'answers' => ['nachweis' => UploadedFile::fake()->create('attest.pdf', 50, 'application/pdf')],
                'confirm' => 1,
            ])
            ->assertSessionHasNoErrors();

        $first = InquiryResponse::firstOrFail();
        Storage::disk('local')->assertExists($first->answers['nachweis']['path']);

        $this->post(route('elternabfragen.submit', $assignment), ['answers' => [], 'confirm' => 1])->assertSessionHasNoErrors();
        $second = InquiryResponse::where('version', 2)->firstOrFail();
        $this->assertSame($first->answers['nachweis']['path'], $second->answers['nachweis']['path']);

        $this->get(route('elternabfragen.file', [$assignment, 'nachweis']))->assertOk();
        $this->actingAs($this->oma)->get(route('elternabfragen.file', [$assignment, 'nachweis']))->assertNotFound();
    }

    private function runningInquiryWithFile()
    {
        $templates = app(\App\Services\Elternabfragen\TemplateService::class);
        $this->actingAs($this->sekretariat);
        $version = $templates->create([
            'title' => 'Nachweis', 'purpose' => 'Attest',
            'fields' => [['key' => 'nachweis', 'type' => 'file', 'label' => 'Attest', 'required' => true]],
        ], $this->sekretariat);
        $templates->submit($version);
        $templates->approve($version, $this->schulleitung, true, null);

        $service = app(\App\Services\Elternabfragen\InquiryService::class);
        $inquiry = $service->create([
            'template_version_id' => $version->id, 'title' => 'Attest', 'starts_at' => now()->subMinute(),
            'deadline_at' => now()->addWeek(), 'target_type' => 'groups', 'target_group_ids' => [$this->klasse->id],
            'notify_mail' => true, 'correction_window_days' => 7,
        ], $this->sekretariat);
        $service->submit($inquiry);
        $service->approve($inquiry, $this->schulleitung);

        return $inquiry->fresh();
    }
}
