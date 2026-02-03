<?php

namespace Tests\Feature;

use App\Model\Schickzeiten;
use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-Tests für Schickzeiten-Management
 */
class SchickzeitenTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function user_can_create_schickzeit()
    {
        $user = User::factory()->create();

        $schickzeit = Schickzeiten::factory()->create([
            'users_id' => $user->id,
        ]);

        $this->assertDatabaseHas('schickzeiten', [
            'id' => $schickzeit->id,
            'users_id' => $user->id,
        ]);
    }

    /**
     * @test
     */
    public function schickzeit_belongs_to_user()
    {
        $user = User::factory()->create();
        $schickzeit = Schickzeiten::factory()->create(['users_id' => $user->id]);

        $this->assertInstanceOf(User::class, $schickzeit->user);
        $this->assertEquals($user->id, $schickzeit->user->id);
    }

    /**
     * @test
     */
    public function user_can_update_schickzeit()
    {
        $user = User::factory()->create();
        $schickzeit = Schickzeiten::factory()->create([
            'users_id' => $user->id,
            'time' => '08:00',
        ]);

        $schickzeit->update(['time' => '09:00']);

        $this->assertDatabaseHas('schickzeiten', [
            'id' => $schickzeit->id,
            'time' => '09:00',
        ]);
    }
}
