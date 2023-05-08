<?php

namespace Tests\Feature\Http\Controllers;

use Tests\TestCase;

/**
 * @see \App\Http\Controllers\SettingsController
 */
class SettingsControllerTest extends TestCase
{
    /**
     * @test
     */
    public function change_nav_returns_an_ok_response()
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $response = $this->get('settings/modul/bottomnav/{modul}');

        $response->assertRedirect(back());

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function change_status_returns_an_ok_response()
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $response = $this->get('settings/modul/{modul}');

        $response->assertRedirect(back());

        // TODO: perform additional assertions
    }

    /**
     * @test
     */
    public function module_returns_an_ok_response()
    {
        $this->markTestIncomplete('This test case was generated by Shift. When you are ready, remove this line and complete this test case.');

        $response = $this->get('settings');

        $response->assertOk();
        $response->assertViewIs('settings.module');
        $response->assertViewHas('module');

        // TODO: perform additional assertions
    }

    // test cases...
}
