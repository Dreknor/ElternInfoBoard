<?php

namespace Tests\Feature\Elternabfragen;

use App\Enums\InquiryReviewStatus;
use App\Mail\ElternabfrageMail;
use App\Model\InquiryDelivery;
use App\Model\InquiryExport;
use App\Model\InquiryResponse;
use App\Services\Elternabfragen\ReminderService;
use App\Services\Elternabfragen\ResponseService;
use App\Services\Elternabfragen\RetentionService;
use App\Services\App\TodoService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;

/**
 * Prozess D/E, Kap. 8, 9, 7.4 und App-API v1.
 */
class RuecklaufExportApiTest extends ElternabfragenTestCase
{
    private function submit($assignment, array $answers = null): InquiryResponse
    {
        return app(ResponseService::class)->submit($assignment->fresh(), $this->mutter, $answers ?? $this->validAnswers(), []);
    }

    #[Test]
    public function erinnerungen_folgen_den_standardregeln(): void
    {
        $inquiry = $this->runningInquiry();
        $assignment = $this->assignmentFor($inquiry);
        $reminders = app(ReminderService::class);
        Mail::fake();

        // frühestens nach 48 Stunden
        $result = $reminders->remindManually($inquiry, null, $this->sekretariat->id);
        $this->assertSame(0, $result['sent']);

        $this->travel(49)->hours();
        $this->assertSame(1, $reminders->remindManually($inquiry, null, $this->sekretariat->id)['sent']);
        Mail::assertSent(ElternabfrageMail::class, fn ($m) => $m->kind === ElternabfrageMail::KIND_REMINDER);
        $this->assertSame(1, $assignment->fresh()->reminder_count);
        $this->assertTrue(InquiryDelivery::where('kind', InquiryDelivery::KIND_REMINDER_MANUAL)->where('triggered_by', $this->sekretariat->id)->exists());

        // höchstens einmal innerhalb von 24 Stunden
        $this->travel(2)->hours();
        $this->assertSame(0, $reminders->remindManually($inquiry, null, $this->sekretariat->id)['sent']);

        // keine Erinnerung nach erfolgreicher Einreichung
        $this->submit($assignment);
        $this->travel(1)->days();
        $this->assertSame(0, $reminders->remindManually($inquiry, null, $this->sekretariat->id)['sent']);
    }

    #[Test]
    public function automatische_erinnerung_vor_fristende_einmalig(): void
    {
        $inquiry = $this->runningInquiry();
        $reminders = app(ReminderService::class);

        $this->assertSame(0, $reminders->runAutomatic());
        $this->travel(13)->days();
        $this->assertSame(1, $reminders->runAutomatic());
        $this->travel(1)->hours();
        $this->assertSame(0, $reminders->runAutomatic(), 'nicht doppelt');
        $this->assertSame(1, InquiryDelivery::where('kind', InquiryDelivery::KIND_REMINDER_BEFORE)->distinct('assignment_id')->count('assignment_id'));
    }

    #[Test]
    public function ruecklaufuebersicht_zeigt_keine_antwortinhalte(): void
    {
        $inquiry = $this->runningInquiry();
        $this->submit($this->assignmentFor($inquiry), ['hinweis' => 'Geheimer Hinweis'] + $this->validAnswers());

        $this->actingAs($this->sekretariat)->get(route('elternabfragen.admin.inquiries.show', $inquiry))
            ->assertOk()->assertSee('Eingereicht')->assertDontSee('Geheimer Hinweis');
    }

    #[Test]
    public function mitarbeitende_sehen_nur_gepruefte_antworten_freigegebener_abfragen(): void
    {
        $inquiry = $this->runningInquiry();
        $response = $this->submit($this->assignmentFor($inquiry), ['hinweis' => 'Allergie Nüsse'] + $this->validAnswers());
        $lehrer = $this->makeParent();
        $lehrer->givePermissionTo('read elternabfragen');

        $this->actingAs($lehrer)->get(route('elternabfragen.admin.responses.show', $response))->assertNotFound();

        $inquiry->update(['staff_visible' => true]);
        $this->actingAs($lehrer)->get(route('elternabfragen.admin.responses.show', $response))->assertNotFound();

        $this->actingAs($this->sekretariat)
            ->post(route('elternabfragen.admin.responses.review', $response), ['review_status' => InquiryReviewStatus::Approved->value])
            ->assertSessionHasNoErrors();

        $this->actingAs($lehrer)->get(route('elternabfragen.admin.staff', ['inhalt' => 'Nüsse']))->assertOk()->assertSee('Mia');
        $this->get(route('elternabfragen.admin.responses.show', $response))->assertOk()->assertSee('Allergie Nüsse');
        $this->get(route('elternabfragen.admin.index'))->assertRedirect(route('elternabfragen.admin.staff'));
    }

