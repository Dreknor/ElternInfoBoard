<?php

namespace Tests\Feature\Familienmodell;

use App\Model\ChildGuardian;
use App\Model\Family;
use App\Services\Family\FamilyResolver;
use App\Services\Family\Sorg2MigrationService;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Concerns\BuildsFamilies;
use Tests\TestCase;

/**
 * FAM-09: Migration sorg2 → Familien + direkte Kind-Beziehungen.
 */
class Sorg2MigrationTest extends TestCase
{
    use BuildsFamilies;

    private function migrate(bool $dryRun = false): array
    {
        return app(Sorg2MigrationService::class)->run($dryRun);
    }

    #[Test]
    public function consistent_pair_becomes_family_and_partner_child_is_materialized(): void
    {
        [$a, $b, $child] = $this->coupleWithChildOfA();

        $result = $this->migrate();

        $this->assertSame(1, $result['counts']['familien_paare']);
        $this->assertSame(1, $result['counts']['beziehungen_materialisiert']);
        $family = Family::first();
        $this->assertSame(Family::SOURCE_MIGRATION, $family->source);
        $this->assertSame($family->id, $a->fresh()->family_id);
        $this->assertSame($family->id, $b->fresh()->family_id);

        $pivot = $child->parents()->where('users.id', $b->id)->first()->pivot;
        $this->assertSame(ChildGuardian::SOURCE_MIGRATION, $pivot->source);
        $this->assertTrue($pivot->isPendingReview());
        $this->assertTrue($pivot->has_custody);

        // sorg2 unverändert (Rollback)
        $this->assertSame($b->id, $a->fresh()->sorg2);
        $this->assertSame($a->id, $b->fresh()->sorg2);
    }

    #[Test]
    public function after_migration_child_centric_mode_keeps_partner_access(): void
    {
        [, $b, $child] = $this->coupleWithChildOfA();
        $this->migrate();
        $this->useResolver(FamilyResolver::MODE_CHILD_CENTRIC);

        $this->assertTrue($b->fresh()->children()->contains($child));
        $this->assertTrue($b->fresh()->can('manage', $child));
    }

    #[Test]
    public function migrated_rights_are_copied_from_linked_parent(): void
    {
        $a = $this->makeParent();
        $b = $this->makeParent();
        $this->linkPartners($a, $b);
        $child = $this->childFor([]);
        $child->parents()->attach($a->id, ['has_custody' => false, 'can_manage' => true, 'receives_information' => true]);

        $this->migrate();

        $pivot = $child->parents()->where('users.id', $b->id)->first()->pivot;
        $this->assertFalse($pivot->has_custody);
        $this->assertTrue($pivot->can_manage);
    }

    #[Test]
    public function one_sided_link_is_repaired_as_pair_and_reported(): void
    {
        $a = $this->makeParent();
        $b = $this->makeParent();
        $a->update(['sorg2' => $b->id]);

        $result = $this->migrate();

        $this->assertSame(1, $result['counts']['einseitig_repariert']);
        $this->assertSame($a->fresh()->family_id, $b->fresh()->family_id);
        $this->assertNull($b->fresh()->sorg2, 'sorg2 wird nicht verändert');
    }

    #[Test]
    public function conflicting_link_is_reported_without_joint_family(): void
    {
        $a = $this->makeParent();
        $b = $this->makeParent();
        $c = $this->makeParent();
        $this->linkPartners($b, $c);
        $a->update(['sorg2' => $b->id]);
        $a->assignRole(Role::findOrCreate('Eltern', 'web'));

        $result = $this->migrate();

        $this->assertSame(1, $result['counts']['konflikte']);
        $this->assertSame($b->fresh()->family_id, $c->fresh()->family_id);
        $this->assertNotNull($a->fresh()->family_id);
        $this->assertNotSame($b->fresh()->family_id, $a->fresh()->family_id);
        $this->assertSame($b->id, $a->fresh()->sorg2);
    }

    #[Test]
    public function link_to_deleted_account_is_reported(): void
    {
        $a = $this->makeParent();
        $b = $this->makeParent();
        $this->linkPartners($a, $b);
        $b->delete();
        $a->assignRole(Role::findOrCreate('Eltern', 'web'));

        $result = $this->migrate();

        $this->assertSame(1, $result['counts']['konflikte']);
        $this->assertSame(1, $result['counts']['familien_einzeln']);
    }

    #[Test]
    public function singles_with_parent_role_or_child_get_own_family_others_do_not(): void
    {
        $eltern = $this->makeParent();
        $eltern->assignRole(Role::findOrCreate('Eltern', 'web'));
        $withChild = $this->makeParent();
        $this->childFor([$withChild]);
        $staff = $this->makeParent();

        $result = $this->migrate();

        $this->assertSame(2, $result['counts']['familien_einzeln']);
        $this->assertNotNull($eltern->fresh()->family_id);
        $this->assertNotNull($withChild->fresh()->family_id);
        $this->assertNull($staff->fresh()->family_id);
    }

    #[Test]
    public function dry_run_changes_nothing_but_reports(): void
    {
        $this->coupleWithChildOfA();
        $before = DB::table('child_user')->count();

        $result = $this->migrate(dryRun: true);

        $this->assertSame(1, $result['counts']['familien_paare']);
        $this->assertSame(1, $result['counts']['beziehungen_materialisiert']);
        $this->assertSame(0, Family::count());
        $this->assertSame($before, DB::table('child_user')->count());
    }

    #[Test]
    public function second_run_is_idempotent(): void
    {
        $this->coupleWithChildOfA();
        $this->migrate();
        $links = DB::table('child_user')->count();

        $result = $this->migrate();

        $this->assertSame(0, $result['counts']['familien_paare']);
        $this->assertSame(1, $result['counts']['bereits_migriert']);
        $this->assertSame(0, $result['counts']['beziehungen_materialisiert']);
        $this->assertSame(1, Family::count());
        $this->assertSame($links, DB::table('child_user')->count());
    }

    #[Test]
    public function command_writes_csv_report(): void
    {
        $this->coupleWithChildOfA();
        $path = storage_path('framework/testing/family-migration-test.csv');
        @unlink($path);

        $this->artisan('family:migrate-from-sorg2', ['--report' => $path])->assertSuccessful();

        $this->assertFileExists($path);
        $this->assertStringContainsString('bitte prüfen', file_get_contents($path));
        @unlink($path);
    }
}
