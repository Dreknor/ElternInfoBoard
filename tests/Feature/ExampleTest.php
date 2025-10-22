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
    public function testBasicTest()
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
    public function testAuthenticatedUserCanAccessHome()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
    }
}
