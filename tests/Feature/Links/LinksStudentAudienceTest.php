<?php

namespace Tests\Feature\Links;

use App\Model\Child;
use App\Model\Group;
use App\Model\Link;
use App\Model\User;
use App\Services\Students\StudentAccountService;
use App\Support\StudentAccounts;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\ActivatesModules;
use Tests\Concerns\BuildsFamilies;
use Tests\TestCase;

/**
 * Modul „Links“ mit Schülerkonten: Zielgruppe (eltern | eltern_schueler | schueler),
 * Schüleransicht und Schüler-API. Ohne Schülerkonten-Modul keine Spur davon.
 */
class LinksStudentAudienceTest extends TestCase
{
    use ActivatesModules, BuildsFamilies;

    private User $admin;

    private User $parent;

    private Group $klasse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->activateModule('Links');
        Cache::forget('modules');

        $this->admin = $this->makeParent(['name' => 'Sekretariat']);
        $this->admin->givePermissionTo('edit links');
        $this->parent = $this->makeParent(['name' => 'Mutter']);
        $this->klasse = Group::factory()->create(['name' => 'Klasse 4a', 'protected' => false, 'owner_id' => null]);
        $this->parent->groups()->attach($this->klasse);
    }

    private function enableStudents(): User
    {
        config(['modules.unlocked' => [StudentAccounts::MODULE]]);
        $this->activateModule(StudentAccounts::MODULE);
        StudentAccounts::flush();
        Cache::forget('modules');

        $child = Child::factory()->create(['class_id' => $this->klasse->id]);
        [$student] = app(StudentAccountService::class)->createForChild($child);

        return $student;
    }

    private function link(string $title, string $zielgruppe, array $attributes = [], array $groups = []): Link
    {
        $link = new Link($attributes + [
            'title' => $title,
            'url' => 'https://'.strtolower(str_replace(' ', '-', $title)).'.example.de',
            'for_all' => $groups === [],
            'is_active' => true,
        ]);
        $link->zielgruppe = $zielgruppe;
        $link->save();
        $link->groups()->sync($groups);

        return $link;
    }

    private function formData(array $overrides = []): array
    {
        return $overrides + [
            'title' => 'Lernplattform',
            'url' => 'https://lernen.example.de',
            'for_all' => '1',
            'is_active' => '1',
        ];
    }

    #[Test]
    public function ohne_schuelerkonten_gibt_es_kein_feld_und_neue_links_sind_fuer_eltern(): void
    {
        $this->actingAs($this->admin)->get(route('links.admin.create'))
            ->assertOk()->assertDontSee('Sichtbar für')->assertDontSee('nur Schüler');

        $this->actingAs($this->admin)->post(route('links.admin.store'), $this->formData(['zielgruppe' => 'schueler']))
            ->assertRedirect(route('links.admin.index'));

        $this->assertSame(StudentAccounts::ZIELGRUPPE_ELTERN, Link::sole()->zielgruppe);
    }

    #[Test]
    public function mit_schuelerkonten_waehlt_die_verwaltung_die_zielgruppe(): void
    {
        $this->enableStudents();

        $this->actingAs($this->admin)->get(route('links.admin.create'))
            ->assertOk()->assertSee('Sichtbar für')->assertSee('nur Schüler');

        $this->actingAs($this->admin)->post(route('links.admin.store'), $this->formData(['zielgruppe' => 'schueler']))
            ->assertRedirect(route('links.admin.index'));

        $link = Link::query()->withStudentAudience()->sole();
        $this->assertSame(StudentAccounts::ZIELGRUPPE_SCHUELER, $link->zielgruppe);

        // Verwaltung sieht und bearbeitet den Schülerlink, mit Hinweis
        $this->actingAs($this->admin)->get(route('links.admin.index'))->assertOk()->assertSee('Lernplattform')->assertSee('nur Schüler');
        $this->actingAs($this->admin)->get(route('links.admin.edit', $link))->assertOk();
        $this->actingAs($this->admin)->put(route('links.admin.update', $link), $this->formData(['title' => 'Neu', 'zielgruppe' => 'eltern_schueler']))
            ->assertRedirect();
        $this->assertSame('eltern_schueler', $link->fresh()->zielgruppe);

        $this->actingAs($this->admin)->post(route('links.admin.store'), $this->formData(['zielgruppe' => 'alle']))
            ->assertSessionHasErrors('zielgruppe');
    }

    #[Test]
    public function bearbeiten_ohne_feld_laesst_die_zielgruppe_unveraendert(): void
    {
        $this->enableStudents();
        $link = $this->link('Schülerseite', StudentAccounts::ZIELGRUPPE_SCHUELER);

        $this->actingAs($this->admin)->put(route('links.admin.update', $link), $this->formData(['title' => 'Umbenannt']))->assertRedirect();

        $this->assertSame(StudentAccounts::ZIELGRUPPE_SCHUELER, $link->fresh()->zielgruppe);
    }

    #[Test]
    public function eltern_sehen_nie_links_nur_fuer_schueler(): void
    {
        $this->enableStudents();
        $this->link('Elternportal', StudentAccounts::ZIELGRUPPE_ELTERN, ['pinned' => true]);
        $this->link('Für beide', StudentAccounts::ZIELGRUPPE_ELTERN_SCHUELER, ['pinned' => true]);
        $this->link('Nur Kinder', StudentAccounts::ZIELGRUPPE_SCHUELER, ['pinned' => true]);

        $this->actingAs($this->parent)->get(route('links.index'))
            ->assertOk()->assertSee('Elternportal')->assertSee('Für beide')->assertDontSee('Nur Kinder');
        $this->actingAs($this->parent)->get(route('dashboard'))->assertDontSee('Nur Kinder');

        Sanctum::actingAs($this->parent);
        $titles = collect($this->getJson('/api/v1/links')->assertOk()->json('data'))->pluck('title')->all();
        $this->assertEqualsCanonicalizing(['Elternportal', 'Für beide'], $titles);
        $dashboard = collect($this->getJson('/api/v1/parent/dashboard')->assertOk()->json('data.links'))->pluck('title')->all();
        $this->assertContains('Für beide', $dashboard);
        $this->assertNotContains('Nur Kinder', $dashboard);
    }

    #[Test]
    public function schueler_sehen_links_fuer_schueler_ihrer_klasse(): void
    {
        $student = $this->enableStudents();
        $andere = Group::factory()->create(['name' => 'Klasse 1b', 'protected' => false, 'owner_id' => null]);

        $this->link('Elternportal', StudentAccounts::ZIELGRUPPE_ELTERN);
        $this->link('Mensa', StudentAccounts::ZIELGRUPPE_ELTERN_SCHUELER, ['category' => 'Alltag', 'pinned' => true]);
        $this->link('Lernspiele 4a', StudentAccounts::ZIELGRUPPE_SCHUELER, [], [$this->klasse->id]);
        $this->link('Lernspiele 1b', StudentAccounts::ZIELGRUPPE_SCHUELER, [], [$andere->id]);
        $this->link('Versteckt', StudentAccounts::ZIELGRUPPE_SCHUELER, ['is_active' => false]);
        $this->link('Elternseite intern', StudentAccounts::ZIELGRUPPE_SCHUELER, ['url' => '/termine']);
        $this->link('Stundenplan intern', StudentAccounts::ZIELGRUPPE_SCHUELER, ['url' => '/schueler/stundenplan']);

        $this->actingAs($student)->get(route('student.links'))->assertOk()
            ->assertSee('Mensa')->assertSee('Alltag')->assertSee('Lernspiele 4a')->assertSee('Stundenplan intern')
            ->assertDontSee('Elternportal')->assertDontSee('Lernspiele 1b')->assertDontSee('Versteckt')->assertDontSee('Elternseite intern')
            ->assertSee('rel="noopener noreferrer"', false);

        $this->actingAs($student)->get(route('student.home'))->assertOk()
            ->assertSee('Wichtige Links')->assertSee('Mensa')->assertSee(route('student.links'));
    }

    #[Test]
    public function schueler_api_liefert_links_und_bereich(): void
    {
        $student = $this->enableStudents();
        $this->link('Mensa', StudentAccounts::ZIELGRUPPE_ELTERN_SCHUELER, ['category' => 'Alltag', 'icon' => 'fas fa-utensils']);
        $this->link('Elternportal', StudentAccounts::ZIELGRUPPE_ELTERN);

        Sanctum::actingAs($student, [StudentAccounts::TOKEN_ABILITY]);
        $this->getJson('/api/v1/student/bootstrap')->assertOk()->assertJsonFragment(['links']);
        $this->assertContains('links', $this->getJson('/api/v1/student/bootstrap')->json('data.bereiche'));

        $this->getJson('/api/v1/student/links')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.titel', 'Mensa')
            ->assertJsonPath('data.0.kategorie', 'Alltag')
            ->assertJsonPath('data.0.symbol', 'utensils')
            ->assertJsonPath('data.0.art', 'external')
            ->assertJsonPath('data.0.url', 'https://mensa.example.de');
    }

    #[Test]
    public function ohne_links_modul_haben_schueler_keine_links(): void
    {
        $student = $this->enableStudents();
        $this->deactivateLinks();

        $this->actingAs($student)->get(route('student.links'))->assertNotFound();
        $this->actingAs($student)->get(route('student.home'))->assertOk()->assertDontSee(route('student.links'));

        Sanctum::actingAs($student, [StudentAccounts::TOKEN_ABILITY]);
        $this->assertNotContains('links', $this->getJson('/api/v1/student/bootstrap')->assertOk()->json('data.bereiche'));
        $this->getJson('/api/v1/student/links')->assertNotFound();
    }

    #[Test]
    public function schueler_erreichen_die_eltern_und_verwaltungsseiten_nicht(): void
    {
        $student = $this->enableStudents();
        $link = $this->link('Mensa', StudentAccounts::ZIELGRUPPE_ELTERN_SCHUELER);

        foreach ([route('links.index'), route('links.admin.index'), route('links.admin.edit', $link)] as $url) {
            $status = $this->actingAs($student)->get($url)->getStatusCode();
            $this->assertTrue(in_array($status, [302, 403, 404], true), "$url: $status");
        }
        Sanctum::actingAs($student, [StudentAccounts::TOKEN_ABILITY]);
        $this->assertFalse($this->getJson('/api/v1/links')->isOk());
    }

    private function deactivateLinks(): void
    {
        $module = \App\Model\Module::where('setting', 'Links')->firstOrFail();
        $options = $module->options;
        $options['active'] = false;
        $module->options = $options;
        $module->save();
        Cache::forget('modules');
    }
}
