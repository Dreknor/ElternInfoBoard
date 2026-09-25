<?php

namespace Tests\Feature\Familienmodell;

use App\Enums\GuardianRelation;
use App\Model\Group;
use App\Model\Pflichtstunde;
use App\Model\User;
use App\Services\Family\FamilyResolver;
use App\Services\Pflichtstunden\PflichtstundenService;
use App\Services\Pflichtstunden\PflichtstundenUnit;
use App\Settings\PflichtstundenSetting;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\BuildsFamilies;
use Tests\TestCase;

/**
 * FAM-05a: Pflichtstunden-Berechnung je Settings (E1) – Matrix aus Basis ×
 * Umgang mit geteilten Kindern × Familienszenarien. Soll pro Einheit = 10 h.
 */
class PflichtstundenServiceTest extends TestCase
{
    use BuildsFamilies;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::findOrCreate('view Pflichtstunden', 'web');
        Permission::findOrCreate('edit settings', 'web');
        $this->useResolver(FamilyResolver::MODE_CHILD_CENTRIC);
    }

    private function service(string $basis, string $shared, ?int $max = null, array $groups = []): PflichtstundenService
    {
        $settings = new PflichtstundenSetting;
        $settings->pflichtstunden_anzahl = 10;
        $settings->pflichtstunden_basis = $basis;
        $settings->pflichtstunden_geteilte_kinder = $shared;
        $settings->pflichtstunden_max_kinder = $max;
        $settings->pflichtstunden_kinder_gruppen = $groups;

        return new PflichtstundenService(app(FamilyResolver::class), $settings);
    }

    private function participant(): User
    {
        $user = $this->makeParent();
        $user->givePermissionTo('view Pflichtstunden');

        return $user;
    }

    private function activeChild(array $guardians, ?Group $class = null)
    {
        return $this->childFor($guardians, ['class_id' => ($class ?? Group::factory()->create())->id]);
    }

    /**
     * Erwartete Soll-Stunden je Einheit (sortiert), Schlüssel: Szenario.
     */
    public static function matrix(): array
    {
        return [
            //                      basis,    shared,      couple,   separated,  patchwork,   no child
            'family / separate' => ['family', 'separate', [10],     [10, 10],   [10, 10],    [10]],
            'family / combined' => ['family', 'combined', [10],     [10],       [10],        [10]],
            'family / split'    => ['family', 'split',    [10],     [5, 5],     [5, 10],     [10]],
            'child / separate'  => ['child',  'separate', [20],     [10, 10],   [10, 20],    [0]],
            'child / combined'  => ['child',  'combined', [20],     [10],       [20],        [0]],
            'child / split'     => ['child',  'split',    [20],     [5, 5],     [5, 15],     [0]],
        ];
    }

    private function requiredHours(PflichtstundenService $service): array
    {
        return $service->units()
            ->map(fn (PflichtstundenUnit $u) => $u->requiredMinutes / 60)
            ->sort()
            ->values()
            ->all();
    }

    #[Test]
    #[DataProvider('matrix')]
    public function classic_couple(string $basis, string $shared, array $couple): void
    {
        [$a, $b] = [$this->participant(), $this->participant()];
        $this->familyOf($a, $b);
        $this->activeChild([$a, $b]);
        $this->activeChild([$a, $b]);

        $this->assertEquals($couple, $this->requiredHours($this->service($basis, $shared)));
    }

    #[Test]
    #[DataProvider('matrix')]
    public function separated_parents(string $basis, string $shared, array $couple, array $separated): void
    {
        [$a, $b] = [$this->participant(), $this->participant()];
        $this->familyOf($a);
        $this->familyOf($b);
        $this->activeChild([$a, $b]);

        $this->assertEquals($separated, $this->requiredHours($this->service($basis, $shared)));
    }

    #[Test]
    #[DataProvider('matrix')]
    public function patchwork_family(string $basis, string $shared, array $couple, array $separated, array $patchwork): void
    {
        [$a, $b, $c] = [$this->participant(), $this->participant(), $this->participant()];
        $this->familyOf($a, $c);
        $this->familyOf($b);
        $this->activeChild([$a, $b]);               // X: A + B (getrennt)
        $y = $this->activeChild([$a]);              // Y: A, Partner C ohne Sorgerecht
        $this->linkGuardian($y, $c, GuardianRelation::Partner);

        $this->assertEquals($patchwork, $this->requiredHours($this->service($basis, $shared)));
    }

    #[Test]
    #[DataProvider('matrix')]
    public function family_without_children(string $basis, string $shared, array $couple, array $separated, array $patchwork, array $noChild): void
    {
        $this->familyOf($this->participant());

        $this->assertEquals($noChild, $this->requiredHours($this->service($basis, $shared)));
    }

    #[Test]
    public function combined_units_sum_hours_of_both_families(): void
    {
        [$a, $b] = [$this->participant(), $this->participant()];
        $this->familyOf($a);
        $this->familyOf($b);
        $this->activeChild([$a, $b]);
        foreach ([$a, $b] as $user) {
            $start = now()->subDays(2)->setTime(9, 0);
            Pflichtstunde::factory()->create(['user_id' => $user->id, 'start' => $start, 'end' => $start->copy()->addHours(2), 'approved' => true]);
        }

        $unit = $this->service('family', 'combined')->unitFor($a);

        $this->assertSame(240, $unit->doneMinutes);
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $unit->userIds);
        // Einträge bleiben familienbezogen – der Ex-Partner sieht nur seine eigenen
        $this->assertCount(1, $this->service('family', 'combined')->entriesFor($a));
    }

    #[Test]
    public function max_children_caps_requirement(): void
    {
        $a = $this->participant();
        $this->familyOf($a);
        $this->activeChild([$a]);
        $this->activeChild([$a]);
        $this->activeChild([$a]);

        $this->assertEquals([20], $this->requiredHours($this->service('child', 'separate', max: 2)));
    }

    #[Test]
    public function group_filter_and_inactive_children_are_respected(): void
    {
        $grundschule = Group::factory()->create();
        $oberschule = Group::factory()->create();
        $a = $this->participant();
        $this->familyOf($a);
        $this->activeChild([$a], $grundschule);
        $this->activeChild([$a], $oberschule);
        $this->childFor([$a], ['class_id' => null, 'group_id' => null]); // kein aktives Kind

        $this->assertEquals([20], $this->requiredHours($this->service('child', 'separate')));
        $this->assertEquals([10], $this->requiredHours($this->service('child', 'separate', groups: [$grundschule->id])));
    }

    #[Test]
    public function only_approved_hours_in_period_count(): void
    {
        $a = $this->participant();
        $this->familyOf($a);
        $start = now()->subDays(2)->setTime(9, 0);
        Pflichtstunde::factory()->create(['user_id' => $a->id, 'start' => $start, 'end' => $start->copy()->addHour(), 'approved' => true]);
        Pflichtstunde::factory()->create(['user_id' => $a->id, 'start' => $start, 'end' => $start->copy()->addHour(), 'approved' => false]);
        $old = now()->subYears(2);
        Pflichtstunde::factory()->create(['user_id' => $a->id, 'start' => $old, 'end' => $old->copy()->addHour(), 'approved' => true]);

        $unit = $this->service('family', 'separate')->unitFor($a);

        $this->assertSame(60, $unit->doneMinutes);
        $this->assertSame(540, $unit->openMinutes());
        $this->assertEquals(10.0, $unit->percent());
    }

    #[Test]
    public function saving_basis_change_logs_timestamp_and_user(): void
    {
        $admin = $this->makeParent();
        $admin->givePermissionTo('edit settings');

        $this->actingAs($admin)->put('settings/pflichtstunden', [
            'pflichtstunden_start' => '08-01',
            'pflichtstunden_ende' => '07-31',
            'pflichtstunden_text' => 'Text',
            'pflichtstunden_anzahl' => 12,
            'pflichtstunden_betrag' => 20,
            'pflichtstunden_basis' => 'child',
            'pflichtstunden_geteilte_kinder' => 'split',
            'pflichtstunden_max_kinder' => 2,
        ])->assertRedirect();

        $this->app->forgetScopedInstances();
        $saved = new PflichtstundenSetting;
        $this->assertSame('child', $saved->pflichtstunden_basis);
        $this->assertSame('split', $saved->pflichtstunden_geteilte_kinder);
        $this->assertSame(2, $saved->pflichtstunden_max_kinder);
        $this->assertNotNull($saved->pflichtstunden_basis_changed_at);
        $this->assertSame($admin->id, $saved->pflichtstunden_basis_changed_by);
    }

    #[Test]
    public function saving_without_basis_change_keeps_log_empty(): void
    {
        $admin = $this->makeParent();
        $admin->givePermissionTo('edit settings');

        $this->actingAs($admin)->put('settings/pflichtstunden', [
            'pflichtstunden_start' => '08-01',
            'pflichtstunden_ende' => '07-31',
            'pflichtstunden_text' => 'Text',
            'pflichtstunden_anzahl' => 12,
            'pflichtstunden_betrag' => 20,
            'pflichtstunden_basis' => 'family',
            'pflichtstunden_geteilte_kinder' => 'separate',
        ])->assertRedirect();

        $this->app->forgetScopedInstances();
        $this->assertNull((new PflichtstundenSetting)->pflichtstunden_basis_changed_at);
    }

    #[Test]
    public function preview_compares_current_and_new_basis(): void
    {
        $admin = $this->makeParent();
        $admin->givePermissionTo('edit settings');
        $a = $this->participant();
        $this->familyOf($a);
        $this->activeChild([$a]);
        $this->activeChild([$a]);

        $this->actingAs($admin)->postJson('settings/pflichtstunden/preview', [
            'pflichtstunden_basis' => 'child',
            'pflichtstunden_geteilte_kinder' => 'separate',
        ])
            ->assertOk()
            ->assertJsonPath('aktuell.einheiten', 1)
            ->assertJsonPath('aktuell.soll_stunden', 20)   // Default-Setting: 20 h pro Familie
            ->assertJsonPath('neu.soll_stunden', 40);      // 2 Kinder × 20 h
    }
}
