<?php

namespace Tests\Feature\Elternabfragen;

use App\Enums\InquiryAssignmentStatus;
use App\Enums\InquiryStatus;
use App\Enums\InquiryTemplateStatus;
use App\Mail\ElternabfrageMail;
use App\Model\Inquiry;
use App\Model\InquiryRecipient;
use App\Model\Notification;
use App\Services\Elternabfragen\InquiryService;
use App\Services\Elternabfragen\TemplateService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use LogicException;
use PHPUnit\Framework\Attributes\Test;

/**
 * Prozess A und B: geprüfte, versionierte Vorlagen und Freigabe vor Versand.
 */
class VorlagenUndFreigabeTest extends ElternabfragenTestCase
{
    #[Test]
    public function vorlage_wird_ueber_die_oberflaeche_angelegt_und_eingereicht(): void
    {
        $this->actingAs($this->sekretariat)
            ->post(route('elternabfragen.admin.templates.store'), [
                'title' => 'Stammdatenabfrage',
                'purpose' => 'Aktualisierung der Kontaktdaten.',
                'default_deadline_days' => 10,
                'fields_json' => json_encode($this->fields()),
                'submit_review' => 1,
            ])
            ->assertRedirect();

        $version = \App\Model\InquiryTemplateVersion::firstOrFail();
        $this->assertSame(InquiryTemplateStatus::InReview, $version->status);
        $this->assertSame(1, $version->version);
        $this->assertSame('fahrrad', $version->fields[1]['key']);
        $this->assertNotEmpty($version->fields[0]['key'], 'Schlüssel werden automatisch vergeben');
    }

    #[Test]
    public function felddefinition_wird_geprueft(): void
    {
        $this->actingAs($this->sekretariat)
            ->post(route('elternabfragen.admin.templates.store'), [
                'title' => 'Fehlerhaft',
                'purpose' => 'Test',
                'default_deadline_days' => 10,
                'fields_json' => json_encode([
                    ['type' => 'radio', 'label' => 'Nur eine Option', 'options' => ['A']],
                    ['type' => 'consent', 'label' => 'Ohne Text'],
                ]),
            ])
            ->assertSessionHasErrors(['fields.0', 'fields.1']);
    }

    #[Test]
    public function vier_augen_prinzip_und_zusatzpruefung_bei_sensiblen_inhalten(): void
    {
        $templates = app(TemplateService::class);
        $this->actingAs($this->sekretariat);
        $version = $templates->create(['title' => 'Gesundheit', 'purpose' => 'Notfall', 'is_sensitive' => true, 'fields' => $this->fields()], $this->sekretariat);
        $templates->submit($version);

        $this->sekretariat->givePermissionTo('approve elternabfragen');
        try {
            $templates->approve($version, $this->sekretariat, true, null);
            $this->fail('Vier-Augen-Prinzip verletzt');
        } catch (ValidationException) {
        }

        try {
            $templates->approve($version, $this->schulleitung, false, null);
            $this->fail('Zusatzprüfung fehlt');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('privacy_checked', $e->errors());
        }

        $templates->approve($version, $this->schulleitung, true, 'geprüft');
        $version->refresh();
        $this->assertSame(InquiryTemplateStatus::Approved, $version->status);
        $this->assertSame($this->schulleitung->id, $version->privacy_checked_by);
    }

    #[Test]
    public function freigegebene_version_ist_unveraenderlich_und_neue_version_ersetzt_sie(): void
    {
        $v1 = $this->approvedVersion();

        $this->expectExceptionObject(new LogicException('Freigegebene Vorlagenversionen sind unveränderlich. Bitte eine neue Version anlegen.'));
        try {
            $v1->update(['purpose' => 'anders']);
        } finally {
            $templates = app(TemplateService::class);
            $v2 = $templates->newVersion($v1->template, $this->sekretariat);
            $this->assertSame(2, $v2->version);
            $this->assertSame(InquiryTemplateStatus::Draft, $v2->status);
            $templates->submit($v2);
            $templates->approve($v2, $this->schulleitung, true, null);
            $this->assertSame(InquiryTemplateStatus::Superseded, $v1->fresh()->status);
            $this->assertSame($v2->id, $v1->template->fresh()->approvedVersion->id);
        }
    }

