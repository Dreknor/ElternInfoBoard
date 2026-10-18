<?php

namespace Tests\Feature\Elternabfragen;

use App\Enums\GuardianRelation;
use App\Model\Child;
use App\Model\Group;
use App\Model\Inquiry;
use App\Model\InquiryAssignment;
use App\Model\InquiryTemplateVersion;
use App\Model\User;
use App\Services\Elternabfragen\InquiryService;
use App\Services\Elternabfragen\TemplateService;
use App\Services\Family\FamilyResolver;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\ActivatesModules;
use Tests\Concerns\BuildsFamilies;
use Tests\TestCase;

/**
 * Gemeinsame Szenarien: Sekretariat legt an, Schulleitung gibt frei (Vier-Augen),
 * Klasse 3a mit Kind Mia (Mutter + Vater sorgeberechtigt, Oma nur Information).
 */
abstract class ElternabfragenTestCase extends TestCase
{
    use ActivatesModules, BuildsFamilies;

    protected User $sekretariat;

    protected User $schulleitung;

    protected Group $klasse;

    protected User $mutter;

    protected User $vater;

    protected User $oma;

    protected Child $mia;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->useResolver(FamilyResolver::MODE_CHILD_CENTRIC);
        $this->activateModule('Elternabfragen');

        $this->sekretariat = $this->makeParent(['name' => 'Sekretariat']);
        $this->sekretariat->givePermissionTo(['manage elternabfragen', 'view elternabfragen answers', 'export elternabfragen']);
        $this->schulleitung = $this->makeParent(['name' => 'Schulleitung']);
        $this->schulleitung->givePermissionTo(['approve elternabfragen', 'view elternabfragen answers']);

        $this->klasse = Group::factory()->create(['name' => 'Klasse 3a', 'protected' => false, 'owner_id' => null]);
        $this->mutter = $this->makeParent(['name' => 'Mutter Muster']);
        $this->vater = $this->makeParent(['name' => 'Vater Muster']);
        $this->oma = $this->makeParent(['name' => 'Oma Muster']);
        $this->mia = $this->childFor([$this->mutter, $this->vater], ['first_name' => 'Mia', 'last_name' => 'Muster', 'class_id' => $this->klasse->id]);
        $this->linkGuardian($this->mia, $this->oma, GuardianRelation::Grandparent);
    }

    protected function fields(): array
    {
        return [
            ['type' => 'info', 'label' => 'Fahrrad'],
            ['key' => 'fahrrad', 'type' => 'yes_no', 'label' => 'Darf Ihr Kind mit dem Fahrrad kommen?', 'required' => true],
            ['key' => 'foto', 'type' => 'consent', 'label' => 'Fotos', 'required' => true, 'consent_text' => 'Ich willige ein, dass Fotos veröffentlicht werden.'],
            ['key' => 'telefon', 'type' => 'text', 'label' => 'Notfalltelefon', 'required' => true, 'format' => 'phone'],
            ['key' => 'hinweis', 'type' => 'textarea', 'label' => 'Hinweise', 'required' => false, 'max_length' => 200],
        ];
    }

    protected function approvedVersion(array $overrides = []): InquiryTemplateVersion
    {
        $templates = app(TemplateService::class);
        $this->actingAs($this->sekretariat);
        $version = $templates->create($overrides + [
            'title' => 'Fahrraderlaubnis',
            'purpose' => 'Wir benötigen die Erlaubnis für den Schulweg.',
            'default_deadline_days' => 14,
            'fields' => $this->fields(),
        ], $this->sekretariat);
        $templates->submit($version);
        $this->actingAs($this->schulleitung);
        $templates->approve($version, $this->schulleitung, true, null);

        return $version->fresh();
    }

    protected function runningInquiry(array $overrides = []): Inquiry
    {
        $version = $this->approvedVersion();
        $service = app(InquiryService::class);

        $this->actingAs($this->sekretariat);
        $inquiry = $service->create($overrides + [
            'template_version_id' => $version->id,
            'title' => 'Fahrraderlaubnis 2026',
            'intro' => 'Bitte ausfüllen.',
            'starts_at' => now()->subMinute(),
            'deadline_at' => now()->addDays(14),
            'target_type' => Inquiry::TARGET_GROUPS,
            'target_group_ids' => [$this->klasse->id],
            'notify_mail' => true,
            'notify_push' => true,
            'auto_reminders' => true,
            'correction_window_days' => 7,
        ], $this->sekretariat);
        $service->submit($inquiry);

        $this->actingAs($this->schulleitung);
        $service->approve($inquiry, $this->schulleitung);

        return $inquiry->fresh();
    }

    protected function assignmentFor(Inquiry $inquiry, ?Child $child = null): InquiryAssignment
    {
        return InquiryAssignment::where('inquiry_id', $inquiry->id)->where('child_id', ($child ?? $this->mia)->id)->firstOrFail();
    }

    protected function validAnswers(): array
    {
        return ['fahrrad' => 'yes', 'foto' => 'denied', 'telefon' => '0351 123456', 'hinweis' => 'Keine'];
    }
}
