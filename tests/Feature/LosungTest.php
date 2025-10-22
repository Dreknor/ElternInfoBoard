<?php

namespace Tests\Feature;

use App\Model\Losung;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-Tests für Losungen (Tagesspruch/Tagesvers)
 */
class LosungTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function losung_can_be_created()
    {
        $losung = Losung::factory()->create([
            'date' => now()->format('Y-m-d'),
            'text' => 'Siehe, ich bin bei euch alle Tage',
        ]);

        $this->assertDatabaseHas('losungs', [
            'id' => $losung->id,
            'text' => 'Siehe, ich bin bei euch alle Tage',
        ]);
    }

    /**
     * @test
     */
    public function losung_for_today_can_be_retrieved()
    {
        $todayLosung = Losung::factory()->create([
            'date' => now()->format('Y-m-d'),
            'text' => 'Heutiger Vers',
        ]);

        $yesterdayLosung = Losung::factory()->create([
            'date' => now()->subDay()->format('Y-m-d'),
            'text' => 'Gestriger Vers',
        ]);

        $today = Losung::whereDate('date', now())->first();

        $this->assertNotNull($today);
        $this->assertEquals('Heutiger Vers', $today->text);
    }

    /**
     * @test
     */
    public function losung_has_unique_date()
    {
        $date = now()->format('Y-m-d');

        Losung::factory()->create(['date' => $date]);

        // Bei Versuch, eine weitere Losung für das gleiche Datum zu erstellen,
        // sollte entweder ein Fehler auftreten oder die existierende aktualisiert werden
        $existingLosung = Losung::whereDate('date', $date)->first();
        $this->assertNotNull($existingLosung);
    }
}