    #[Test]
    public function abfrage_wird_erst_nach_freigabe_versendet_und_erzeugt_auftraege_je_kind(): void
    {
        $ohneKonto = $this->childFor([], ['first_name' => 'Ben', 'class_id' => $this->klasse->id]);
        $andereKlasse = $this->childFor([$this->makeParent()], ['class_id' => \App\Model\Group::factory()->create()->id]);

        $inquiry = $this->runningInquiry();

        $this->assertSame(InquiryStatus::Running, $inquiry->status);
        $this->assertSame(2, $inquiry->expected_children);
        $this->assertSame(2, $inquiry->expected_accounts, 'nur Sorgeberechtigte, nicht die Oma');
        $this->assertSame(2, $inquiry->assignments()->count());
        $this->assertDatabaseMissing('inquiry_assignments', ['child_id' => $andereKlasse->id]);

        $mia = $this->assignmentFor($inquiry);
        $this->assertEqualsCanonicalizing([$this->mutter->id, $this->vater->id], InquiryRecipient::where('assignment_id', $mia->id)->pluck('user_id')->all());
        $this->assertNotNull($mia->notified_at);
        Mail::assertSent(ElternabfrageMail::class, 2);
        $this->assertSame(1, Notification::where('user_id', $this->mutter->id)->where('type', 'Elternabfragen')->count());

        $ben = $this->assignmentFor($inquiry, $ohneKonto);
        $this->assertSame(InquiryAssignmentStatus::Problem, $ben->status);
        $this->assertTrue(Notification::where('user_id', $this->sekretariat->id)->where('important', true)->exists(), 'verantwortliche Person wird informiert');
    }

    #[Test]
    public function geplante_abfrage_startet_erst_zum_startzeitpunkt(): void
    {
        $inquiry = $this->runningInquiry(['starts_at' => now()->addDay()]);
        $this->assertSame(InquiryStatus::Scheduled, $inquiry->status);
        $this->assertSame(0, $inquiry->assignments()->count());

        $this->travel(2)->days();
        $this->artisan('elternabfragen:process')->assertSuccessful();

        $this->assertSame(InquiryStatus::Running, $inquiry->fresh()->status);
        $this->assertSame(1, $inquiry->assignments()->count());
    }

