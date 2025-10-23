<?php

namespace Tests\Feature;

use App\Model\Liste;
use App\Model\Listen_Eintragungen;
use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-Tests für Listen-Management
 */
class ListenManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function user_can_create_liste()
    {
        $user = User::factory()->create(['password_changed_at' => now()]);

        $liste = Liste::factory()->create([
            'besitzer' => $user->id,
            'listenname' => 'Testliste',
        ]);

        $this->assertDatabaseHas('listen', [
            'id' => $liste->id,
            'besitzer' => $user->id,
            'listenname' => 'Testliste',
        ]);
    }

    /**
     * @test
     */
    public function liste_can_have_eintragungen()
    {
        $liste = Liste::factory()->create();
        $eintragungen = Listen_Eintragungen::factory()->count(5)->create([
            'listen_id' => $liste->id,
        ]);

        $this->assertCount(5, $liste->eintragungen);
    }

    /**
     * @test
     */
    public function user_can_add_eintragung_to_liste()
    {
        $user = User::factory()->create(['password_changed_at' => now()]);
        $liste = Liste::factory()->create();

        $eintragung = Listen_Eintragungen::factory()->create([
            'listen_id' => $liste->id,
            'user_id' => $user->id,
            'text' => 'Meine Eintragung',
        ]);

        $this->assertDatabaseHas('listen_eintragungen', [
            'id' => $eintragung->id,
            'listen_id' => $liste->id,
            'user_id' => $user->id,
        ]);
    }

    /**
     * @test
     */
    public function liste_belongs_to_author()
    {
        $user = User::factory()->create(['password_changed_at' => now()]);
        $liste = Liste::factory()->create(['besitzer' => $user->id]);

        $this->assertInstanceOf(User::class, $liste->ersteller);
        $this->assertEquals($user->id, $liste->ersteller->id);
    }
}

