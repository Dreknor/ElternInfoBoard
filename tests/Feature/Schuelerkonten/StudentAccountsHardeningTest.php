<?php

namespace Tests\Feature\Schuelerkonten;

use App\Model\Child;
use App\Model\Group;
use App\Model\Post;
use App\Model\User;
use App\Services\Family\GroupMembershipService;
use App\Services\Students\StudentAccountService;
use App\Settings\SchuelerSetting;
use App\Support\StudentAccounts;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\ActivatesModules;
use Tests\TestCase;

/**
 * Schreibschutz und Pflege (Pakete S-04, S-11).
 */
class StudentAccountsHardeningTest extends TestCase
{
    use ActivatesModules;

    private function student(?Child $child = null): User
    {
        config(['modules.unlocked' => [StudentAccounts::MODULE]]);
        $this->activateModule(StudentAccounts::MODULE);
        $child ??= Child::factory()->create();
        [$student] = app(StudentAccountService::class)->createForChild($child);

        return $student;
    }

    /** @test */
    public function user_id_validation_ignores_student_accounts(): void
    {
        $student = $this->student();
        $parent = User::factory()->create();

        $this->assertTrue(Validator::make(['id' => $student->id], ['id' => 'exists:users,id'])->fails());
        $this->assertFalse(Validator::make(['id' => $parent->id], ['id' => 'exists:users,id'])->passes() === false);
    }

    /** @test */
    public function students_never_get_derived_group_memberships(): void
    {
        $student = $this->student();
        app(GroupMembershipService::class)->syncDerivedGroups($student);

        $this->assertSame(0, \DB::table('group_user')->where('user_id', $student->id)->count());
    }

    /** @test */
    public function saving_strips_parent_fields_from_students(): void
    {
        $student = $this->student();
        $student->forceFill(['email' => 'a@b.de', 'benachrichtigung' => 'daily', 'phone' => '123'])->save();

        $fresh = $student->fresh();
        $this->assertNull($fresh->email);
        $this->assertNull($fresh->phone);
        $this->assertSame('off', $fresh->benachrichtigung);
    }

    /** @test */
    public function view_all_does_not_reveal_student_only_posts(): void
    {
        $this->student();
        Permission::findOrCreate('view all', 'web');
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('view all');

        $post = Post::factory()->create(['released' => 1, 'external' => 0, 'zielgruppe' => StudentAccounts::ZIELGRUPPE_SCHUELER]);

        $this->assertFalse($viewer->can('view', $post));

        Permission::findOrCreate('edit posts', 'web');
        $viewer->givePermissionTo('edit posts');
        $this->assertTrue($viewer->fresh()->can('view', $post));
    }

    /** @test */
    public function leaving_child_locks_the_account_immediately(): void
    {
        $child = Child::factory()->create();
        $student = $this->student($child);

        $child->update(['status' => Child::STATUS_LEFT]);

        $this->assertFalse($student->fresh()->is_active);
    }

    /** @test */
    public function maintenance_deletes_orphaned_accounts_after_the_deadline(): void
    {
        $child = Child::factory()->create();
        $student = $this->student($child);
        $kept = $this->student(Child::factory()->create());
        app(StudentAccountService::class)->deactivate($kept); // von der Verwaltung gesperrt, Kind aktiv

        $child->delete();
        $this->assertFalse($student->fresh()->is_active);

        $this->artisan('schuelerkonten:pflege')->assertSuccessful();
        $this->assertNotNull(User::withStudents()->find($student->id), 'Frist noch nicht abgelaufen');

        $this->travel(31)->days();
        $this->artisan('schuelerkonten:pflege')->assertSuccessful();

        $this->assertNull(User::withStudents()->find($student->id));
        $this->assertNotNull(User::withStudents()->find($kept->id), 'Kind aktiv → bleibt');
    }

    /** @test */
    public function maintenance_creates_ucs_accounts_only_for_released_groups(): void
    {
        $this->student();
        $released = Group::factory()->create();
        $a = Child::factory()->create(['ucs_username' => 'kind.a', 'ucs_source' => 'kelvin', 'class_id' => $released->id]);
        $b = Child::factory()->create(['ucs_username' => 'kind.b', 'ucs_source' => 'kelvin', 'class_id' => Group::factory()->create()->id]);

        $s = app(SchuelerSetting::class);
        $s->ucs_provisionierung = true;
        $s->freigegebene_gruppen = [$released->id];
        $s->save();

        $this->artisan('schuelerkonten:pflege')->assertSuccessful();

        $this->assertNotNull($a->fresh()->user_id);
        $this->assertNull($b->fresh()->user_id);
    }

    /** @test */
    public function privacy_export_lists_the_childs_account_only_when_enabled(): void
    {
        $child = Child::factory()->create();
        $parent = User::factory()->create();
        $parent->children_rel()->attach($child->id, ['has_custody' => true]);

        $body = fn () => (string) ($this->actingAs($parent->fresh())->get(route('datenschutz.export.json'))->streamedContent());
        $this->assertStringNotContainsString('schuelerkonto', $body());

        $this->student($child);
        $this->assertStringContainsString('schuelerkonto', $body());
    }
}
