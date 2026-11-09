<?php

namespace Tests\Feature\Schuelerkonten;

use App\Model\Child;
use App\Model\Module;
use App\Model\Post;
use App\Model\Termin;
use App\Model\User;
use App\Settings\SchuelerSetting;
use App\Support\StudentAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\ActivatesModules;
use Tests\TestCase;

/**
 * Schülerkonten, Paket S-01 (docs/konzept-schuelerkonten.md §1.1, §3, §9).
 */
class StudentAccountsFoundationTest extends TestCase
{
    use ActivatesModules;
    use RefreshDatabase;

    /** @test */
    public function module_is_restricted_and_inactive_by_default(): void
    {
        $this->assertContains(StudentAccounts::MODULE, config('modules.restricted'));

        $module = Module::where('setting', StudentAccounts::MODULE)->first();
        $this->assertNotNull($module, 'Migration legt das Modul an');
        $this->assertFalse(StudentAccounts::enabled());
    }

    /** @test */
    public function module_stays_off_while_locked_even_if_stored_active(): void
    {
        config(['modules.unlocked' => []]);
        $this->activateModule(StudentAccounts::MODULE);

        $this->assertFalse(StudentAccounts::enabled());
    }

    /** @test */
    public function module_can_be_enabled_once_unlocked_for_the_instance(): void
    {
        config(['modules.unlocked' => [StudentAccounts::MODULE]]);
        $this->assertFalse(StudentAccounts::enabled(), 'freigeschaltet, aber noch nicht aktiviert');

        $this->activateModule(StudentAccounts::MODULE);
        $this->assertTrue(StudentAccounts::enabled());
    }

    /** @test */
    public function existing_records_default_to_standard_and_eltern(): void
    {
        $user = User::factory()->create();
        $this->assertSame(User::ACCOUNT_STANDARD, $user->fresh()->account_type);
        $this->assertFalse($user->fresh()->isStudent());

        $post = Post::factory()->create(['author' => $user->id]);
        $this->assertSame(StudentAccounts::ZIELGRUPPE_ELTERN, $post->fresh()->zielgruppe);

        $termin = Termin::create(['terminname' => 'T', 'start' => now(), 'ende' => now()->addHour(), 'fullDay' => 0]);
        $this->assertSame(StudentAccounts::ZIELGRUPPE_ELTERN, $termin->fresh()->zielgruppe);
    }

    /** @test */
    public function student_accounts_have_no_email_and_are_separated_by_scopes(): void
    {
        $parent = User::factory()->create();
        $studentA = User::factory()->student()->create();
        $studentB = User::factory()->student()->create();

        $this->assertNull($studentA->fresh()->email);
        $this->assertTrue($studentA->fresh()->isStudent());

        $this->assertEqualsCanonicalizing([$parent->id], User::withoutStudents()->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$studentA->id, $studentB->id], User::students()->pluck('id')->all());
    }

    /** @test */
    public function username_is_unique(): void
    {
        User::factory()->student()->create(['username' => 'max.muster']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        User::factory()->student()->create(['username' => 'max.muster']);
    }

    /** @test */
    public function child_links_to_at_most_one_student_account(): void
    {
        $student = User::factory()->student()->create();
        $child = Child::forceCreate(['first_name' => 'Max', 'last_name' => 'Muster', 'user_id' => $student->id]);

        $this->assertTrue($child->studentAccount->is($student));
        $this->assertTrue($student->studentChild->is($child));

        $this->expectException(\Illuminate\Database\QueryException::class);
        Child::forceCreate(['first_name' => 'Moritz', 'last_name' => 'Muster', 'user_id' => $student->id]);
    }

    /** @test */
    public function deleting_the_account_unlinks_the_child(): void
    {
        $student = User::factory()->student()->create();
        $child = Child::forceCreate(['first_name' => 'Max', 'last_name' => 'Muster', 'user_id' => $student->id]);

        $student->forceDelete();

        $this->assertNull($child->fresh()->user_id);
    }

    /** @test */
    public function settings_have_safe_defaults(): void
    {
        $settings = app(SchuelerSetting::class);

        $this->assertTrue($settings->login_sso);
        $this->assertFalse($settings->login_benutzername);
        $this->assertFalse($settings->jit_anlage);
        $this->assertFalse($settings->ucs_provisionierung);
        $this->assertSame([], $settings->freigegebene_gruppen);
        $this->assertFalse($settings->eltern_zustimmung);
        $this->assertFalse($settings->messenger_lehrkraefte);
        $this->assertFalse($settings->eltern_codes);
        $this->assertSame(30, $settings->loeschen_nach_tagen);
        $this->assertFalse($settings->vertretung_abwesenheiten);
    }

    /** @test */
    public function permission_of_locked_module_is_hidden_from_rights_management(): void
    {
        $this->withoutVite();
        $admin = $this->rightsAdmin();

        $this->actingAs($admin)->get('/roles')
            ->assertOk()
            ->assertDontSee(StudentAccounts::PERMISSION_CREATE_POSTS)
            ->assertDontSee(StudentAccounts::MODULE);

        config(['modules.unlocked' => [StudentAccounts::MODULE]]);

        $this->actingAs($admin)->get('/roles')
            ->assertOk()
            ->assertSee(StudentAccounts::PERMISSION_CREATE_POSTS);
    }

    /** @test */
    public function saving_roles_keeps_hidden_permissions(): void
    {
        $admin = $this->rightsAdmin();
        $role = Role::firstOrCreate(['name' => 'Lehrkraft', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view posts', 'guard_name' => 'web']);
        $role->givePermissionTo(StudentAccounts::PERMISSION_CREATE_POSTS);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($admin)->put('/roles', ['Lehrkraft' => ['view posts']]);

        $names = $role->fresh()->permissions->pluck('name')->all();
        $this->assertContains('view posts', $names);
        $this->assertContains(StudentAccounts::PERMISSION_CREATE_POSTS, $names);
    }

    private function rightsAdmin(): User
    {
        Permission::firstOrCreate(['name' => 'edit permission', 'guard_name' => 'web']);
        $admin = User::factory()->create(['password_changed_at' => now()]);
        $admin->givePermissionTo('edit permission');

        return $admin;
    }
}
