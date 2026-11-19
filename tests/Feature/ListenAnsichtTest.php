<?php

namespace Tests\Feature;

use App\Model\Group;
use App\Model\Liste;
use App\Model\Listen_Eintragungen;
use App\Model\listen_termine;
use App\Model\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsFamilies;
use Tests\TestCase;

/**
 * Darstellung der Listen: Übersicht mit „Ihre Buchungen“, Termine nach Tagen,
 * eigene Buchung auf der Detailseite (inkl. Absagen) und Rückleitung zur Liste.
 */
class ListenAnsichtTest extends TestCase
{
    use BuildsFamilies;

    private User $parent;

    private User $owner;

    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Mail::fake();

        $this->group = Group::withoutGlobalScopes()->create(['name' => 'Klasse 3a', 'protected' => false]);
        $this->parent = $this->makeParent(['changePassword' => false, 'name' => 'Paula Parent']);
        $this->owner = $this->makeParent(['changePassword' => false, 'name' => 'Lena Lehrerin']);
        DB::table('group_user')->insert(['group_id' => $this->group->id, 'user_id' => $this->parent->id]);
    }

    private function liste(string $type, array $attributes = []): Liste
    {
        $liste = Liste::create(array_merge([
            'listenname' => $type === 'termin' ? 'Elterngespräche' : 'Sommerfest',
            'type' => $type,
            'besitzer' => $this->owner->id,
            'visible_for_all' => false,
            'active' => true,
            'ende' => now()->addWeek(),
            'multiple' => false,
            'make_new_entry' => false,
            'duration' => 15,
        ], $attributes));
        DB::table('group_listen')->insert(['group_id' => $this->group->id, 'liste_id' => $liste->id]);

        return $liste;
    }

    private function termin(Liste $liste, $zeit, ?int $reserviertFuer = null): listen_termine
    {
        return listen_termine::create([
            'listen_id' => $liste->id,
            'termin' => $zeit,
            'duration' => 15,
            'reserviert_fuer' => $reserviertFuer,
        ]);
    }

    #[Test]
    public function termine_are_grouped_by_day_with_free_counter(): void
    {
        $liste = $this->liste('termin');
        $tag = now()->addDays(3)->setTime(16, 0);
        $this->termin($liste, $tag);
        $this->termin($liste, $tag->copy()->addMinutes(15), $this->owner->id);

        $this->actingAs($this->parent)->get("/listen/{$liste->id}")
            ->assertOk()
            ->assertSee($tag->copy()->locale('de')->isoFormat('dddd, D. MMMM YYYY'))
            ->assertSee('1 von 2 frei')
            ->assertSee('1 von 2 Terminen frei')
            ->assertSee('16:00–16:15')
            ->assertSee('Nur freie Termine')
            ->assertDontSee('Lena Lehrerin');
    }

    #[Test]
    public function parents_do_not_see_past_termine(): void
    {
        $liste = $this->liste('termin');
        $this->termin($liste, now()->subDays(2)->setTime(9, 0));
        $this->termin($liste, now()->addDays(2)->setTime(10, 0));

        $this->actingAs($this->parent)->get("/listen/{$liste->id}")
            ->assertOk()
            ->assertDontSee('09:00–09:15')
            ->assertSee('10:00–10:15');
    }

    #[Test]
    public function own_booking_is_highlighted_on_detail_page_with_cancel_option(): void
    {
        $liste = $this->liste('termin');
        $termin = $this->termin($liste, now()->addDays(2)->setTime(11, 0), $this->parent->id);
        $this->termin($liste, now()->addDays(2)->setTime(11, 15));

        $this->actingAs($this->parent)->get("/listen/{$liste->id}")
            ->assertOk()
            ->assertSee('Ihr Termin')
            ->assertSee('Absagen')
            ->assertSee('Sie haben bereits einen Termin.')
            ->assertSee('Zum Umbuchen bitte zuerst absagen.');

        // Absagen von der Detailseite führt zurück auf die Detailseite
        $this->actingAs($this->parent)
            ->from("/listen/{$liste->id}")
            ->delete("listen/termine/absagen/{$termin->id}", ['text' => 'Krank'])
            ->assertRedirect("/listen/{$liste->id}");

        $this->assertNull($termin->fresh()->reserviert_fuer);
    }

    #[Test]
    public function reserving_redirects_back_to_the_list(): void
    {
        $liste = $this->liste('termin');
        $termin = $this->termin($liste, now()->addDays(2)->setTime(12, 0));

        $this->actingAs($this->parent)
            ->put("listen/termine/{$termin->id}")
            ->assertRedirect(url("listen/{$liste->id}"))
            ->assertSessionHas('Meldung', 'Termin wurde reserviert.');

        $this->assertSame($this->parent->id, (int) $termin->fresh()->reserviert_fuer);
    }

    #[Test]
    public function all_booked_shows_contact_hint(): void
    {
        $liste = $this->liste('termin');
        $this->termin($liste, now()->addDays(2)->setTime(12, 0), $this->owner->id);

        $this->actingAs($this->parent)->get("/listen/{$liste->id}")
            ->assertOk()
            ->assertSee('Alle Termine sind vergeben.')
            ->assertSee('Bei Fragen wenden Sie sich an Lena Lehrerin.');
    }

    #[Test]
    public function managers_see_names_and_management_toolbar(): void
    {
        $liste = $this->liste('termin');
        $this->termin($liste, now()->addDays(2)->setTime(12, 0), $this->parent->id);
        DB::table('group_user')->insert(['group_id' => $this->group->id, 'user_id' => $this->owner->id]);

        $this->actingAs($this->owner)->get("/listen/{$liste->id}")
            ->assertOk()
            ->assertSee('Paula Parent')
            ->assertSee('Neue Termine anlegen')
            ->assertSee('Vergangene anzeigen')
            ->assertSee('Druckansicht')
            ->assertSee('Absagen …');
    }

    #[Test]
    public function eintragsliste_separates_own_open_and_taken_entries(): void
    {
        $liste = $this->liste('eintrag', ['make_new_entry' => true]);
        Listen_Eintragungen::create(['listen_id' => $liste->id, 'eintragung' => 'Kuchen', 'created_by' => $this->owner->id, 'user_id' => $this->parent->id, 'bemerkung' => 'Apfelkuchen']);
        Listen_Eintragungen::create(['listen_id' => $liste->id, 'eintragung' => 'Salat', 'created_by' => $this->owner->id]);
        Listen_Eintragungen::create(['listen_id' => $liste->id, 'eintragung' => 'Getränke', 'created_by' => $this->owner->id, 'user_id' => $this->owner->id]);

        $this->actingAs($this->parent)->get("/listen/{$liste->id}")
            ->assertOk()
            ->assertSeeInOrder(['Ihr Eintrag', 'Kuchen', 'Apfelkuchen', 'Austragen', 'Noch offen', 'Salat', 'Bereits vergeben', 'Getränke'])
            ->assertSee('Sie haben sich bereits eingetragen.')
            ->assertSee('1 von 3 Einträgen offen')
            ->assertDontSee('Lena Lehrerin')
            // bereits eingetragen und nur einmal erlaubt: kein eigener Eintrag und kein Übernehmen mehr
            ->assertDontSee('Eigenen Eintrag hinzufügen')
            ->assertDontSee('Verbindlich übernehmen');
    }

    #[Test]
    public function eintragsliste_offers_own_entry_when_allowed(): void
    {
        $liste = $this->liste('eintrag', ['make_new_entry' => true]);

        $this->actingAs($this->parent)->get("/listen/{$liste->id}")
            ->assertOk()
            ->assertSee('Eigenen Eintrag hinzufügen')
            ->assertSee('Noch keine Einträge.');
    }

    #[Test]
    public function overview_shows_bookings_and_lists_by_type(): void
    {
        $terminListe = $this->liste('termin');
        $this->termin($terminListe, now()->addDays(2)->setTime(8, 30), $this->parent->id);
        $this->liste('eintrag', ['listenname' => 'Kuchenbasar']);

        $this->actingAs($this->parent)->get('/listen')
            ->assertOk()
            ->assertSeeInOrder(['Ihre Buchungen', 'Elterngespräche', '08:30', 'Terminlisten', 'Elterngespräche', 'gebucht', 'Eintragslisten', 'Kuchenbasar', 'offen'])
            ->assertSee('Ansehen')
            ->assertSee('Eintragen')
            ->assertSee('Absagen');
    }

    #[Test]
    public function overview_without_lists_shows_empty_state(): void
    {
        $this->actingAs($this->parent)->get('/listen')
            ->assertOk()
            ->assertSee('Aktuell gibt es keine Listen für Sie.')
            ->assertDontSee('Ihre Buchungen');
    }

    #[Test]
    public function owner_sees_own_inactive_list_in_management_section_only(): void
    {
        $this->owner->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate('create terminliste', 'web'));
        $this->liste('eintrag', ['listenname' => 'Geheime Planung', 'active' => false]);

        $this->actingAs($this->owner)->get('/listen')
            ->assertOk()
            ->assertSee('Verwaltung')
            ->assertSee('Geheime Planung')
            ->assertSee('inaktiv')
            ->assertSee('Neue Liste');

        $this->actingAs($this->parent)->get('/listen')
            ->assertOk()
            ->assertDontSee('Geheime Planung');
    }
}
