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
    public function losung_can_be_created(): void
    {
        $losung = Losung::factory()->create([
            'date' => now()->format('Y-m-d'),
            'Losungstext' => 'Siehe, ich bin bei euch alle Tage',
        ]);

        $this->assertDatabaseHas('losungen', [
            'id' => $losung->id,
            'Losungstext' => 'Siehe, ich bin bei euch alle Tage',
        ]);
    }

    /**
     * @test
     */
    public function losung_for_today_can_be_retrieved(): void
    {
        $todayLosung = Losung::factory()->create([
            'date' => now()->format('Y-m-d'),
            'Losungstext' => 'Heutiger Vers',
        ]);

        $yesterdayLosung = Losung::factory()->create([
            'date' => now()->subDay()->format('Y-m-d'),
            'Losungstext' => 'Gestriger Vers',
        ]);

        $today = Losung::whereDate('date', now())->first();

        $this->assertNotNull($today);
        $this->assertEquals('Heutiger Vers', $today->Losungstext);
    }

    /**
     * @test
     */
    public function losung_has_unique_date(): void
    {
        $date = now()->format('Y-m-d');

        Losung::factory()->create(['date' => $date]);

        $existingLosung = Losung::whereDate('date', $date)->first();
        $this->assertNotNull($existingLosung);
    }
}
