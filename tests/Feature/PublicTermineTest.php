<?php

namespace Tests\Feature;

use App\Model\Group;
use App\Model\Termin;
use App\Model\User;
use App\Settings\GeneralSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Öffentliche Termine: Einstellung, Formular, HTML-Seite und ICS-Feed.
 */
class PublicTermineTest extends TestCase
{
    use RefreshDatabase;

    private function setPublicEnabled(bool $enabled): void
    {
        $settings = app(GeneralSetting::class);
        $settings->public_termine_enabled = $enabled;
        $settings->save();
    }

    private function terminAnleger(): User
    {
        Permission::findOrCreate('edit termin', 'web');
        $user = User::factory()->create(['password_changed_at' => now()]);
        $user->givePermissionTo('edit termin');

        return $user;
    }

    private function terminDaten(Group $group): array
    {
        return [
            'terminname' => 'Sommerfest',
            'start' => now()->addDays(3)->format('Y-m-d H:i'),
            'ende' => now()->addDays(3)->addHours(2)->format('Y-m-d H:i'),
            'gruppen' => [$group->id],
            'public' => 1,
        ];
    }

    #[Test]
    public function html_seite_zeigt_nur_kommende_oeffentliche_termine(): void
    {
        $this->setPublicEnabled(true);

        Termin::create(['terminname' => 'Tag der offenen Tür', 'start' => now()->addDays(2), 'ende' => now()->addDays(2)->addHour(), 'public' => true]);
        Termin::create(['terminname' => 'Interner Elternabend', 'start' => now()->addDays(2), 'ende' => now()->addDays(2)->addHour(), 'public' => false]);
        Termin::create(['terminname' => 'Vergangenes Fest', 'start' => now()->subDays(10), 'ende' => now()->subDays(10)->addHour(), 'public' => true]);

        $this->get('termine/oeffentlich')
            ->assertOk()
            ->assertSee('Tag der offenen Tür')
            ->assertDontSee('Interner Elternabend')
            ->assertDontSee('Vergangenes Fest')
            ->assertSee('ical/publicEvents', false);
    }

    #[Test]
    public function html_seite_enthaelt_schema_org_events_als_json_ld(): void
    {
        $this->setPublicEnabled(true);
        $settings = app(GeneralSetting::class);
        $settings->public_termine_location_name = 'Grundschule Musterstadt';
        $settings->public_termine_location_street = 'Schulweg 1';
        $settings->public_termine_location_postal_code = '01234';
        $settings->public_termine_location_city = 'Musterstadt';
        $settings->save();

        $termin = Termin::create(['terminname' => 'Tag der offenen Tür <b>', 'start' => now()->addDays(2)->setTime(10, 0), 'ende' => now()->addDays(2)->setTime(12, 0), 'public' => true]);
        $ganztags = Termin::create(['terminname' => 'Projekttag', 'start' => now()->addDays(5)->startOfDay(), 'ende' => now()->addDays(5)->endOfDay(), 'fullDay' => true, 'public' => true]);

        $html = $this->get('termine/oeffentlich')->assertOk()->getContent();

        $this->assertSame(1, preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m));
        $this->assertStringNotContainsString('<b>', $m[1], 'JSON-LD muss HTML-Tags escapen');
        $jsonLd = json_decode($m[1], true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('https://schema.org', $jsonLd['@context']);
        $this->assertCount(2, $jsonLd['@graph']);

        $event = $jsonLd['@graph'][0];
        $this->assertSame('Event', $event['@type']);
        $this->assertSame('Tag der offenen Tür <b>', $event['name']);
        $this->assertSame($termin->start->timezone('Europe/Berlin')->toIso8601String(), $event['startDate']);
        $this->assertStringEndsWith('#termin-'.$termin->id, $event['url']);
        $this->assertSame('https://schema.org/EventScheduled', $event['eventStatus']);
        $this->assertSame('Grundschule Musterstadt', $event['location']['name']);
        $this->assertSame([
            '@type' => 'PostalAddress',
            'streetAddress' => 'Schulweg 1',
            'postalCode' => '01234',
            'addressLocality' => 'Musterstadt',
            'addressCountry' => 'DE',
        ], $event['location']['address']);

        // Ganztägige Termine nur mit Datum
        $this->assertSame($ganztags->start->toDateString(), $jsonLd['@graph'][1]['startDate']);

        // Semantisches HTML mit maschinenlesbaren Zeitangaben und Ankern
        $this->assertStringContainsString('id="termin-'.$termin->id.'"', $html);
        $this->assertStringContainsString('<time datetime="'.$event['startDate'].'"', $html);
    }

    #[Test]
    public function html_seite_und_ics_sind_bei_deaktivierter_einstellung_nicht_erreichbar(): void
    {
        $this->setPublicEnabled(false);

        $this->get('termine/oeffentlich')->assertNotFound();
        $this->get('ical/publicEvents')->assertNotFound();
    }

    #[Test]
    public function ics_feed_liefert_oeffentliche_termine(): void
    {
        $this->setPublicEnabled(true);
        Termin::create(['terminname' => 'Tag der offenen Tür', 'start' => now()->addDays(2), 'ende' => now()->addDays(2)->addHour(), 'public' => true]);

        $response = $this->get('ical/publicEvents')->assertOk();
        $this->assertStringContainsString('Tag der offenen Tür', $response->getContent());
    }

    #[Test]
    public function oeffentlich_option_wird_bei_deaktivierter_einstellung_ausgeblendet(): void
    {
        $user = $this->terminAnleger();

        $this->setPublicEnabled(false);
        $this->actingAs($user)->get('termine/create')->assertOk()->assertDontSee('name="public"', false);

        $this->setPublicEnabled(true);
        $this->actingAs($user)->get('termine/create')->assertOk()->assertSee('name="public"', false);
    }

    #[Test]
    public function termin_wird_bei_deaktivierter_einstellung_nicht_oeffentlich_gespeichert(): void
    {
        $user = $this->terminAnleger();
        $group = Group::factory()->create();
        $this->setPublicEnabled(false);

        $this->actingAs($user)->post('termine', $this->terminDaten($group))->assertRedirect();

        $this->assertDatabaseHas('termine', ['terminname' => 'Sommerfest', 'public' => false]);
    }

    #[Test]
    public function termin_wird_bei_aktiver_einstellung_oeffentlich_gespeichert(): void
    {
        $user = $this->terminAnleger();
        $group = Group::factory()->create();
        $this->setPublicEnabled(true);

        $this->actingAs($user)->post('termine', $this->terminDaten($group))->assertRedirect();

        $this->assertDatabaseHas('termine', ['terminname' => 'Sommerfest', 'public' => true]);
    }
}
