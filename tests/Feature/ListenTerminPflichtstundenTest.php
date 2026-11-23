<?php

namespace Tests\Feature;

use App\Model\Group;
use App\Model\Liste;
use App\Model\listen_termine;
use App\Model\Pflichtstunde;
use App\Model\User;
use App\Settings\PflichtstundenSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsFamilies;
use Tests\TestCase;

/**
 * Automatische Pflichtstunden bei Buchung in Listen mit „creates_pflichtstunden“.
 * Regression: ListenService::reserveTermin buchte per Query-Builder → Observer feuerte nicht.
 */
class ListenTerminPflichtstundenTest extends TestCase
{
    use BuildsFamilies;

    private User $parent;

    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Mail::fake();
        $this->setAutocreate(true);

        $this->group = Group::withoutGlobalScopes()->create(['name' => 'K1', 'protected' => false]);
        $this->parent = $this->makeParent(['changePassword' => false]);
        $this->familyOf($this->parent);
        DB::table('group_user')->insert(['group_id' => $this->group->id, 'user_id' => $this->parent->id]);
    }

    private function setAutocreate(bool $value): void
    {
        $s = app(PflichtstundenSetting::class);
        $s->listen_autocreate = $value;
        $s->save();
    }

    private function termin(array $liste = [], array $termin = []): listen_termine
    {
        $l = Liste::create(array_merge([
            'listenname' => 'Gartentag', 'type' => 'termin', 'besitzer' => $this->makeParent()->id,
            'visible_for_all' => false, 'active' => true, 'ende' => now()->addWeek(),
            'multiple' => false, 'creates_pflichtstunden' => true,
        ], $liste));
        DB::table('group_listen')->insert(['group_id' => $this->group->id, 'liste_id' => $l->id]);

        return listen_termine::create(array_merge(['listen_id' => $l->id, 'termin' => now()->addDays(2), 'duration' => 90], $termin));
    }

    private function pflichtstunden(listen_termine $t)
    {
        return Pflichtstunde::withoutGlobalScopes()->withTrashed()->where('listen_termin_id', $t->id)->get();
    }

    #[Test]
    public function web_buchung_legt_pflichtstunde_an(): void
    {
        $t = $this->termin();
        $this->actingAs($this->parent)->put("listen/termine/{$t->id}");

        $this->assertSame($this->parent->id, (int) $t->fresh()->reserviert_fuer);
        $p = $this->pflichtstunden($t);
        $this->assertCount(1, $p);
        $this->assertSame($this->parent->id, (int) $p[0]->user_id);
        $this->assertSame(90, (int) $p[0]->start->diffInMinutes($p[0]->end));
        $this->assertFalse($p[0]->approved);
    }

    #[Test]
    public function app_buchung_legt_pflichtstunde_an(): void
    {
        $t = $this->termin();
        Sanctum::actingAs($this->parent);
        $this->postJson("/api/v1/listen/termine/{$t->id}/reservation")->assertSuccessful();

        $this->assertCount(1, $this->pflichtstunden($t));
    }

    #[Test]
    public function buchung_in_liste_mit_mehrfachbuchung_legt_pflichtstunde_an(): void
    {
        $t = $this->termin(['multiple' => true]);
        $this->actingAs($this->parent)->put("listen/termine/{$t->id}");

        $this->assertCount(1, $this->pflichtstunden($t));
    }

    #[Test]
    public function belegter_termin_wird_nicht_ueberschrieben(): void
    {
        $other = $this->makeParent();
        $t = $this->termin();
        // direkt in der DB belegen, ohne dass der Controller-Vorabcheck greift
        listen_termine::whereKey($t->id)->update(['reserviert_fuer' => $other->id]);

        Sanctum::actingAs($this->parent);
        $this->postJson("/api/v1/listen/termine/{$t->id}/reservation")->assertStatus(409);
        $this->assertSame($other->id, (int) $t->fresh()->reserviert_fuer);
    }

    #[Test]
    public function ohne_creates_pflichtstunden_keine_pflichtstunde(): void
    {
        $t = $this->termin(['creates_pflichtstunden' => false]);
        $this->actingAs($this->parent)->put("listen/termine/{$t->id}");

        $this->assertNotNull($t->fresh()->reserviert_fuer);
        $this->assertCount(0, $this->pflichtstunden($t));
    }

    #[Test]
    public function absage_lehnt_pflichtstunde_ab(): void
    {
        $t = $this->termin();
        $this->actingAs($this->parent)->put("listen/termine/{$t->id}");
        Sanctum::actingAs($this->parent);
        $this->deleteJson("/api/v1/listen/termine/{$t->id}/reservation")->assertSuccessful();

        $this->assertTrue((bool) $this->pflichtstunden($t)[0]->rejected);
    }

    #[Test]
    public function nacherfassen_legt_nur_fehlende_an_und_ist_wiederholbar(): void
    {
        $fehlt = $this->termin();
        $hat = $this->termin();
        $geloescht = $this->termin();
        $ohneFlag = $this->termin(['creates_pflichtstunden' => false]);
        $frei = $this->termin();

        // Buchungen wie im Fehlerfall: ohne Model-Events
        foreach ([$fehlt, $hat, $geloescht, $ohneFlag] as $t) {
            listen_termine::whereKey($t->id)->update(['reserviert_fuer' => $this->parent->id, 'updated_at' => now()]);
        }
        Pflichtstunde::create(['user_id' => $this->parent->id, 'listen_termin_id' => $hat->id, 'start' => now(), 'end' => now()->addHour(), 'description' => 'x', 'approved' => true]);
        Pflichtstunde::create(['user_id' => $this->parent->id, 'listen_termin_id' => $geloescht->id, 'start' => now(), 'end' => now()->addHour(), 'description' => 'x'])->delete();

        $this->artisan('pflichtstunden:listen-nacherfassen', ['--dry-run' => true])->assertSuccessful();
        $this->assertCount(0, $this->pflichtstunden($fehlt));

        $this->artisan('pflichtstunden:listen-nacherfassen')->assertSuccessful();
        $this->assertCount(1, $this->pflichtstunden($fehlt));
        $this->assertSame(90, (int) $this->pflichtstunden($fehlt)[0]->start->diffInMinutes($this->pflichtstunden($fehlt)[0]->end));
        $this->assertCount(1, $this->pflichtstunden($hat));
        $this->assertCount(1, $this->pflichtstunden($geloescht));
        $this->assertCount(0, $this->pflichtstunden($ohneFlag));
        $this->assertCount(0, $this->pflichtstunden($frei));

        // zweiter Lauf: nichts Neues
        $this->artisan('pflichtstunden:listen-nacherfassen')->assertSuccessful();
        $this->assertCount(1, $this->pflichtstunden($fehlt));
    }

    #[Test]
    public function nacherfassen_bricht_ab_wenn_autocreate_aus(): void
    {
        $this->setAutocreate(false);
        $this->artisan('pflichtstunden:listen-nacherfassen')->assertFailed();
    }
}
