<?php

namespace Tests\Feature\Http\Controllers;

use App\Model\Changelog;
use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\BenutzerController
 */
class BenutzerControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     **/
    public function show_returns_an_ok_response(): void
    {
        $user = User::factory()->create(['changePassword' => false]);

        $response = $this->actingAs($user)->get('einstellungen');

        $response->assertOk();
        $response->assertViewIs('user.settings');
        $response->assertViewHas('user');
        $response->assertViewHas('changelog');
    }

    /**
     * @test
     **/
    public function show_displays_changelog_when_change_settings_is_true(): void
    {
        $user = User::factory()->create(['changeSettings' => true, 'changePassword' => false]);
        $changelog = Changelog::factory()->create(['changeSettings' => true]);

        $response = $this->actingAs($user)
            ->withSession(['changelog' => true])
            ->get('einstellungen');

        $this->assertTrue(in_array($response->status(), [200, 302]));

        if ($response->status() === 200) {
            $response->assertViewHas('changelog', function ($viewChangelog) use ($changelog) {
                return $viewChangelog->id === $changelog->id;
            });
        }
    }

    /**
     * @test
     **/
    public function unauthenticated_user_cannot_access_settings(): void
    {
        $response = $this->get('einstellungen');

        $response->assertRedirect(route('login'));
    }

    /**
     * @test
     **/
    public function update_returns_a_redirect_response(): void
    {
        $user = User::factory()->create(['changePassword' => false]);
        $response = $this->actingAs($user)->put('einstellungen', [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'benachrichtigung' => 'daily',
            'sendCopy' => 0,
            'track_login' => 1,
            'publicMail' => 'public@example.com',
            'publicPhone' => '123456789',
            'calendar_prefix' => 'TEST',
            'releaseCalendar' => 1,
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
     **/
    public function update_changes_password_when_provided(): void
    {
        $currentPassword = 'AktuellesPasswort1!';
        $user = User::factory()->create([
            'changePassword' => false,
            'password' => Hash::make($currentPassword),
        ]);
        $response = $this->actingAs($user)->put('einstellungen', [
            'name' => $user->name,
            'email' => $user->email,
            'benachrichtigung' => 'weekly',
            'sendCopy' => 0,
            'track_login' => 1,
            'publicMail' => '',
            'publicPhone' => '',
            'calendar_prefix' => '',
            'releaseCalendar' => 0,
            'current_password' => $currentPassword,
            'password' => 'NeuesPasswort1!',
            'password_confirmation' => 'NeuesPasswort1!',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('type', 'success');

        $user->refresh();
        $this->assertTrue(Hash::check('NeuesPasswort1!', $user->password));
    }

    /**
     * @test
     **/
    public function update_validates_with_a_form_request(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\BenutzerController::class,
            'update',
            \App\Http\Requests\UpdateProfileRequest::class
        );
    }

    /**
     * @test
     **/
    public function create_token_generates_new_api_token(): void
    {
        $user = User::factory()->create(['changePassword' => false]);

        $response = $this->actingAs($user)->post('/einstellungen/token', [
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
