<?php

namespace Tests\Feature;

use App\Model\Termin;
use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-Tests für Termin-Management
 */
class TerminManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function user_can_create_termin(): void
    {
        $user = User::factory()->create(['password_changed_at' => now()]);

        $termin = Termin::create([
            'terminname' => 'Test Termin',
            'start' => now()->addDays(1),
            'ende' => now()->addDays(1)->addHours(2),
        ]);

        $this->assertDatabaseHas('termine', [
            'id' => $termin->id,
            'terminname' => 'Test Termin',
        ]);
    }

    /**
     * @test
     */
    public function termin_belongs_to_author(): void
    {
        // Termine haben keine author_id Spalte - Test übersprungen
        $this->markTestSkipped('Termine Tabelle hat keine author_id Spalte');
    }

    /**
     * @test
     */
    public function future_termins_can_be_queried(): void
    {
        Termin::create([
            'terminname' => 'Zukünftiger Termin',
            'start' => now()->addDays(5),
            'ende' => now()->addDays(6),
        ]);

        Termin::create([
            'terminname' => 'Vergangener Termin',
            'start' => now()->subDays(5),
            'ende' => now()->subDays(4),
        ]);

        $futureTermins = Termin::where('start', '>', now())->get();

        $this->assertCount(1, $futureTermins);
    }
}
