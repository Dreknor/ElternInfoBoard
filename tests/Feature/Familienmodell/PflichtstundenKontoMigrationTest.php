<?php

namespace Tests\Feature\Familienmodell;

use App\Model\PflichtstundenFamilyAccount;
use App\Model\PflichtstundenFamilyRule;
use App\Model\User;
use App\Services\Family\FamilyResolver;
use App\Services\PflichtstundenFamilyService;
use App\Settings\PflichtstundenSetting;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\BuildsFamilies;
use Tests\TestCase;

/**
 * Pflichtstunden-Konten und Sollmodell-Regeln (gespeichert unter family_key =
 * kleinste User-ID) müssen die Umstellung sorg2 → Familien, eine geänderte
 * Familienzusammensetzung und einen Rollback überstehen (kein Datenverlust).
 */
class PflichtstundenKontoMigrationTest extends TestCase
{
    use BuildsFamilies;

    private User $c;

    private User $a;

    private User $b;

    private int $periodYear;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('view Pflichtstunden', 'web');

        $settings = app(PflichtstundenSetting::class);
        $settings->konto_uebertrag_aktiv = true;
        $settings->konto_uebertrag_max_stunden = null;
        $settings->save();

        // c zuerst anlegen: kleinste ID, später drittes Familienmitglied
        $this->c = $this->makeParent();
        $this->a = $this->makeParent();
        $this->b = $this->makeParent();
        foreach ([$this->c, $this->a, $this->b] as $user) {
            $user->givePermissionTo('view Pflichtstunden');
        }

        // Altbestand: a und b per sorg2 verknüpft, Regel + Vorjahreskonto unter Schlüssel a
        $this->linkPartners($this->a, $this->b);
        $service = app(PflichtstundenFamilyService::class);
        [$periodStart] = $service->resolvePeriod(null);
        $this->periodYear = $service->periodStartYear($periodStart);

        PflichtstundenFamilyRule::create([
            'family_key' => (string) $this->a->id,
            'period_year' => $this->periodYear,
            'mode' => 'custom',
            'custom_required_hours' => 7,
        ]);
        PflichtstundenFamilyAccount::create([
            'family_key' => (string) $this->a->id,
            'period_year' => $this->periodYear - 1,
            'opening_balance_minutes' => 0,
            'earned_minutes' => 0,
            'required_minutes' => 0,
            'closing_balance_minutes' => 90,
            'carried_to_next_minutes' => 90,
            'carryover_applied' => true,
        ]);
    }

    private function summaryFor(User $user): array
    {
        $service = app(PflichtstundenFamilyService::class);
        [$start, $end] = $service->resolvePeriod(null);

        return $service->buildFamilySummaries($start, $end, false)
            ->first(fn (array $summary) => in_array($user->id, $summary['user_ids'], true));
    }

    #[Test]
    public function legacy_pair_keeps_rule_and_carryover(): void
    {
        $this->useResolver(FamilyResolver::MODE_LEGACY);

        $summary = $this->summaryFor($this->b);

        $this->assertSame((string) $this->a->id, $summary['family_key']);
        $this->assertSame('custom', $summary['rule_mode']);
        $this->assertSame(7 * 60, $summary['required_minutes']);
        $this->assertSame(90, $summary['opening_balance_minutes']);
    }

    #[Test]
    public function migrated_pair_in_child_centric_mode_keeps_rule_and_carryover(): void
    {
        $this->familyOf($this->a, $this->b);
        $this->useResolver(FamilyResolver::MODE_CHILD_CENTRIC);

        $summary = $this->summaryFor($this->a);

        $this->assertSame((string) $this->a->id, $summary['family_key']);
        $this->assertSame('custom', $summary['rule_mode']);
        $this->assertSame(90, $summary['opening_balance_minutes']);
    }

    #[Test]
    public function changed_family_composition_still_finds_rule_and_carryover_of_a_member(): void
    {
        // c (kleinere ID) kommt dazu → neuer Schlüssel c, gespeicherte Daten liegen unter a
        $this->familyOf($this->c, $this->a, $this->b);
        $this->useResolver(FamilyResolver::MODE_CHILD_CENTRIC);

        $summary = $this->summaryFor($this->b);

        $this->assertSame((string) $this->c->id, $summary['family_key']);
        $this->assertSame('custom', $summary['rule_mode']);
        $this->assertSame(7 * 60, $summary['required_minutes']);
        $this->assertSame(90, $summary['opening_balance_minutes']);

        // Rollback auf legacy: sorg2 ist unverändert, alte Zuordnung greift wieder
        $this->useResolver(FamilyResolver::MODE_LEGACY);
        $legacy = $this->summaryFor($this->b);
        $this->assertSame('custom', $legacy['rule_mode']);
        $this->assertSame(90, $legacy['opening_balance_minutes']);
    }

    #[Test]
    public function viewing_a_closed_period_does_not_overwrite_its_account(): void
    {
        $this->familyOf($this->c, $this->a, $this->b);
        $this->useResolver(FamilyResolver::MODE_CHILD_CENTRIC);

        $service = app(PflichtstundenFamilyService::class);
        [$start, $end] = $service->resolvePeriod($this->periodYear - 1);

        // Ansehen (Verwaltung/Export) eines abgelaufenen Zeitraums: Konto bleibt unverändert
        $service->buildFamilySummaries($start, $end, true);
        $this->assertSame(1, PflichtstundenFamilyAccount::where('period_year', $this->periodYear - 1)->count());
        $account = PflichtstundenFamilyAccount::where('period_year', $this->periodYear - 1)->first();
        $this->assertSame((string) $this->a->id, $account->family_key);
        $this->assertSame(90, $account->closing_balance_minutes);

        // Bewusster Jahresabschluss darf neu schreiben
        $service->buildFamilySummaries($start, $end, true, false, true);
        $this->assertSame(2, PflichtstundenFamilyAccount::where('period_year', $this->periodYear - 1)->count());
    }
}
