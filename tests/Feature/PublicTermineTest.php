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
