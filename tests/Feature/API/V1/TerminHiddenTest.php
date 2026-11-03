<?php

namespace Tests\Feature\API\V1;

use App\Model\Group;
use App\Model\Termin;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

/**
 * B-102: Eltern blenden Termine aus, die sie nicht betreffen – gilt in App, Web und iCal-Feed.
 */
class TerminHiddenTest extends AppApiTestCase
{
    private function terminIn(Group $group, string $name): Termin
    {
        $termin = Termin::create([
            'terminname' => $name,
            'start' => now()->addDays(3)->setTime(18, 0),
            'ende' => now()->addDays(3)->setTime(20, 0),
            'fullDay' => false,
            'public' => false,
        ]);
        DB::table('group_termine')->insert(['group_id' => $group->id, 'termin_id' => $termin->id]);

        return $termin;
    }

    /** @test */
    public function parent_can_hide_and_show_a_termin_via_the_app_api(): void
    {
        $group = $this->group();
        $user = $this->parentIn($group);
        $neueltern = $this->terminIn($group, 'Elternabend Neueltern');
        $this->terminIn($group, 'Sommerfest');
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/termine')->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.hidden_count', 0);

        $this->postJson("/api/v1/termine/{$neueltern->id}/hide")->assertOk()
            ->assertJsonPath('data.hidden', true);
        // Doppelt ausblenden ist harmlos (Wiederholung über Idempotency/Offline).
        $this->postJson("/api/v1/termine/{$neueltern->id}/hide")->assertOk();

        $this->getJson('/api/v1/termine')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Sommerfest')
            ->assertJsonPath('meta.hidden_count', 1);
        $this->getJson('/api/v1/termine/hidden')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 'termin-'.$neueltern->id);
        $this->getJson('/api/v1/parent/dashboard')->assertOk()
            ->assertJsonCount(1, 'data.termine');

        $this->deleteJson("/api/v1/termine/{$neueltern->id}/hide")->assertOk()
            ->assertJsonPath('data.hidden', false);
        $this->getJson('/api/v1/termine')->assertOk()->assertJsonCount(2, 'data');
    }

    /** @test */
    public function hiding_only_affects_the_own_account(): void
    {
        $group = $this->group();
        $a = $this->parentIn($group);
        $b = $this->parentIn($group);
        $termin = $this->terminIn($group, 'Elternabend Neueltern');

        Sanctum::actingAs($a);
        $this->postJson("/api/v1/termine/{$termin->id}/hide")->assertOk();

        Sanctum::actingAs($b);
        $this->getJson('/api/v1/termine')->assertOk()->assertJsonCount(1, 'data');
    }

    /** @test */
    public function foreign_termine_cannot_be_hidden(): void
    {
        $user = $this->parentIn($this->group());
        $fremd = $this->terminIn($this->group('Andere'), 'Fremder Termin');
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/termine/{$fremd->id}/hide")->assertNotFound();
        $this->assertDatabaseCount('termin_hidden', 0);
    }

    /** @test */
    public function hidden_termine_are_missing_in_the_ical_feed(): void
    {
        $group = $this->group();
        $user = $this->parentIn($group, ['releaseCalendar' => true]);
        $hidden = $this->terminIn($group, 'Elternabend Neueltern');
        $this->terminIn($group, 'Sommerfest');
        $user->hiddenTermine()->attach($hidden->id);

        $this->get($user->uuid.'/ical')->assertOk()
            ->assertSee('Sommerfest')
            ->assertDontSee('Neueltern');
    }

    /** @test */
    public function parent_can_hide_and_show_a_termin_on_the_website(): void
    {
        $group = $this->group();
        $user = $this->parentIn($group);
        $termin = $this->terminIn($group, 'Elternabend Neueltern');
        $this->terminIn($group, 'Sommerfest');
        $this->actingAs($user);
        $this->withoutVite();

        $this->from('/termine')->post("/termine/{$termin->id}/ausblenden")->assertRedirect('/termine');
        $this->assertTrue($user->hiddenTermine()->whereKey($termin->id)->exists());

        // Die Erfolgsmeldung nennt den Termin – daher die Liste selbst prüfen.
        $response = $this->get('/termine')->assertOk()->assertSee('1 Termin ausgeblendet');
        $this->assertSame(['Sommerfest'], $response->viewData('termine')->pluck('terminname')->all());
        $this->get('/termine?ausgeblendet=1')->assertOk()
            ->assertSee('Elternabend Neueltern')
            ->assertSee('Wieder anzeigen');

        $this->from('/termine')->delete("/termine/{$termin->id}/ausblenden")->assertRedirect('/termine');
        $this->get('/termine')->assertOk()->assertSee('Elternabend Neueltern');
    }
}
