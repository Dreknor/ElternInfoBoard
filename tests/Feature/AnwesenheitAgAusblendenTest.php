<?php

namespace Tests\Feature;

use App\Model\Arbeitsgemeinschaft;
use App\Model\Child;
use App\Model\Group;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Anwesenheitsliste: AGs verschwinden, sobald ihr Ende mehr als 15 Minuten zurückliegt –
 * auch wenn die AGs des Tages bereits zwischengespeichert sind.
 */
class AnwesenheitAgAusblendenTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** @test */
    public function ag_is_hidden_fifteen_minutes_after_its_end(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-11 13:00')); // Mittwoch
        $child = Child::factory()->create(['group_id' => Group::factory()->create(['owner_id' => null])->id]);
        $ag = Arbeitsgemeinschaft::create([
            'name' => 'Schach', 'weekday' => now()->dayOfWeek, 'start_time' => '14:00', 'end_time' => '15:00',
            'start_date' => now()->subWeek(), 'end_date' => now()->addMonth(),
        ]);
        DB::table('arbeitsgemeinschaften_participants')->insert(['ag_id' => $ag->id, 'participant_id' => $child->id]);

        $this->assertSame(['Schach'], $child->arbeitsgemeinschaften_aktuell()->pluck('name')->all());

        Carbon::setTestNow(Carbon::parse('2026-11-11 15:14'));
        $this->assertCount(1, $child->arbeitsgemeinschaften_aktuell());

        Carbon::setTestNow(Carbon::parse('2026-11-11 15:16'));
        $this->assertCount(0, $child->arbeitsgemeinschaften_aktuell());
        $this->assertCount(1, $child->arbeitsgemeinschaften_today(), 'Tagesliste bleibt unverändert');
    }
}
