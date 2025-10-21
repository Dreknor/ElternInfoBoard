<?php

namespace Tests\Feature\Http\Controllers;

use App\Model\Changelog;
use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\BenutzerController
 */
class BenutzerControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function show_returns_an_ok_response()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('einstellungen');

        $response->assertOk();
        $response->assertViewIs('user.settings');
        $response->assertViewHas('user');
        $response->assertViewHas('changelog');
    }

    /**
     * @test
     */
    public function show_displays_changelog_when_changeSettings_is_true()
    {
        $user = User::factory()->create(['changeSettings' => true]);
        $changelog = Changelog::factory()->create(['changeSettings' => true]);

        $response = $this->actingAs($user)
            ->withSession(['changelog' => true])
            ->get('einstellungen');

        $response->assertOk();
        $response->assertViewHas('changelog', function ($viewChangelog) use ($changelog) {
            return $viewChangelog->id === $changelog->id;
        });
    }

    /**
     * @test
     */
    public function unauthenticated_user_cannot_access_settings()
    {
        $response = $this->get('einstellungen');

        $response->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function update_returns_a_redirect_response()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('einstellungen', [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'benachrichtigung' => true,
            'sendCopy' => false,
            'track_login' => true,
            'publicMail' => 'public@example.com',
            'publicPhone' => '123456789',
            'calendar_prefix' => 'TEST',
            'releaseCalendar' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('type', 'success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    }

    /**
     * @test
     */
    public function update_changes_password_when_provided()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('einstellungen', [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('type', 'success');

        $user->refresh();
        $this->assertTrue(\Hash::check('newpassword123', $user->password));
    }

    /**
     * @test
     */
    public function update_validates_with_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\BenutzerController::class,
            'update',
            \App\Http\Requests\editUserRequest::class
        );
    }

    /**
     * @test
     */
    public function create_token_generates_new_api_token()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('token.create'), [
            'name' => 'Test Token',
        ]);

        $response->assertRedirect(url('einstellungen'));
        $response->assertSessionHas('type', 'success');
        $response->assertSessionHas('token');

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'Test Token',
        ]);
    }
}
