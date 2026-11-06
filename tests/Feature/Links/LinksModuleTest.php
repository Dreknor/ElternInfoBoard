<?php

namespace Tests\Feature\Links;

use App\Model\Group;
use App\Model\Link;
use App\Model\Module;
use App\Model\User;
use App\Support\LinkUrl;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\ActivatesModules;
use Tests\Concerns\BuildsFamilies;
use Tests\TestCase;

/**
 * Modul „Links“: Sichtbarkeit (alle / Gruppen), Verwaltung, Modulsperre, Web und API.
 */
class LinksModuleTest extends TestCase
{
    use ActivatesModules, BuildsFamilies;

    private User $admin;

    private User $parent;

    private User $other;

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
        $this->other = $this->makeParent(['name' => 'Andere']);

        $this->klasse = Group::factory()->create(['name' => 'Klasse 2b', 'protected' => false, 'owner_id' => null]);
        $this->parent->groups()->attach($this->klasse);
    }

    private function link(array $attributes = [], array $groups = []): Link
    {
        $link = Link::create($attributes + [
            'title' => 'Schulhomepage',
            'url' => 'https://www.schule.example.de',
            'for_all' => true,
            'is_active' => true,
        ]);
        $link->groups()->sync($groups);

        return $link;
    }

    #[Test]
    public function migration_legt_modul_inaktiv_und_recht_an(): void
    {
        $this->assertTrue(Permission::where('name', 'edit links')->exists());
        Module::where('setting', 'Links')->delete();
        $migration = require database_path('migrations/2026_11_06_000002_create_links_module.php');
        $migration->up();

        $module = Module::where('setting', 'Links')->firstOrFail();
        $this->assertFalse((bool) $module->options['active']);
        $this->assertSame('links', $module->options['nav']['link']);
        $this->assertSame(['edit links'], $module->options['adm-nav']['adm-rights']);
    }

    #[Test]
    public function nutzer_sieht_links_fuer_alle_und_eigene_gruppen(): void
    {
        $this->link(['title' => 'Für alle']);
        $this->link(['title' => 'Nur Klasse 2b', 'for_all' => false], [$this->klasse->id]);
        $this->link(['title' => 'Ausgeblendet', 'is_active' => false]);

        $this->actingAs($this->parent)->get(route('links.index'))
            ->assertOk()->assertSee('Für alle')->assertSee('Nur Klasse 2b')->assertDontSee('Ausgeblendet');

        $this->actingAs($this->other)->get(route('links.index'))
            ->assertOk()->assertSee('Für alle')->assertDontSee('Nur Klasse 2b');
    }

    #[Test]
    public function externe_links_oeffnen_in_neuem_fenster_interne_nicht(): void
    {
        $this->link(['title' => 'Extern', 'url' => 'https://extern.example.org/seite']);
        $this->link(['title' => 'Intern', 'url' => '/termine']);

        $html = $this->actingAs($this->parent)->get(route('links.index'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('#href="https://extern\.example\.org/seite"\s+target="_blank" rel="noopener noreferrer"#', $html);
        $this->assertStringContainsString('href="'.url('/termine').'"', $html);
        $this->assertDoesNotMatchRegularExpression('#href="'.preg_quote(url('/termine'), '#').'"\s+target=#', $html);
    }

    #[Test]
    public function inaktives_modul_sperrt_web_und_api(): void
    {
        Module::where('setting', 'Links')->update(['options' => json_encode(['active' => '0', 'rights' => []])]);
        Cache::forget('modules');

        $this->actingAs($this->admin)->get(route('links.index'))->assertNotFound();
        $this->actingAs($this->admin)->get(route('links.admin.index'))->assertNotFound();
        Sanctum::actingAs($this->parent);
        $this->getJson('/api/v1/links')->assertNotFound();
    }

    #[Test]
    public function verwaltung_nur_mit_recht(): void
    {
        $link = $this->link();

        $this->actingAs($this->parent)->get(route('links.admin.index'))->assertForbidden();
        $this->actingAs($this->parent)->post(route('links.admin.store'), ['title' => 'X', 'url' => 'https://x.de', 'for_all' => 1])->assertForbidden();
        $this->actingAs($this->parent)->delete(route('links.admin.destroy', $link))->assertForbidden();
        $this->assertSame(1, Link::count());
    }

    #[Test]
    public function verwaltung_legt_link_fuer_gruppe_an(): void
    {
        $this->actingAs($this->admin)->get(route('links.admin.create'))->assertOk()->assertSee('Neuer Link');

        $this->post(route('links.admin.store'), [
            'title' => 'Mensa',
            'url' => 'www.mensa.example.de/bestellen',
            'description' => 'Essen bestellen',
            'category' => 'Schulalltag',
            'icon' => 'fas fa-utensils',
            'gruppen' => [$this->klasse->id],
            'pinned' => 1,
            'is_active' => 1,
        ])->assertRedirect(route('links.admin.index'))->assertSessionHas('type', 'success');

        $link = Link::firstOrFail();
        $this->assertSame('https://www.mensa.example.de/bestellen', $link->url);
        $this->assertFalse($link->for_all);
        $this->assertTrue($link->pinned);
        $this->assertSame([$this->klasse->id], $link->groups->pluck('id')->all());
        $this->assertSame($this->admin->id, $link->created_by);

        $this->get(route('links.admin.index'))->assertOk()->assertSee('Mensa')->assertSee('Klasse 2b')->assertSee('Schulalltag');
    }

    #[Test]
    public function ohne_gruppe_und_ohne_fuer_alle_wird_abgelehnt(): void
    {
        $this->actingAs($this->admin)->post(route('links.admin.store'), [
            'title' => 'Niemand', 'url' => 'https://x.example.de', 'is_active' => 1,
        ])->assertSessionHasErrors('gruppen');

        $this->assertSame(0, Link::count());
    }

    #[Test]
    #[DataProvider('unsichereAdressen')]
    public function unsichere_adressen_werden_abgelehnt(string $url): void
    {
        $this->actingAs($this->admin)->post(route('links.admin.store'), [
            'title' => 'Böse', 'url' => $url, 'for_all' => 1, 'is_active' => 1,
        ])->assertSessionHasErrors('url');

        $this->assertSame(0, Link::count());
    }

    public static function unsichereAdressen(): array
    {
        return [
            'javascript' => ['javascript:alert(1)'],
            'javascript mit Großbuchstaben' => ['JaVaScRiPt:alert(1)'],
            'data' => ['data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg=='],
            'protokollrelativ' => ['//boese.example.com'],
            'Backslash' => ['/\\boese.example.com'],
            'Zugangsdaten' => ['https://bank.example.de@boese.example.com'],
            'Leerzeichen' => ['https://schule.de/mein dokument'],
            'ftp' => ['ftp://dateien.example.de'],
            'kein Host' => ['https://'],
            'Unsinn' => ['bitte hier klicken'],
        ];
    }

    #[Test]
    public function erlaubte_adressarten(): void
    {
        foreach (['https://schule.example.de/a?b=c#d', 'http://localhost:8000', 'mailto:info@schule.example.de', 'tel:+49 351 123456', '/termine'] as $url) {
            $this->assertTrue(LinkUrl::isValid(LinkUrl::normalize($url)), $url);
        }
        $this->assertSame('tel:+49351123456', LinkUrl::normalize('tel:+49 351 123456'));
        $this->assertSame('mail', LinkUrl::type('mailto:info@schule.example.de'));
        $this->assertSame('phone', LinkUrl::type('tel:+49351123456'));
        $this->assertSame('internal', LinkUrl::type('/termine'));
        $this->assertSame('internal', LinkUrl::type(rtrim(config('app.url'), '/').'/listen'));
        $this->assertSame('external', LinkUrl::type('https://extern.example.org'));
        $this->assertSame('schule.example.de', LinkUrl::display('https://www.schule.example.de/x'));
    }

    #[Test]
    public function bearbeiten_ausblenden_verschieben_und_loeschen(): void
    {
        $a = $this->link(['title' => 'A', 'position' => 0]);
        $b = $this->link(['title' => 'B', 'position' => 1]);
        $anders = $this->link(['title' => 'Verein', 'category' => 'Förderverein', 'position' => 2]);

        $this->actingAs($this->admin)->get(route('links.admin.edit', $a))->assertOk()->assertSee('Link bearbeiten');
        $this->put(route('links.admin.update', $a), [
            'title' => 'A neu', 'url' => 'https://a.example.de', 'gruppen' => [$this->klasse->id], 'is_active' => 1,
        ])->assertRedirect(route('links.admin.index'));
        $a->refresh();
        $this->assertSame('A neu', $a->title);
        $this->assertFalse($a->for_all);
        $this->assertSame([$this->klasse->id], $a->groups->pluck('id')->all());

        // Verschieben bleibt innerhalb der Kategorie: B nach oben tauscht mit A, nicht mit „Verein“.
        $this->post(route('links.admin.move', $b), ['direction' => 'up'])->assertRedirect();
        $this->assertSame(['B', 'A neu', 'Verein'], Link::query()->ordered()->pluck('title')->all());
        $this->post(route('links.admin.move', $anders), ['direction' => 'up'])->assertRedirect();
        $this->assertSame(['B', 'A neu', 'Verein'], Link::query()->ordered()->pluck('title')->all());

        $this->post(route('links.admin.toggle', $b))->assertRedirect();
        $this->assertFalse($b->fresh()->is_active);

        $this->delete(route('links.admin.destroy', $b))->assertRedirect(route('links.admin.index'));
        $this->assertNull(Link::find($b->id));
        $this->assertDatabaseMissing('link_group', ['link_id' => $b->id]);
    }

    #[Test]
    public function angeheftete_links_erscheinen_auf_dem_dashboard(): void
    {
        $this->link(['title' => 'Angeheftet', 'pinned' => true]);
        $this->link(['title' => 'Nicht angeheftet']);

        $this->actingAs($this->parent)->get(route('dashboard'))
            ->assertOk()->assertSee('Wichtige Links')->assertSee('Angeheftet')->assertDontSee('Nicht angeheftet');
    }

    #[Test]
    public function api_liefert_sichtbare_links_mit_kategorien(): void
    {
        $this->link(['title' => 'Mensa', 'url' => '/termine', 'category' => 'Schulalltag', 'icon' => 'fas fa-utensils', 'position' => 1]);
        $this->link(['title' => 'Homepage', 'position' => 0, 'pinned' => true]);
        $this->link(['title' => 'Gruppe', 'for_all' => false, 'position' => 2], [$this->klasse->id]);

        Sanctum::actingAs($this->other);
        $this->getJson('/api/v1/links')->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.title', 'Homepage')
            ->assertJsonPath('data.0.type', 'external')
            ->assertJsonPath('data.0.host', 'schule.example.de')
            ->assertJsonPath('data.0.pinned', true)
            ->assertJsonPath('data.0.icon_key', 'external-link-alt')
            ->assertJsonPath('data.1.url', url('/termine'))
            ->assertJsonPath('data.1.type', 'internal')
            ->assertJsonPath('data.1.icon', 'fas fa-utensils')
            ->assertJsonPath('data.1.icon_key', 'utensils')
            ->assertJsonPath('meta.categories', [null, 'Schulalltag']);

        Sanctum::actingAs($this->parent);
        $this->getJson('/api/v1/links')->assertOk()->assertJsonCount(3, 'data');

        $this->getJson('/api/v1/parent/dashboard')->assertOk()
            ->assertJsonCount(1, 'data.links')
            ->assertJsonPath('data.links.0.title', 'Homepage');
    }

    #[Test]
    public function dashboard_api_liefert_keine_links_bei_inaktivem_modul(): void
    {
        $this->link(['title' => 'Homepage', 'pinned' => true]);
        Module::where('setting', 'Links')->update(['options' => json_encode(['active' => '0', 'rights' => []])]);

        Sanctum::actingAs($this->parent);
        $this->getJson('/api/v1/parent/dashboard')->assertOk()->assertJsonPath('data.links', []);
        $this->getJson('/api/v1/bootstrap')->assertOk();
    }
}
