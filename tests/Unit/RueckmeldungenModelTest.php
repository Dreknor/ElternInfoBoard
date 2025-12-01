<?php

namespace Tests\Unit;

use App\Model\Liste;
use App\Model\Post;
use App\Model\Rueckmeldungen;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RueckmeldungenModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_terminliste_fields_in_fillable()
    {
        $rueckmeldung = new Rueckmeldungen();

        $this->assertContains('liste_id', $rueckmeldung->getFillable());
        $this->assertContains('terminliste_start_date', $rueckmeldung->getFillable());
        $this->assertContains('terminliste_end_date', $rueckmeldung->getFillable());
    }

    /** @test */
    public function it_casts_terminliste_dates_correctly()
    {
        $rueckmeldung = Rueckmeldungen::factory()->create([
            'terminliste_start_date' => '2025-12-01',
            'terminliste_end_date' => '2025-12-31',
        ]);

        $this->assertInstanceOf(Carbon::class, $rueckmeldung->terminliste_start_date);
        $this->assertInstanceOf(Carbon::class, $rueckmeldung->terminliste_end_date);
    }

    /** @test */
    public function it_belongs_to_liste()
    {
        $liste = Liste::factory()->create();
        $rueckmeldung = Rueckmeldungen::factory()->create([
            'liste_id' => $liste->id,
        ]);

        $this->assertInstanceOf(Liste::class, $rueckmeldung->liste);
        $this->assertEquals($liste->id, $rueckmeldung->liste->id);
    }

    /** @test */
    public function liste_relation_returns_null_when_liste_id_is_null()
    {
        $rueckmeldung = Rueckmeldungen::factory()->create([
            'liste_id' => null,
        ]);

        $this->assertNull($rueckmeldung->liste);
    }

    /** @test */
    public function is_terminliste_returns_true_for_terminliste_type()
    {
        $rueckmeldung = Rueckmeldungen::factory()->create([
            'type' => 'terminliste',
        ]);

        $this->assertTrue($rueckmeldung->isTerminliste());
    }

    /** @test */
    public function is_terminliste_returns_false_for_other_types()
    {
        $emailRueckmeldung = Rueckmeldungen::factory()->create(['type' => 'email']);
        $abfrageRueckmeldung = Rueckmeldungen::factory()->create(['type' => 'abfrage']);

        $this->assertFalse($emailRueckmeldung->isTerminliste());
        $this->assertFalse($abfrageRueckmeldung->isTerminliste());
    }

    /** @test */
    public function it_can_store_terminliste_with_all_fields()
    {
        $post = Post::factory()->create();
        $liste = Liste::factory()->create();

        $rueckmeldung = Rueckmeldungen::create([
            'post_id' => $post->id,
            'type' => 'terminliste',
            'liste_id' => $liste->id,
            'terminliste_start_date' => Carbon::today(),
            'terminliste_end_date' => Carbon::today()->addWeek(),
            'ende' => Carbon::now()->addWeek(),
            'text' => 'Terminbuchung',
            'empfaenger' => 'test@example.com',
            'pflicht' => true,
        ]);

        $this->assertDatabaseHas('rueckmeldungen', [
            'id' => $rueckmeldung->id,
            'type' => 'terminliste',
            'liste_id' => $liste->id,
        ]);
    }

    /** @test */
    public function terminliste_dates_can_be_null()
    {
        $rueckmeldung = Rueckmeldungen::factory()->create([
            'type' => 'email',
            'terminliste_start_date' => null,
            'terminliste_end_date' => null,
        ]);

        $this->assertNull($rueckmeldung->terminliste_start_date);
        $this->assertNull($rueckmeldung->terminliste_end_date);
    }

    /** @test */
    public function it_updates_post_archiv_ab_when_ende_is_greater()
    {
        $post = Post::factory()->create([
            'archiv_ab' => Carbon::now()->addDays(5),
        ]);

        $rueckmeldung = Rueckmeldungen::create([
            'post_id' => $post->id,
            'type' => 'terminliste',
            'ende' => Carbon::now()->addDays(10),
            'text' => 'Test',
            'empfaenger' => 'test@example.com',
        ]);

        $post->refresh();
        $this->assertEquals(
            Carbon::now()->addDays(10)->format('Y-m-d'),
            $post->archiv_ab->format('Y-m-d')
        );
    }

    /** @test */
    public function liste_relation_uses_null_on_delete()
    {
        $liste = Liste::factory()->create();
        $rueckmeldung = Rueckmeldungen::factory()->create([
            'liste_id' => $liste->id,
        ]);

        $liste->delete();
        $rueckmeldung->refresh();

        $this->assertNull($rueckmeldung->liste_id);
    }
}
