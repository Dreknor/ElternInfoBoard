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
        $user = User::factory()->create();

        $liste = Liste::factory()->create([
            'author_id' => $user->id,
            'name' => 'Testliste',
        ]);

        $this->assertDatabaseHas('listes', [
            'id' => $liste->id,
            'author_id' => $user->id,
            'name' => 'Testliste',
        ]);
    }

    /**
     * @test
     */
    public function liste_can_have_eintragungen()
    {
        $liste = Liste::factory()->create();
        $eintragungen = Listen_Eintragungen::factory()->count(5)->create([
            'liste_id' => $liste->id,
        ]);

        $this->assertCount(5, $liste->eintragungen);
    }

    /**
     * @test
     */
    public function user_can_add_eintragung_to_liste()
    {
        $user = User::factory()->create();
        $liste = Liste::factory()->create();

        $eintragung = Listen_Eintragungen::factory()->create([
            'liste_id' => $liste->id,
            'user_id' => $user->id,
            'text' => 'Meine Eintragung',
        ]);

        $this->assertDatabaseHas('listen__eintragungen', [
            'id' => $eintragung->id,
            'liste_id' => $liste->id,
            'user_id' => $user->id,
        ]);
    }

    /**
     * @test
     */
    public function liste_belongs_to_author()
    {
        $user = User::factory()->create();
        $liste = Liste::factory()->create(['author_id' => $user->id]);

        $this->assertInstanceOf(User::class, $liste->author);
        $this->assertEquals($user->id, $liste->author->id);
    }
}

