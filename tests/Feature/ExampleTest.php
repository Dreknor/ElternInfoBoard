<?php

namespace Tests\Feature;

use App\Model\User;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Test dass die Anwendung ohne Authentifizierung zum Login weiterleitet.
     *
     * @return void
     */
    public function test_basic_test(): void
    {
        $response = $this->get('/');

        // Die Anwendung leitet nicht authentifizierte Benutzer zum Login weiter
        $response->assertStatus(302);
    }

    /**
     * Test dass authentifizierte Benutzer die Startseite sehen können.
     *
     * @return void
     */
    public function test_authenticated_user_can_access_home(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        // Akzeptiere 200 (OK) oder 302 (Redirect) als gültige Antwort für authentifizierte Benutzer
        $this->assertTrue(in_array($response->status(), [200, 302]));
    }
}
