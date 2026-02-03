<?php

namespace Tests\Feature;

use App\Model\Group;
use App\Model\Liste;
use App\Model\listen_termine;
use App\Model\Post;
use App\Model\Rueckmeldungen;
use App\Model\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TerminlisteRueckmeldungTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected User $admin;

    protected Group $group;

    protected Liste $liste;

    protected Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        // Erstelle Berechtigungen
        Permission::create(['name' => 'create posts', 'guard_name' => 'web']);
        Permission::create(['name' => 'manage rueckmeldungen', 'guard_name' => 'web']);
        Permission::create(['name' => 'edit terminliste', 'guard_name' => 'web']);

        // Erstelle Nutzer
        $this->user = User::factory()->create();
        $this->user->givePermissionTo('create posts');

        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo(['create posts', 'manage rueckmeldungen', 'edit terminliste']);

        // Erstelle Gruppe
        $this->group = Group::factory()->create();
        $this->user->groups()->attach($this->group);
        $this->admin->groups()->attach($this->group);

        // Erstelle Terminliste
        $this->liste = Liste::factory()->create([
            'type' => 'termin',
            'active' => 1,
            'besitzer' => $this->admin->id,
            'ende' => Carbon::now()->addMonths(2),
            'multiple' => false,
            'duration' => 60,
        ]);
        $this->liste->groups()->attach($this->group);

        // Erstelle freie Termine
        listen_termine::factory()->count(5)->create([
            'listen_id' => $this->liste->id,
            'termin' => Carbon::now()->addDays(rand(1, 30)),
            'reserviert_fuer' => null,
        ]);

        // Erstelle Post
        $this->post = Post::factory()->create([
            'author' => $this->user->id,
            'released' => 1,
            'archiv_ab' => Carbon::now()->addWeek(),
        ]);
        $this->post->groups()->attach($this->group);
    }

    /** @test */
    public function user_can_see_terminliste_option_when_creating_post()
    {
        $response = $this->actingAs($this->user)->get('/posts/create');

        $response->assertStatus(200);
        $response->assertSee('Ja, Terminliste');
    }

    /** @test */
    public function user_can_create_terminliste_rueckmeldung()
    {
        $startDate = Carbon::today()->addDays(1);
        $endDate = Carbon::today()->addDays(7);

        $response = $this->actingAs($this->user)->post("/rueckmeldung/{$this->post->id}/create/terminliste", [
            'liste_id' => $this->liste->id,
            'terminliste_start_date' => $startDate->format('Y-m-d'),
            'terminliste_end_date' => $endDate->format('Y-m-d'),
            'ende' => Carbon::now()->addWeek()->format('Y-m-d'),
            'pflicht' => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('type', 'success');

        $this->assertDatabaseHas('rueckmeldungen', [
            'post_id' => $this->post->id,
            'type' => 'terminliste',
            'liste_id' => $this->liste->id,
            'terminliste_start_date' => $startDate->format('Y-m-d'),
            'terminliste_end_date' => $endDate->format('Y-m-d'),
            'pflicht' => 1,
        ]);
    }

    /** @test */
    public function terminliste_rueckmeldung_requires_valid_liste()
    {
        $response = $this->actingAs($this->user)->post("/rueckmeldung/{$this->post->id}/create/terminliste", [
            'liste_id' => 99999, // Nicht existierende Liste
            'terminliste_start_date' => Carbon::today()->format('Y-m-d'),
            'terminliste_end_date' => Carbon::today()->addWeek()->format('Y-m-d'),
            'ende' => Carbon::now()->addWeek()->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors('liste_id');
    }

    /** @test */
    public function terminliste_rueckmeldung_requires_valid_date_range()
    {
        // Ende vor Start
        $response = $this->actingAs($this->user)->post("/rueckmeldung/{$this->post->id}/create/terminliste", [
            'liste_id' => $this->liste->id,
            'terminliste_start_date' => Carbon::today()->addWeek()->format('Y-m-d'),
            'terminliste_end_date' => Carbon::today()->format('Y-m-d'), // Vor Start
            'ende' => Carbon::now()->addWeek()->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors('terminliste_end_date');
    }

    /** @test */
    public function user_can_see_free_termine_in_nachricht()
    {
        $rueckmeldung = Rueckmeldungen::create([
            'post_id' => $this->post->id,
            'type' => 'terminliste',
            'liste_id' => $this->liste->id,
            'terminliste_start_date' => Carbon::today(),
            'terminliste_end_date' => Carbon::today()->addMonth(),
            'ende' => Carbon::now()->addWeek(),
            'text' => 'Terminbuchung',
            'empfaenger' => $this->user->email,
            'pflicht' => 0,
        ]);

        $response = $this->actingAs($this->user)->get('/home');

        $response->assertStatus(200);
        $response->assertSee($this->liste->listenname);
        $response->assertSee('Verfügbare Termine zum Buchen');
    }

    /** @test */
    public function user_can_book_termin_from_nachricht()
    {
        $termin = $this->liste->termine()->whereNull('reserviert_fuer')->first();

        Rueckmeldungen::create([
            'post_id' => $this->post->id,
            'type' => 'terminliste',
            'liste_id' => $this->liste->id,
            'terminliste_start_date' => Carbon::today(),
            'terminliste_end_date' => Carbon::today()->addMonth(),
            'ende' => Carbon::now()->addWeek(),
            'text' => 'Terminbuchung',
            'empfaenger' => $this->user->email,
            'pflicht' => 0,
        ]);

        $response = $this->actingAs($this->user)
            ->put("/listen/termine/{$termin->id}");

        $response->assertRedirect();
        $response->assertSessionHas('type', 'success');

        $this->assertDatabaseHas('listen_termine', [
            'id' => $termin->id,
            'reserviert_fuer' => $this->user->id,
        ]);
    }

    /** @test */
    public function user_sees_own_booked_termine_in_nachricht()
    {
        $termin = $this->liste->termine()->first();
        $termin->update(['reserviert_fuer' => $this->user->id]);

        Rueckmeldungen::create([
            'post_id' => $this->post->id,
            'type' => 'terminliste',
            'liste_id' => $this->liste->id,
            'terminliste_start_date' => Carbon::today(),
            'terminliste_end_date' => Carbon::today()->addMonth(),
            'ende' => Carbon::now()->addWeek(),
            'text' => 'Terminbuchung',
            'empfaenger' => $this->user->email,
            'pflicht' => 0,
        ]);

        $response = $this->actingAs($this->user)->get('/home');

        $response->assertStatus(200);
        $response->assertSee('Ihre gebuchten Termine');
        $response->assertSee($termin->termin->format('d.m.Y'));
    }

    /** @test */
    public function user_cannot_book_multiple_termine_when_multiple_is_false()
    {
        $termin1 = $this->liste->termine()->first();
        $termin1->update(['reserviert_fuer' => $this->user->id]);

        $termin2 = $this->liste->termine()->skip(1)->first();

        Rueckmeldungen::create([
            'post_id' => $this->post->id,
            'type' => 'terminliste',
            'liste_id' => $this->liste->id,
            'terminliste_start_date' => Carbon::today(),
            'terminliste_end_date' => Carbon::today()->addMonth(),
            'ende' => Carbon::now()->addWeek(),
            'text' => 'Terminbuchung',
            'empfaenger' => $this->user->email,
            'pflicht' => 0,
        ]);

        $response = $this->actingAs($this->user)->get('/home');

        $response->assertStatus(200);
        $response->assertDontSee('Verfügbare Termine zum Buchen');
        $response->assertSee('Sie haben bereits einen Termin gebucht');
    }

    /** @test */
    public function user_can_book_multiple_termine_when_multiple_is_true()
    {
        $this->liste->update(['multiple' => true]);

        $termin1 = $this->liste->termine()->first();
        $termin1->update(['reserviert_fuer' => $this->user->id]);

        Rueckmeldungen::create([
            'post_id' => $this->post->id,
            'type' => 'terminliste',
            'liste_id' => $this->liste->id,
            'terminliste_start_date' => Carbon::today(),
            'terminliste_end_date' => Carbon::today()->addMonth(),
            'ende' => Carbon::now()->addWeek(),
            'text' => 'Terminbuchung',
            'empfaenger' => $this->user->email,
            'pflicht' => 0,
        ]);

        $response = $this->actingAs($this->user)->get('/home');

        $response->assertStatus(200);
        $response->assertSee('Verfügbare Termine zum Buchen');
        $response->assertSee('Mehrfachbuchungen erlaubt');
    }

    /** @test */
    public function user_cannot_book_after_deadline()
    {
        $termin = $this->liste->termine()->whereNull('reserviert_fuer')->first();

        Rueckmeldungen::create([
            'post_id' => $this->post->id,
            'type' => 'terminliste',
            'liste_id' => $this->liste->id,
            'terminliste_start_date' => Carbon::today(),
            'terminliste_end_date' => Carbon::today()->addMonth(),
            'ende' => Carbon::yesterday(), // Frist abgelaufen
            'text' => 'Terminbuchung',
            'empfaenger' => $this->user->email,
            'pflicht' => 0,
        ]);

        $response = $this->actingAs($this->user)->get('/home');

        $response->assertStatus(200);
        $response->assertSee('Buchungsfrist abgelaufen');
        $response->assertDontSee('Buchen');
    }

    /** @test */
    public function only_termine_in_date_range_are_shown()
    {
        // Termin außerhalb des Zeitraums
        $terminOutside = listen_termine::factory()->create([
            'listen_id' => $this->liste->id,
            'termin' => Carbon::now()->addMonths(2),
            'reserviert_fuer' => null,
        ]);

        Rueckmeldungen::create([
            'post_id' => $this->post->id,
            'type' => 'terminliste',
            'liste_id' => $this->liste->id,
            'terminliste_start_date' => Carbon::today(),
            'terminliste_end_date' => Carbon::today()->addMonth(),
            'ende' => Carbon::now()->addWeek(),
            'text' => 'Terminbuchung',
            'empfaenger' => $this->user->email,
            'pflicht' => 0,
        ]);

        $response = $this->actingAs($this->user)->get('/home');

        $response->assertStatus(200);
        // Termin außerhalb des Zeitraums sollte nicht angezeigt werden
        $response->assertDontSee($terminOutside->termin->format('d.m.Y'));
    }

    /** @test */
    public function admin_can_see_terminliste_statistics()
    {
        $rueckmeldung = Rueckmeldungen::create([
            'post_id' => $this->post->id,
            'type' => 'terminliste',
            'liste_id' => $this->liste->id,
            'terminliste_start_date' => Carbon::today(),
            'terminliste_end_date' => Carbon::today()->addMonth(),
            'ende' => Carbon::now()->addWeek(),
            'text' => 'Terminbuchung',
            'empfaenger' => $this->user->email,
            'pflicht' => 0,
        ]);

        // Buche einige Termine
        $this->liste->termine()->take(2)->each(function ($termin) {
            $termin->update(['reserviert_fuer' => $this->user->id]);
        });

        $response = $this->actingAs($this->admin)
            ->get("/rueckmeldungen/{$rueckmeldung->id}/show");

        $response->assertStatus(200);
        $response->assertSee('Gebucht');
        $response->assertSee('Frei');
        $response->assertSee($this->liste->listenname);
    }

    /** @test */
    public function terminliste_rueckmeldung_shows_in_index()
    {
        $rueckmeldung = Rueckmeldungen::create([
            'post_id' => $this->post->id,
            'type' => 'terminliste',
            'liste_id' => $this->liste->id,
            'terminliste_start_date' => Carbon::today(),
            'terminliste_end_date' => Carbon::today()->addMonth(),
            'ende' => Carbon::now()->addWeek(),
            'text' => 'Terminbuchung',
            'empfaenger' => $this->user->email,
            'pflicht' => 0,
        ]);

        $response = $this->actingAs($this->admin)->get('/rueckmeldungen');

        $response->assertStatus(200);
        $response->assertSee('fa-calendar-check');
        $response->assertSee($this->liste->listenname);
    }

    /** @test */
    public function user_with_sorg2_sees_both_bookings()
    {
        $sorg2 = User::factory()->create();
        $this->user->update(['sorg2' => $sorg2->id]);
        $sorg2->groups()->attach($this->group);

        $termin1 = $this->liste->termine()->first();
        $termin1->update(['reserviert_fuer' => $this->user->id]);

        $termin2 = $this->liste->termine()->skip(1)->first();
        $termin2->update(['reserviert_fuer' => $sorg2->id]);

        Rueckmeldungen::create([
            'post_id' => $this->post->id,
            'type' => 'terminliste',
            'liste_id' => $this->liste->id,
            'terminliste_start_date' => Carbon::today(),
            'terminliste_end_date' => Carbon::today()->addMonth(),
            'ende' => Carbon::now()->addWeek(),
            'text' => 'Terminbuchung',
            'empfaenger' => $this->user->email,
            'pflicht' => 0,
        ]);

        $response = $this->actingAs($this->user)->get('/home');

        $response->assertStatus(200);
        $response->assertSee($termin1->termin->format('d.m.Y'));
        $response->assertSee($termin2->termin->format('d.m.Y'));
    }

    /** @test */
    public function only_active_listen_with_free_termine_shown_in_dropdown()
    {
        // Inaktive Liste
        $inactiveListe = Liste::factory()->create([
            'type' => 'termin',
            'active' => 0,
            'besitzer' => $this->admin->id,
            'ende' => Carbon::now()->addMonths(2),
        ]);

        // Liste ohne freie Termine
        $fullListe = Liste::factory()->create([
            'type' => 'termin',
            'active' => 1,
            'besitzer' => $this->admin->id,
            'ende' => Carbon::now()->addMonths(2),
        ]);
        listen_termine::factory()->create([
            'listen_id' => $fullListe->id,
            'termin' => Carbon::now()->addDays(5),
            'reserviert_fuer' => $this->user->id, // Gebucht
        ]);

        // Simuliere Post-Erstellung mit Terminliste als Rückmeldung
        $response = $this->actingAs($this->user)->post('/posts', [
            'header' => 'Test Nachricht',
            'news' => 'Test Inhalt',
            'type' => 'info',
            'gruppen' => [$this->group->id],
            'archiv_ab' => Carbon::now()->addWeek()->format('Y-m-d'),
            'released' => 1,
            'rueckmeldung' => 'terminliste',
        ]);

        $response->assertStatus(200);

        // Nur aktive Liste mit freien Terminen sollte verfügbar sein
        $response->assertSee($this->liste->listenname);
        $response->assertDontSee($inactiveListe->listenname);
        $response->assertDontSee($fullListe->listenname);
    }

    /** @test */
    public function terminliste_can_be_deleted_from_edit_view()
    {
        $rueckmeldung = Rueckmeldungen::create([
            'post_id' => $this->post->id,
            'type' => 'terminliste',
            'liste_id' => $this->liste->id,
            'terminliste_start_date' => Carbon::today(),
            'terminliste_end_date' => Carbon::today()->addMonth(),
            'ende' => Carbon::now()->addWeek(),
            'text' => 'Terminbuchung',
            'empfaenger' => $this->user->email,
            'pflicht' => 0,
        ]);

        $response = $this->actingAs($this->user)->get("/posts/edit/{$this->post->id}");

        $response->assertStatus(200);
        $response->assertSee('Terminlisten-Rückmeldung');
        $response->assertSee($this->liste->listenname);
        $response->assertSee('rueckmeldungLoeschen');
    }
}
