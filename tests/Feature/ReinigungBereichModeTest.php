<?php

namespace Tests\Feature;

use App\Model\Group;
use App\Model\Reinigung;
use App\Model\User;
use App\Settings\ReinigungSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Feature-Tests für den gemeinsamen/getrennten Modus des Reinigungsplans
 * (siehe docs/Konzept_Reinigungsplan_Bereiche.md).
 */
class ReinigungBereichModeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Settings auf Standardwerte zurücksetzen (verhindert Kontamination bei
        // in-memory SQLite, das keine Transaktions-Isolation zwischen Tests bietet)
        (new ReinigungSetting)->fill([
            'separate_bereiche' => true,
            'combined_exclude_bereiche' => [],
            'skip_holidays' => false,
            'reminder_enabled' => false,
            'reminder_days_before' => 3,
            'reminder_email' => true,
            'reminder_push' => true,
            'reminder_time' => '08:00',
        ])->save();
    }

    private function editReinigungUser(): User
    {
        // changePassword explizit false setzen: die Factory
        // (Database\Factories\Model\UserFactory) würfelt diesen Wert sonst zufällig,
        // was über die PasswordExpired-Middleware zu einem flakey 302-Redirect
        // statt 200 führen würde.
        $user = User::factory()->create(['changePassword' => false]);
        Permission::findOrCreate('edit reinigung');
        $user->givePermissionTo('edit reinigung');

        return $user;
    }

    /** @test */
    public function index_falls_back_to_combined_mode_when_no_bereiche_are_configured(): void
    {
        $user = $this->editReinigungUser();

        // Gruppen ohne gepflegten Bereich (bereich = null)
        Group::factory()->create(['bereich' => null]);

        $response = $this->actingAs($user)->get('reinigung');

        $response->assertOk();
        $response->assertViewHas('Bereiche', function ($bereiche) {
            return $bereiche->count() === 1 && $bereiche->first() === Reinigung::BEREICH_GESAMT;
        });
        $response->assertViewHas('combinedMode', true);
    }

    /** @test */
    public function index_stays_separated_when_bereiche_are_configured_and_setting_enabled(): void
    {
        $user = $this->editReinigungUser();

        Group::factory()->create(['bereich' => 'Kindergarten']);
        Group::factory()->create(['bereich' => 'Hort']);

        $response = $this->actingAs($user)->get('reinigung');

        $response->assertOk();
        $response->assertViewHas('Bereiche', function ($bereiche) {
            return $bereiche->count() === 2 && $bereiche->contains('Kindergarten') && $bereiche->contains('Hort');
        });
        $response->assertViewHas('combinedMode', false);
    }

    /** @test */
    public function index_ignores_groups_with_empty_string_bereich(): void
    {
        // Regression: Gruppen mit bereich = '' (leerer String, nicht NULL) dürfen
        // keinen leeren Bereichs-Eintrag erzeugen - das führte zuvor zu kaputten
        // Links wie "reinigung//auto" (404).
        $user = $this->editReinigungUser();

        Group::factory()->create(['bereich' => 'Kindergarten']);
        Group::factory()->create(['bereich' => '']);

        $response = $this->actingAs($user)->get('reinigung');

        $response->assertOk();
        $response->assertViewHas('Bereiche', function ($bereiche) {
            return $bereiche->count() === 1 && $bereiche->first() === 'Kindergarten';
        });
        $response->assertViewHas('combinedMode', false);
    }

    /** @test */
    public function index_uses_combined_mode_when_setting_disabled_even_with_configured_bereiche(): void
    {
        $user = $this->editReinigungUser();

        Group::factory()->create(['bereich' => 'Kindergarten']);
        Group::factory()->create(['bereich' => 'Hort']);

        $settings = new ReinigungSetting;
        $settings->separate_bereiche = false;
        $settings->save();

        $response = $this->actingAs($user)->get('reinigung');

        $response->assertOk();
        $response->assertViewHas('Bereiche', function ($bereiche) {
            return $bereiche->count() === 1 && $bereiche->first() === Reinigung::BEREICH_GESAMT;
        });
        $response->assertViewHas('combinedMode', true);
    }

    /** @test */
    public function destroy_allows_deletion_in_combined_mode_even_if_stored_bereich_differs_from_route(): void
    {
        $user = $this->editReinigungUser();

        // Im gemeinsamen Modus wird beim automatischen Befüllen weiterhin der reale
        // Bereich des Datensatzes gespeichert - die Löschung darf davon unabhängig sein.
        $target = User::factory()->create();
        $reinigung = Reinigung::factory()->create([
            'bereich' => 'Kindergarten',
            'users_id' => $target->id,
        ]);

        (new ReinigungSetting)->fill(['separate_bereiche' => false])->save();

        $response = $this->actingAs($user)
            ->delete('reinigung/'.Reinigung::BEREICH_GESAMT.'/'.$reinigung->id.'/trash');

        $response->assertRedirect();
        $this->assertDatabaseMissing('reinigung', ['id' => $reinigung->id]);
    }

    /** @test */
    public function destroy_still_enforces_bereich_match_in_separated_mode(): void
    {
        $user = $this->editReinigungUser();

        Group::factory()->create(['bereich' => 'Kindergarten']);

        $target = User::factory()->create();
        $reinigung = Reinigung::factory()->create([
            'bereich' => 'Kindergarten',
            'users_id' => $target->id,
        ]);

        $response = $this->actingAs($user)
            ->delete('reinigung/Hort/'.$reinigung->id.'/trash');

        // Bei Bereichs-Mismatch im getrennten Modus liefert destroy() (wie im
        // Ursprungscode) keine Weiterleitung, sondern eine leere Antwort - der
        // Datensatz bleibt jedoch unangetastet.
        $response->assertOk();
        $this->assertDatabaseHas('reinigung', ['id' => $reinigung->id]);
    }

    /** @test */
    public function autocreate_start_uses_the_route_bereich_for_the_form_action_in_combined_mode(): void
    {
        // Regression: Im gemeinsamen Modus enthält der Gruppenpool auch Gruppen
        // ohne gepflegten Bereich (bereich = NULL). Die Auto-Fill-Formular-Action
        // darf sich NICHT aus dem (ggf. NULL-)Bereich der ersten Gruppe ableiten
        // ("reinigung//auto" -> 404), sondern muss den Routen-Bereich (hier:
        // Reinigung::BEREICH_GESAMT) verwenden.
        $user = $this->editReinigungUser();

        // Gruppe ohne Bereich, alphabetisch vor der Gruppe mit Bereich einsortiert.
        Group::factory()->create(['name' => 'AAA Ohne Bereich', 'bereich' => null]);
        Group::factory()->create(['name' => 'ZZZ Mit Bereich', 'bereich' => 'Kindergarten']);

        (new ReinigungSetting)->fill(['separate_bereiche' => false])->save();

        $response = $this->actingAs($user)
            ->get('reinigung/'.Reinigung::BEREICH_GESAMT.'/auto');

        $response->assertOk();
        $response->assertSee('reinigung/'.Reinigung::BEREICH_GESAMT.'/auto', false);
        $response->assertDontSee('reinigung//auto', false);
    }

    /** @test */
    public function autocreate_reuses_users_when_the_pool_is_smaller_than_the_number_of_slots(): void
    {
        $user = $this->editReinigungUser();

        $group = Group::factory()->create(['bereich' => 'Kindergarten']);

        // Nur eine Familie im Bereich, aber zwei Wochen mit je einer Aufgabe.
        $family = User::factory()->create();
        $family->groups()->attach($group);

        $task = \App\Model\ReinigungsTask::factory()->create();

        $response = $this->actingAs($user)->post('reinigung/Kindergarten/auto', [
            'aufgaben' => [$task->id],
            'exclude' => [],
            'start' => now()->startOfWeek()->format('Y-m-d'),
            'end' => now()->addWeeks(2)->endOfWeek()->format('Y-m-d'),
        ]);

        $response->assertRedirect(url('reinigung'));
        $response->assertSessionHas('type', 'success');

        // Die einzige Familie wurde in mehreren Wochen wiederverwendet statt
        // dass der Lauf mit "Nicht genügend Nutzer" abbricht.
        $this->assertGreaterThan(1, Reinigung::where('users_id', $family->id)->count());
    }

    /** @test */
    public function autocreate_shows_an_error_when_no_users_are_eligible_at_all(): void
    {
        $user = $this->editReinigungUser();

        Group::factory()->create(['bereich' => 'Kindergarten']);

        $task = \App\Model\ReinigungsTask::factory()->create();

        $response = $this->actingAs($user)->post('reinigung/Kindergarten/auto', [
            'aufgaben' => [$task->id],
            'exclude' => [],
            'start' => now()->startOfWeek()->format('Y-m-d'),
            'end' => now()->endOfWeek()->format('Y-m-d'),
        ]);

        $response->assertRedirect(url('reinigung'));
        $response->assertSessionHas('type', 'danger');
        $this->assertDatabaseCount('reinigung', 0);
    }

    /** @test */
    public function create_sorts_users_by_period_assignment_count_then_name(): void
    {
        $user = $this->editReinigungUser();

        $group = Group::factory()->create(['bereich' => 'Kindergarten']);

        $familyBusy = User::factory()->create(['name' => 'Max Aaron']);
        $familyBusy->groups()->attach($group);
        Reinigung::factory()->create([
            'bereich' => 'Kindergarten',
            'users_id' => $familyBusy->id,
            'datum' => now()->addWeek(),
        ]);

        $familyFreeZ = User::factory()->create(['name' => 'Max Zimmermann']);
        $familyFreeZ->groups()->attach($group);

        $familyFreeA = User::factory()->create(['name' => 'Max Abel']);
        $familyFreeA->groups()->attach($group);

        $response = $this->actingAs($user)
            ->get('reinigung/create/Kindergarten/'.now()->addWeeks(2)->format('Ymd'));

        $response->assertOk();

        $positionFreeA = strpos($response->getContent(), 'Abel');
        $positionFreeZ = strpos($response->getContent(), 'Zimmermann');
        $positionBusy = strpos($response->getContent(), 'Aaron');

        // Familien ohne Einsatz im aktuellen Zeitraum stehen vor der bereits
        // eingeteilten Familie, untereinander alphabetisch sortiert.
        $this->assertLessThan($positionBusy, $positionFreeA);
        $this->assertLessThan($positionBusy, $positionFreeZ);
        $this->assertLessThan($positionFreeZ, $positionFreeA);
    }
}
