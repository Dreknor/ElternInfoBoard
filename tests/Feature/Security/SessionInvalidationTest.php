<?php

namespace Tests\Feature\Security;

use App\Model\SecurityEvent;
use App\Model\User;
use Grosv\LaravelPasswordlessLogin\LoginUrl;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * AuthenticateSession (web-Gruppe): Nach einer Passwortänderung enden andere Web-Sitzungen,
 * die eigene bleibt; Impersonation und Anmelde-Link funktionieren weiter.
 */
class SessionInvalidationTest extends TestCase
{
    /** Nächster Request wie von einem neuen Prozess: Nutzer aus der Sitzung neu laden. */
    private function freshGuard(): void
    {
        Auth::forgetGuards();
    }

    public function test_passwortaenderung_an_anderer_stelle_beendet_diese_sitzung(): void
    {
        $user = User::factory()->create(['changePassword' => false, 'password' => Hash::make('Altes-Passwort1')]);
        $this->post('/login', ['email' => $user->email, 'password' => 'Altes-Passwort1'])->assertRedirect();

        $this->freshGuard();
        $this->get('/einstellungen')->assertOk();

        // anderes Gerät / Admin ändert das Passwort
        User::whereKey($user->id)->update(['password' => Hash::make('Neues-Passwort-1')]);

        $this->freshGuard();
        $this->get('/einstellungen')->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertSame('session_invalidated', SecurityEvent::where('event', 'auth.logout')->sole()->context['reason']);
    }

    public function test_eigene_passwortaenderung_behaelt_die_eigene_sitzung(): void
    {
        $user = User::factory()->create(['changePassword' => false, 'password' => Hash::make('Altes-Passwort1')]);
        $this->post('/login', ['email' => $user->email, 'password' => 'Altes-Passwort1']);

        $this->freshGuard();
        $this->put('/einstellungen', [
            'name' => $user->name,
            'email' => $user->email,
            'benachrichtigung' => 'weekly',
            'current_password' => 'Altes-Passwort1',
            'password' => 'Neues-Passwort-123!',
            'password_confirmation' => 'Neues-Passwort-123!',
        ])->assertRedirect();

        $this->freshGuard();
        $this->get('/einstellungen')->assertOk();
        $this->assertAuthenticatedAs($user);
    }

    public function test_impersonation_bleibt_angemeldet(): void
    {
        foreach (['loginAsUser', 'edit user'] as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }
        $admin = User::factory()->create(['changePassword' => false, 'password' => Hash::make('Admin-Passwort1')]);
        $admin->givePermissionTo('loginAsUser', 'edit user');
        $target = User::factory()->create(['changePassword' => false, 'password' => Hash::make('Anderes-Passwort1')]);

        $this->post('/login', ['email' => $admin->email, 'password' => 'Admin-Passwort1']);
        $this->freshGuard();
        $this->get('/einstellungen')->assertOk();

        $this->freshGuard();
        $this->post('/showUser/'.$target->id)->assertRedirect();

        $this->freshGuard();
        $this->get('/einstellungen')->assertOk();
        $this->assertAuthenticatedAs($target);

        $this->freshGuard();
        $this->get('/logoutAsUser')->assertRedirect();

        $this->freshGuard();
        $this->get('/einstellungen')->assertOk();
        $this->assertAuthenticatedAs($admin);
    }

    public function test_kontowechsel_ohne_login_fuehrt_nicht_zur_abmeldung(): void
    {
        $a = User::factory()->create(['changePassword' => false, 'password' => Hash::make('Passwort-A-123')]);
        $b = User::factory()->create(['changePassword' => false, 'password' => Hash::make('Passwort-B-123')]);

        $this->actingAs($a)->get('/einstellungen')->assertOk();
        $this->actingAs($b)->get('/einstellungen')->assertOk();
        $this->assertAuthenticatedAs($b);
    }

    public function test_anmelde_link_bleibt_angemeldet(): void
    {
        $user = User::factory()->create(['changePassword' => false]);

        $this->get((new LoginUrl($user))->generate())->assertRedirect();

        $this->freshGuard();
        $this->get('/einstellungen')->assertOk();
        $this->assertAuthenticatedAs($user);
    }
}
