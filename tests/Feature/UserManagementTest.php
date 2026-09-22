<?php

namespace Tests\Feature;

use App\Mail\NewUserPasswordMail;
use App\Model\Group;
use App\Model\Pflichtstunde;
use App\Model\User;
use App\Scopes\GetGroupsScope;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Feature-Tests für die Benutzerverwaltung
 */
class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Spatie Permission Cache leeren für Test-Isolation
        $this->app[PermissionRegistrar::class]->forgetCachedPermissions();

        // Basis-Permissions und Rollen anlegen
        Permission::firstOrCreate(['name' => 'edit user', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'edit permission', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'set password', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'loginAsUser', 'guard_name' => 'web']);

        $adminRole = Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        $adminRole->givePermissionTo(['edit user', 'edit permission', 'set password', 'loginAsUser']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrator');

        // EmailSetting-Daten für Tests bereitstellen (verhindert Settings-Fehler in UserService)
        DB::table('settings')->insertOrIgnore([
            ['group' => 'email', 'name' => 'new_user_welcome_text', 'payload' => json_encode('Willkommen!'), 'locked' => false],
            ['group' => 'email', 'name' => 'mail_server', 'payload' => json_encode('localhost'), 'locked' => false],
            ['group' => 'email', 'name' => 'mail_port', 'payload' => json_encode('25'), 'locked' => false],
            ['group' => 'email', 'name' => 'mail_username', 'payload' => json_encode(''), 'locked' => false],
            ['group' => 'email', 'name' => 'mail_password', 'payload' => json_encode(''), 'locked' => false],
            ['group' => 'email', 'name' => 'mail_encryption', 'payload' => json_encode(''), 'locked' => false],
            ['group' => 'email', 'name' => 'mail_from_address', 'payload' => json_encode('test@example.com'), 'locked' => false],
            ['group' => 'email', 'name' => 'mail_from_name', 'payload' => json_encode('Test'), 'locked' => false],
            ['group' => 'email', 'name' => 'log_sent_emails', 'payload' => json_encode(false), 'locked' => false],
        ]);
    }

    /**
     * @test
     * Admin kann neuen Benutzer anlegen und erhält Willkommens-E-Mail
     */
    public function test_admin_can_create_user(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->admin)
            ->post('/users', [
                'name' => 'Max Mustermann',
                'email' => 'max@example.com',
            ]);

        $response->assertSessionHasNoErrors();
        // Erfolgreiche Erstellung leitet zu users/{id} weiter
        $response->assertRedirectContains('users/');
        $this->assertDatabaseHas('users', ['email' => 'max@example.com']);
        Mail::assertSent(NewUserPasswordMail::class, fn ($mail) => $mail->hasTo('max@example.com'));
    }

    /**
     * @test
     * Benutzer ohne 'edit user' Permission kann nicht auf Benutzerverwaltung zugreifen
     */
    public function test_non_admin_cannot_access_user_management(): void
    {
        // changePassword=false explizit setzen, damit PasswordExpired nicht dazwischenkommt
        $normalUser = User::factory()->create(['changePassword' => false]);
        $this->actingAs($normalUser);

        $response = $this->get(route('users.index'));
        // Spatie PermissionMiddleware: 403 wenn kein 'edit user'
        $response->assertStatus(403);
    }

    /**
     * @test
     * Unique-Validierung verhindert doppelte E-Mail-Adressen
     */
    public function test_unique_validation_prevents_duplicate_email(): void
    {
        $this->actingAs($this->admin);
        User::factory()->create(['email' => 'doppelt@example.com']);

        $response = $this->post('/users', [
            'name' => 'Anderer Name',
            'email' => 'doppelt@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 2);
    }

    /**
     * @test
     * Massenlöschung überspringt geschützte Rollen (Mitarbeiter, Administrator)
     */
    public function test_mass_delete_with_protected_roles_skips_them(): void
    {
        $this->actingAs($this->admin);

        Role::firstOrCreate(['name' => 'Mitarbeiter', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Eltern', 'guard_name' => 'web']);

        $mitarbeiter = User::factory()->create();
        $mitarbeiter->assignRole('Mitarbeiter');

        $elternteil = User::factory()->create();
        $elternteil->assignRole('Eltern');

        /** @var UserService $service */
        $service = app(UserService::class);
        $result = $service->massDeleteUsers([$mitarbeiter->id, $elternteil->id]);

        $this->assertEquals(1, $result['deleted']);
        $this->assertStringContainsString($mitarbeiter->name, $result['errors']);
        $this->assertDatabaseHas('users', ['id' => $mitarbeiter->id]);
        $this->assertDatabaseMissing('users', ['id' => $elternteil->id, 'deleted_at' => null]);
    }

    /**
     * @test
     * UserService::deleteUser() kaskadiert in einer Transaktion
     */
    public function test_destroy_cascades_in_transaction(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create();
        $user->groups()->attach($group->id);

        /** @var UserService $service */
        $service = app(UserService::class);
        $error = $service->deleteUser($user);

        $this->assertEquals('', $error);
        $this->assertDatabaseMissing('users', ['id' => $user->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('groups', ['id' => $group->id]);
    }

    /**
     * @test
     * Beim Löschen eines Users bleibt die Pflichtstunden-Historie erhalten,
     * die Berechnung für die aktive Familie wird aber unterbrochen.
     */
    public function test_deleting_user_keeps_pflichtstunden_history(): void
    {
        $user = User::factory()->create();
        $partner = User::factory()->create();
        $user->update(['sorg2' => $partner->id]);
        $partner->update(['sorg2' => $user->id]);

        $pflichtstunde = Pflichtstunde::create([
            'user_id' => $user->id,
            'description' => 'Testpflichtstunde',
            'start' => now()->subDay()->setTime(8, 0),
            'end' => now()->subDay()->setTime(10, 0),
            'approved' => true,
            'approved_at' => now(),
            'approved_by' => $partner->id,
            'rejected' => false,
        ]);

        /** @var UserService $service */
        $service = app(UserService::class);
        $error = $service->deleteUser($user);

        $this->assertSame('', $error);
        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertDatabaseHas('pflichtstunden', ['id' => $pflichtstunde->id]);
        $this->assertTrue(Pflichtstunde::withTrashed()->whereKey($pflichtstunde->id)->exists());
    }

    /**
     * @test
     * UserService::forceDeleteTrashedUser() rechnet vor dem endgültigen Löschen
     * alle betroffenen (auch historischen) Perioden final ab und persistiert
     * das Familienkonto, sodass der abgerechnete Betrag/Saldo erhalten bleibt,
     * obwohl die Pflichtstunden-Rohdaten per Cascade gelöscht werden. Dies gilt
     * auch, wenn der Partner bereits (soft-)gelöscht ist.
     */
    public function test_force_delete_seals_pflichtstunden_family_history(): void
    {
        $user = User::factory()->create();
        $partner = User::factory()->create();
        $user->update(['sorg2' => $partner->id]);
        $partner->update(['sorg2' => $user->id]);

        $permission = Permission::firstOrCreate(['name' => 'view Pflichtstunden', 'guard_name' => 'web']);
        $user->givePermissionTo($permission);
        $partner->givePermissionTo($permission);

        // Historischer Eintrag aus einer vergangenen Periode (2 Jahre zurück)
        $historicalStart = now()->subYears(2);
        $pflichtstunde = Pflichtstunde::create([
            'user_id' => $user->id,
            'description' => 'Historische Pflichtstunde',
            'start' => $historicalStart->copy()->setTime(8, 0),
            'end' => $historicalStart->copy()->setTime(10, 0),
            'approved' => true,
            'approved_at' => $historicalStart,
            'approved_by' => $partner->id,
            'rejected' => false,
        ]);

        // Partner wird vor dem Hard-Delete des Users bereits soft-gelöscht
        $partner->delete();

        $user->delete(); // in den Papierkorb

        /** @var UserService $service */
        $service = app(UserService::class);
        $error = $service->forceDeleteTrashedUser($user->fresh());

        $this->assertSame('', $error);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('pflichtstunden', ['id' => $pflichtstunde->id]);

        $familyService = app(\App\Services\PflichtstundenFamilyService::class);
        $familyKey = $familyService->determineFamilyKey($user, $partner);
        $expectedPeriodYear = $familyService->resolvePeriodStartYearForDate($historicalStart);

        $this->assertDatabaseHas('pflichtstunden_family_accounts', [
            'family_key' => $familyKey,
            'period_year' => $expectedPeriodYear,
        ]);
    }

    /**
     * @test
     * Sorg2-Verknüpfung löst vorherige bidirektional auf
     */
    public function test_sorg2_link_clears_old_partner(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $user1->update(['sorg2' => $user2->id]);
        $user2->update(['sorg2' => $user1->id]);

        /** @var UserService $service */
        $service = app(UserService::class);
        $service->linkSorgeberechtigte($user1, $user3->id);

        $this->assertNull($user2->fresh()->sorg2);
        $this->assertEquals($user3->id, $user1->fresh()->sorg2);
        $this->assertEquals($user1->id, $user3->fresh()->sorg2);
    }

    /**
     * @test
     * Self-Service Passwort-Änderung erfordert aktuelles Passwort
     */
    public function test_self_service_password_requires_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Aktuell123!'),
        ]);

        // PUT /einstellungen (Route hat keinen Namen für Update)
        $response = $this->actingAs($user)->put('/einstellungen', [
            'name' => $user->name,
            'email' => $user->email,
            'benachrichtigung' => 'daily',
            'password' => 'NeuesPasswort1!',
            'password_confirmation' => 'NeuesPasswort1!',
            // current_password fehlt absichtlich
        ]);

        $response->assertRedirect(); // muss Redirect sein (Validierungsfehler oder Erfolg)

        // Kern-Sicherheitstest: Passwort darf ohne aktuelles Passwort NICHT geändert worden sein
        $this->assertTrue(
            Hash::check('Aktuell123!', $user->fresh()->password),
            'Passwort darf ohne current_password nicht geändert worden sein'
        );
    }

    /**
     * @test
     * Deaktivierter Benutzer wird bei Anfrage ausgeloggt
     */
    public function test_inactive_user_is_logged_out(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('users', 'is_active')) {
            $this->markTestSkipped('Migration 2026_03_21_000001 noch nicht ausgeführt.');
        }

        $inactiveUser = User::factory()->create(['is_active' => false]);
        $this->actingAs($inactiveUser);

        $response = $this->get('/home');
        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}