    #[Test]
    public function export_mit_vorschau_protokoll_und_ablauf(): void
    {
        Storage::fake('local');
        $inquiry = $this->runningInquiry();
        $this->submit($this->assignmentFor($inquiry), ['hinweis' => '=HYPERLINK("x")'] + $this->validAnswers());

        $this->actingAs($this->sekretariat)
            ->post(route('elternabfragen.admin.exports.preview', $inquiry), ['columns' => ['_child', 'fahrrad', 'hinweis'], 'format' => 'csv'])
            ->assertOk()->assertSee('Vorschau vor dem Herunterladen');

        $this->post(route('elternabfragen.admin.exports.store', $inquiry), ['columns' => ['_child', 'fahrrad', 'hinweis'], 'format' => 'csv'])
            ->assertSessionHasErrors('target');

        $this->post(route('elternabfragen.admin.exports.store', $inquiry), ['columns' => ['_child', 'fahrrad', 'hinweis'], 'format' => 'csv', 'target' => 'Schulverwaltung'])
            ->assertRedirect();

        $export = InquiryExport::firstOrFail();
        $this->assertSame(1, $export->record_count);
        $csv = Storage::disk('local')->get($export->path);
        $this->assertStringContainsString('Mia Muster', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv, 'Formeln werden neutralisiert');

        $this->get(route('elternabfragen.admin.exports.download', $export))->assertOk();
        $this->assertSame(1, $export->fresh()->download_count);
        $this->assertDatabaseHas('inquiry_events', ['action' => 'export.downloaded']);

        $this->travel(8)->days();
        $this->artisan('elternabfragen:process')->assertSuccessful();
        Storage::disk('local')->assertMissing($export->path);
        $this->get(route('elternabfragen.admin.exports.download', $export))->assertStatus(410);

        $ohneRecht = $this->makeParent();
        $ohneRecht->givePermissionTo('manage elternabfragen');
        $this->actingAs($ohneRecht)->get(route('elternabfragen.admin.exports.create', $inquiry))->assertForbidden();
    }

    #[Test]
    public function antworten_werden_nach_der_aufbewahrungsfrist_geloescht(): void
    {
        $inquiry = $this->runningInquiry();
        $this->submit($this->assignmentFor($inquiry));
        $inquiry->update(['retention_days' => 30]);
        $this->actingAs($this->sekretariat)->post(route('elternabfragen.admin.inquiries.close', $inquiry))->assertSessionHasNoErrors();

        $this->travel(29)->days();
        $this->assertSame(0, app(RetentionService::class)->run());

        $this->travel(2)->days();
        $this->assertSame(1, app(RetentionService::class)->run());
        $this->assertSame(0, InquiryResponse::count());
        $this->assertSame(0, $inquiry->assignments()->count());
        $this->assertNotNull($inquiry->fresh()->purged_at);
        $this->assertDatabaseHas('inquiry_events', ['inquiry_id' => $inquiry->id, 'action' => 'inquiry.purged']);
    }

    #[Test]
    public function offene_abfrage_erscheint_in_der_aufgabenliste(): void
    {
        $inquiry = $this->runningInquiry();

        $todos = collect(app(TodoService::class)->forUser($this->mutter))->where('type', 'inquiry');
        $this->assertCount(1, $todos);
        $this->assertSame($this->assignmentFor($inquiry)->id, $todos->first()['target']['id']);
        $this->assertCount(0, collect(app(TodoService::class)->forUser($this->oma))->where('type', 'inquiry'), 'nicht für Leseberechtigte');

        $this->submit($this->assignmentFor($inquiry));
        $this->assertCount(0, collect(app(TodoService::class)->forUser($this->mutter))->where('type', 'inquiry'));
    }

    #[Test]
    public function app_api_liste_detail_entwurf_und_abgabe(): void
    {
        $inquiry = $this->runningInquiry();
        $assignment = $this->assignmentFor($inquiry);
        Sanctum::actingAs($this->mutter);

        $this->getJson('/api/v1/elternabfragen')->assertOk()
            ->assertJsonPath('data.0.id', $assignment->id)
            ->assertJsonPath('data.0.child.first_name', 'Mia')
            ->assertJsonPath('data.0.can_submit', true);

        $this->getJson("/api/v1/elternabfragen/{$assignment->id}")->assertOk()
            ->assertJsonPath('data.purpose', 'Wir benötigen die Erlaubnis für den Schulweg.')
            ->assertJsonPath('data.fields.1.key', 'fahrrad')
            ->assertJsonPath('data.fields.2.consent_text', 'Ich willige ein, dass Fotos veröffentlicht werden.');

        $this->putJson("/api/v1/elternabfragen/{$assignment->id}/draft", ['answers' => ['fahrrad' => 'no']])->assertOk()
            ->assertJsonPath('data.answers.fahrrad', 'no');

        $this->postJson("/api/v1/elternabfragen/{$assignment->id}/submit", ['answers' => ['fahrrad' => 'yes'], 'confirm' => true])
            ->assertStatus(422)->assertJsonValidationErrors(['answers.foto', 'answers.telefon']);

        $this->postJson("/api/v1/elternabfragen/{$assignment->id}/submit", ['answers' => $this->validAnswers(), 'confirm' => true])
            ->assertCreated()
            ->assertJsonPath('data.status', 'submitted')
            ->assertJsonPath('data.response.version', 1)
            ->assertJsonPath('data.response.answers.fahrrad', 'yes');

        $this->assertSame('app', InquiryResponse::firstOrFail()->channel);
        $this->getJson("/api/v1/elternabfragen/{$assignment->id}")->assertJsonPath('data.can_correct', true)->assertJsonPath('data.draft', null);
        $this->get("/api/v1/elternabfragen/{$assignment->id}/pdf")->assertOk();
        $this->getJson('/api/v1/parent/todo')->assertOk();
    }

    #[Test]
    public function app_api_schuetzt_fremde_und_leseberechtigte(): void
    {
        $assignment = $this->assignmentFor($this->runningInquiry());

        Sanctum::actingAs($this->makeParent());
        $this->getJson("/api/v1/elternabfragen/{$assignment->id}")->assertNotFound();
        $this->getJson('/api/v1/elternabfragen')->assertOk()->assertJsonCount(0, 'data');

        Sanctum::actingAs($this->oma);
        $this->getJson("/api/v1/elternabfragen/{$assignment->id}")->assertOk()->assertJsonPath('data.can_submit', false)->assertJsonPath('data.response', null);
        $this->postJson("/api/v1/elternabfragen/{$assignment->id}/submit", ['answers' => $this->validAnswers(), 'confirm' => true])->assertForbidden();
    }
}