    #[Test]
    public function gestartete_abfrage_ist_inhaltlich_gesperrt_frist_aenderung_wird_protokolliert(): void
    {
        $inquiry = $this->runningInquiry();

        try {
            $inquiry->update(['title' => 'Still geändert']);
            $this->fail('Inhalt darf nicht still geändert werden');
        } catch (LogicException) {
        }

        $this->actingAs($this->sekretariat)
            ->post(route('elternabfragen.admin.inquiries.deadline', $inquiry), ['deadline_at' => now()->addDays(20)->toDateTimeString()])
            ->assertSessionHasErrors('reason');

        $this->post(route('elternabfragen.admin.inquiries.deadline', $inquiry), ['deadline_at' => now()->addDays(20)->toDateTimeString(), 'reason' => 'Ferien'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('inquiry_events', ['inquiry_id' => $inquiry->id, 'action' => 'inquiry.deadline_changed', 'note' => 'Ferien']);
    }

    #[Test]
    public function nachzuegler_werden_beim_abgleich_ergaenzt(): void
    {
        $inquiry = $this->runningInquiry();
        $neu = $this->childFor([$this->makeParent()], ['class_id' => $this->klasse->id]);

        $this->assertSame(1, app(InquiryService::class)->syncTargets($inquiry));
        $this->assertDatabaseHas('inquiry_assignments', ['inquiry_id' => $inquiry->id, 'child_id' => $neu->id]);
        $this->assertSame(0, app(InquiryService::class)->syncTargets($inquiry));
    }

    #[Test]
    public function verwaltungsseiten_sind_erreichbar_und_geschuetzt(): void
    {
        $inquiry = $this->runningInquiry();

        $this->actingAs($this->sekretariat);
        $this->get(route('elternabfragen.admin.index'))->assertOk()->assertSee('Fahrraderlaubnis 2026');
        $this->get(route('elternabfragen.admin.inquiries.show', $inquiry))->assertOk()->assertSee('Mia Muster');
        $this->get(route('elternabfragen.admin.inquiries.preview', $inquiry))->assertOk()->assertSee('Vorschau aus Sicht der Eltern');
        $this->get(route('elternabfragen.admin.templates.index'))->assertOk();
        $this->get(route('elternabfragen.admin.templates.create'))->assertOk();
        $this->get(route('elternabfragen.admin.inquiries.create'))->assertOk();
        $this->get(route('elternabfragen.admin.assignments.show', $this->assignmentFor($inquiry)))->assertOk();
        $this->get(route('elternabfragen.admin.exports.create', $inquiry))->assertOk();

        $this->actingAs($this->schulleitung);
        $this->get(route('elternabfragen.admin.approvals'))->assertOk();
        $this->get(route('elternabfragen.admin.settings'))->assertOk();

        $this->actingAs($this->mutter);
        $this->get(route('elternabfragen.admin.index'))->assertForbidden();
        $this->get(route('elternabfragen.admin.inquiries.show', $inquiry))->assertForbidden();
    }

    #[Test]
    public function inaktives_modul_sperrt_alle_routen(): void
    {
        $this->activateModule('Elternabfragen')->update(['options' => ['active' => false, 'rights' => []]]);
        \Illuminate\Support\Facades\Cache::flush();

        $this->actingAs($this->sekretariat)->get(route('elternabfragen.admin.index'))->assertNotFound();
        $this->actingAs($this->mutter)->get(route('elternabfragen.index'))->assertNotFound();
    }

    #[Test]
    public function formulare_ueber_die_oberflaeche_bis_zum_start(): void
    {
        $version = $this->approvedVersion();

        $this->actingAs($this->sekretariat)
            ->post(route('elternabfragen.admin.inquiries.store'), [
                'template_version_id' => $version->id,
                'title' => 'Per Formular',
                'starts_at' => now()->subMinute()->format('Y-m-d\TH:i'),
                'deadline_at' => now()->addWeek()->format('Y-m-d\TH:i'),
                'target_type' => 'groups',
                'target_group_ids' => [$this->klasse->id],
                'correction_window_days' => 7,
                'notify_mail' => 1,
                'submit_approval' => 1,
            ])
            ->assertRedirect();

        $inquiry = Inquiry::where('title', 'Per Formular')->firstOrFail();
        $this->assertSame(InquiryStatus::PendingApproval, $inquiry->status);

        $this->actingAs($this->schulleitung)
            ->post(route('elternabfragen.admin.inquiries.approve', $inquiry))
            ->assertSessionHasErrors('confirm');
        $this->post(route('elternabfragen.admin.inquiries.approve', $inquiry), ['confirm' => 1])->assertSessionHasNoErrors();

        $this->assertSame(InquiryStatus::Running, $inquiry->fresh()->status);
    }

    #[Test]
    public function eingereichte_vorlage_zeigt_pruefbereich_oben(): void
    {
        $templates = app(TemplateService::class);
        $this->actingAs($this->sekretariat);
        $version = $templates->create(['title' => 'Ausflug', 'purpose' => 'Planung', 'fields' => $this->fields()], $this->sekretariat);
        $templates->submit($version);

        $this->actingAs($this->schulleitung)->get(route('elternabfragen.admin.templates.show', $version))
            ->assertOk()
            ->assertSee('id="pruefung"', false)
            ->assertSee(route('elternabfragen.admin.templates.approve', $version), false);

        $this->sekretariat->givePermissionTo('approve elternabfragen');
        $this->actingAs($this->sekretariat->fresh())->get(route('elternabfragen.admin.templates.show', $version))
            ->assertOk()
            ->assertSee('Vier-Augen-Prinzip')
            ->assertDontSee(route('elternabfragen.admin.templates.approve', $version), false);
    }
}
