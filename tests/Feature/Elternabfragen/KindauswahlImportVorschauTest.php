<?php

namespace Tests\Feature\Elternabfragen;

use App\Model\Inquiry;
use App\Model\InquiryResponse;
use App\Model\InquiryTemplateVersion;
use App\Services\Elternabfragen\InquiryService;
use App\Services\Elternabfragen\TemplateImporter;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;

/**
 * Feldtyp „Auswahl eines eigenen Kindes“, Import von Excel-Listen als Vorlage und Elternvorschau von Vorlagen.
 */
class KindauswahlImportVorschauTest extends ElternabfragenTestCase
{
    private function childInquiry(): Inquiry
    {
        $version = $this->approvedVersion(['fields' => [
            ['key' => 'geschwister', 'type' => 'child', 'label' => 'Welches Geschwisterkind fährt mit?', 'required' => true],
        ]]);
        $service = app(InquiryService::class);
        $this->actingAs($this->sekretariat);
        $inquiry = $service->create([
            'template_version_id' => $version->id,
            'title' => 'Fahrgemeinschaft',
            'starts_at' => now()->subMinute(),
            'deadline_at' => now()->addDays(14),
            'target_type' => Inquiry::TARGET_GROUPS,
            'target_group_ids' => [$this->klasse->id],
            'notify_mail' => false,
            'notify_push' => true,
            'auto_reminders' => false,
            'correction_window_days' => 7,
        ], $this->sekretariat);
        $service->submit($inquiry);
        $this->actingAs($this->schulleitung);
        $service->approve($inquiry, $this->schulleitung);

        return $inquiry->fresh();
    }

    #[Test]
    public function eltern_waehlen_nur_eigene_kinder_aus(): void
    {
        $leo = $this->childFor([$this->mutter], ['first_name' => 'Leo', 'last_name' => 'Muster']);
        $fremd = $this->childFor([$this->makeParent()], ['first_name' => 'Fremdes', 'last_name' => 'Kind']);
        $assignment = $this->assignmentFor($this->childInquiry());

        $this->actingAs($this->mutter)->get(route('elternabfragen.show', $assignment))->assertOk()
            ->assertSee('Welches Geschwisterkind fährt mit?')
            ->assertSee('Leo Muster')
            ->assertDontSee('Fremdes Kind');

        $this->post(route('elternabfragen.submit', $assignment), ['answers' => ['geschwister' => (string) $fremd->id], 'confirm' => 1])
            ->assertSessionHasErrors('answers.geschwister');
        $this->assertSame(0, InquiryResponse::count());

        $this->post(route('elternabfragen.submit', $assignment), ['answers' => ['geschwister' => (string) $leo->id], 'confirm' => 1])
            ->assertSessionHasNoErrors();
        $response = InquiryResponse::firstOrFail();
        $this->assertSame(['id' => $leo->id, 'name' => 'Leo Muster'], $response->answers['geschwister']);
        $this->get(route('elternabfragen.show', $assignment))->assertSee('Leo Muster');
    }

    #[Test]
    public function app_api_liefert_kinder_als_optionen(): void
    {
        $assignment = $this->assignmentFor($this->childInquiry());

        $this->actingAs($this->mutter)->getJson(route('api.v1.elternabfragen.show', $assignment))->assertOk()
            ->assertJsonPath('data.fields.0.type', 'child')
            ->assertJsonPath('data.fields.0.options.0', ['value' => (string) $this->mia->id, 'label' => 'Mia Muster']);
    }

    #[Test]
    public function excel_spaltenkoepfe_werden_zu_feldern(): void
    {
        $result = app(TemplateImporter::class)->fromRows([
            ['Name', 'Vorname', 'Klasse', 'Fahrrad erlaubt', 'Abholung', 'E-Mail Eltern', 'Datum Rückgabe', 'Bemerkung'],
            ['Muster', 'Mia', '3a', 'Ja', 'Bus', 'a@example.org', '', ''],
            ['Beispiel', 'Ben', '3a', 'nein', 'Eltern', '', '', ''],
            ['Test', 'Tom', '3a', 'ja', 'Bus', '', '', ''],
        ]);

        $this->assertSame(['Name', 'Vorname', 'Klasse'], $result['skipped']);
        $fields = collect($result['fields'])->keyBy('label');
        $this->assertSame(['Fahrrad erlaubt', 'Abholung', 'E-Mail Eltern', 'Datum Rückgabe', 'Bemerkung'], $fields->keys()->all());
        $this->assertSame('yes_no', $fields['Fahrrad erlaubt']['type']);
        $this->assertSame('radio', $fields['Abholung']['type']);
        $this->assertSame(['Bus', 'Eltern'], $fields['Abholung']['options']);
        $this->assertSame('email', $fields['E-Mail Eltern']['format']);
        $this->assertSame('date', $fields['Datum Rückgabe']['type']);
        $this->assertSame('text', $fields['Bemerkung']['type']);
    }

    #[Test]
    public function excel_import_per_upload(): void
    {
        $file = UploadedFile::fake()->createWithContent('liste.csv', "Vorname,Allergien,Schwimmer\nMia,,Ja\nBen,,Nein\n");

        $this->actingAs($this->sekretariat)
            ->postJson(route('elternabfragen.admin.templates.import'), ['file' => $file])
            ->assertOk()
            ->assertJsonPath('skipped', ['Vorname'])
            ->assertJsonPath('fields.0.label', 'Allergien')
            ->assertJsonPath('fields.1.type', 'yes_no');

        $this->actingAs($this->sekretariat)
            ->postJson(route('elternabfragen.admin.templates.import'), ['file' => UploadedFile::fake()->createWithContent('leer.csv', "Name,Klasse\n")])
            ->assertStatus(422)->assertJsonValidationErrors('file');

        $this->actingAs($this->mutter)
            ->postJson(route('elternabfragen.admin.templates.import'), ['file' => $file])
            ->assertForbidden();
    }

    #[Test]
    public function vorlagen_vorschau_zeigt_elternansicht(): void
    {
        $version = $this->approvedVersion();
        $this->actingAs($this->sekretariat)->get(route('elternabfragen.admin.templates.preview', $version))->assertOk()
            ->assertSee('Vorschau aus Sicht der Eltern')
            ->assertSee('Darf Ihr Kind mit dem Fahrrad kommen?')
            ->assertSee('Max Mustermann')
            ->assertSee(route('elternabfragen.admin.templates.show', $version), false);

        $this->actingAs($this->mutter)->get(route('elternabfragen.admin.templates.preview', $version))->assertForbidden();
    }

    #[Test]
    public function vorschau_des_ungespeicherten_editors_speichert_nichts(): void
    {
        $count = InquiryTemplateVersion::count();

        $this->actingAs($this->sekretariat)->put(route('elternabfragen.admin.templates.preview-draft'), [
            'title' => 'Ausflug',
            'purpose' => 'Planung',
            'fields_json' => json_encode([
                ['type' => 'child', 'label' => 'Für welches Kind?', 'required' => true],
                ['type' => 'select', 'label' => 'Essen', 'options' => ['Fleisch', 'Vegetarisch']],
            ]),
        ])->assertOk()
            ->assertSee('Ausflug')
            ->assertSee('Für welches Kind?')
            ->assertSee('Erika Mustermann')
            ->assertSee('Vegetarisch');

        $this->assertSame($count, InquiryTemplateVersion::count());
    }
}
