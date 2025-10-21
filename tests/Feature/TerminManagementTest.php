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
    public function user_can_create_termin()
    {
        $user = User::factory()->create();

        $termin = Termin::factory()->create([
            'author_id' => $user->id,
        ]);

        $this->assertDatabaseHas('termins', [
            'id' => $termin->id,
            'author_id' => $user->id,
        ]);
    }

    /**
     * @test
     */
    public function termin_belongs_to_author()
    {
        $user = User::factory()->create();
        $termin = Termin::factory()->create(['author_id' => $user->id]);

        $this->assertInstanceOf(User::class, $termin->author);
        $this->assertEquals($user->id, $termin->author->id);
    }

    /**
     * @test
     */
    public function future_termins_can_be_queried()
    {
        Termin::factory()->create([
            'start' => now()->addDays(5),
            'end' => now()->addDays(6),
        ]);

        Termin::factory()->create([
            'start' => now()->subDays(5),
            'end' => now()->subDays(4),
        ]);

        $futureTermins = Termin::where('start', '>', now())->get();

        $this->assertCount(1, $futureTermins);
    }

    /**
     * @test
     */
    public function termin_can_have_description()
    {
        $termin = Termin::factory()->create([
            'description' => 'Wichtiger Termin für alle Eltern',
        ]);

        $this->assertEquals('Wichtiger Termin für alle Eltern', $termin->description);
    }
}
