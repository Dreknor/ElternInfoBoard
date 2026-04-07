<?php

namespace Tests\Feature;

use App\Model\Schickzeiten;
use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test für Schickzeiten Accessor-Methoden
 */
class SchickzeitenAccessorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test dass getTimeAttribute() null zurückgibt wenn time nicht gesetzt ist
     *
     * @test
     */
    public function time_accessor_returns_null_when_time_not_set(): void
    {
        $user = User::factory()->create();

        // Erstelle Schickzeit vom Typ "ab" ohne time Feld
        $schickzeit = Schickzeiten::factory()->create([
            'users_id' => $user->id,
            'type' => 'ab',
            'time_ab' => '08:00',
            'time' => null,
        ]);

        // Der Zugriff auf time sollte keinen Fehler werfen und null zurückgeben
        $this->assertNull($schickzeit->time);
    }

    /**
     * Test dass getTimeAttribute() korrekt funktioniert wenn time gesetzt ist
     *
     * @test
     */
    public function time_accessor_returns_carbon_when_time_is_set(): void
    {
        $user = User::factory()->create();

        // Erstelle Schickzeit vom Typ "genau" mit time Feld
        $schickzeit = Schickzeiten::factory()->create([
            'users_id' => $user->id,
            'type' => 'genau',
            'time' => '09:30',
        ]);

        // Der Zugriff auf time sollte ein Carbon-Objekt zurückgeben
        $this->assertNotNull($schickzeit->time);
        $this->assertInstanceOf(\Carbon\Carbon::class, $schickzeit->time);
    }

    /**
     * Test dass Schickzeiten in JSON konvertiert werden können ohne Fehler
     *
     * @test
     */
    public function schickzeit_can_be_converted_to_json_without_time(): void
    {
        $user = User::factory()->create();

        // Erstelle Schickzeit vom Typ "ab" ohne time Feld
        $schickzeit = Schickzeiten::factory()->create([
            'users_id' => $user->id,
            'type' => 'ab',
            'time_ab' => '08:00',
            'time_spaet' => '09:00',
            'time' => null,
        ]);

        // Sollte ohne Fehler in JSON konvertiert werden können
        $json = $schickzeit->toJson();
        $this->assertJson($json);

        $data = json_decode($json, true);
        $this->assertArrayHasKey('time', $data);
        $this->assertNull($data['time']);
    }
}

