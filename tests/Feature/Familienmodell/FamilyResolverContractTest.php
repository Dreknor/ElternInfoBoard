<?php

namespace Tests\Feature\Familienmodell;

use App\Enums\GuardianRelation;
use App\Enums\GuardianRight;
use App\Services\Family\ChildCentricFamilyResolver;
use App\Services\Family\FamilyResolver;
use App\Services\Family\FamilyUnit;
use App\Services\Family\LegacySorg2FamilyResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsFamilies;
use Tests\TestCase;

/**
 * FAM-03: Beide Resolver müssen für Szenarien, die beide abbilden können
 * (Elternpaar, Alleinerziehend, Bezugsperson mit eingeschränkten Rechten),
 * identische Antworten liefern. Das sichert das Umschalten Phase 2 → 4 ab.
 */
class FamilyResolverContractTest extends TestCase
{
    use BuildsFamilies;

    public static function modes(): array
    {
        return [
            'legacy' => [FamilyResolver::MODE_LEGACY],
            'child_centric' => [FamilyResolver::MODE_CHILD_CENTRIC],
        ];
    }

    private function resolver(string $mode): FamilyResolver
    {
        $this->useResolver($mode);

        return app(FamilyResolver::class);
    }

    #[Test]
    public function container_resolves_configured_mode(): void
    {
        $this->assertInstanceOf(LegacySorg2FamilyResolver::class, $this->resolver(FamilyResolver::MODE_LEGACY));
        $this->assertInstanceOf(ChildCentricFamilyResolver::class, $this->resolver(FamilyResolver::MODE_CHILD_CENTRIC));
    }

    #[Test]
    #[DataProvider('modes')]
    public function couple_forms_one_family(string $mode): void
    {
        [$a, $b] = $this->coupleWithSharedChild();
        $single = $this->makeParent();
        $resolver = $this->resolver($mode);

        $this->assertEqualsCanonicalizing([$a->id, $b->id], $resolver->familyUserIds($a));
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $resolver->familyUserIds($b));
        $this->assertSame([$single->id], $resolver->familyUserIds($single));
        $this->assertTrue($resolver->isSameFamily($a, $b));
        $this->assertTrue($resolver->isSameFamily($a, $b->id));
        $this->assertFalse($resolver->isSameFamily($a, $single));
        $this->assertFalse($resolver->isSameFamily($a, null));
    }

    #[Test]
    #[DataProvider('modes')]
    public function family_units_partition_users(string $mode): void
    {
        [$a, $b] = $this->coupleWithSharedChild();
        $single = $this->makeParent();
        $resolver = $this->resolver($mode);

        $units = $resolver->familyUnits([$single, $b, $a]);

        $this->assertCount(2, $units);
        $couple = $units->first(fn (FamilyUnit $u) => $u->contains($a->id));
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $couple->userIds);
        $this->assertSame([$single->id], $units->first(fn (FamilyUnit $u) => $u->contains($single->id))->userIds);
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $resolver->familyUnitFor($b)->userIds);
    }

    #[Test]
    #[DataProvider('modes')]
    public function family_units_only_contain_given_users(string $mode): void
    {
        [$a] = $this->coupleWithSharedChild();
        $resolver = $this->resolver($mode);

        $units = $resolver->familyUnits([$a]);

        $this->assertCount(1, $units);
        $this->assertSame([$a->id], $units->first()->userIds);
    }

    #[Test]
    #[DataProvider('modes')]
    public function guardians_and_strangers_access_to_children(string $mode): void
    {
        [$a, $b, $child] = $this->coupleWithSharedChild();
        $stranger = $this->makeParent();
        $resolver = $this->resolver($mode);

        $this->assertTrue($resolver->childrenFor($a)->contains($child));
        $this->assertTrue($resolver->childrenFor($b)->contains($child));
        $this->assertTrue($resolver->hasAccessToChild($b, $child, GuardianRight::Manage));
        $this->assertFalse($resolver->childrenFor($stranger)->contains($child));
        $this->assertFalse($resolver->hasAccessToChild($stranger, $child));
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $resolver->guardiansFor($child)->modelKeys());
        $this->assertTrue($b->children()->contains($child));
    }

    #[Test]
    #[DataProvider('modes')]
    public function restricted_guardian_only_gets_granted_rights(string $mode): void
    {
        [$a, , $child] = $this->coupleWithSharedChild();
        $grandma = $this->makeParent();
        $this->linkGuardian($child, $grandma, GuardianRelation::Grandparent);
        $resolver = $this->resolver($mode);

        $this->assertTrue($resolver->hasAccessToChild($grandma, $child));
        $this->assertTrue($resolver->hasAccessToChild($grandma, $child, GuardianRight::Information));
        $this->assertFalse($resolver->hasAccessToChild($grandma, $child, GuardianRight::Manage));
        $this->assertFalse($resolver->hasAccessToChild($grandma, $child, GuardianRight::Custody));
        $this->assertFalse($resolver->guardiansFor($child, GuardianRight::Manage)->contains($grandma));
        $this->assertTrue($resolver->guardiansFor($child, GuardianRight::Information)->contains($grandma));
        $this->assertFalse($grandma->children(GuardianRight::Manage)->contains($child));
    }

    #[Test]
    #[DataProvider('modes')]
    public function expired_relation_gives_no_access(string $mode): void
    {
        $user = $this->makeParent();
        $child = $this->childFor([]);
        $this->linkGuardian($child, $user, GuardianRelation::FosterParent, ['valid_until' => now()->subDays(2)->toDateString()]);
        $resolver = $this->resolver($mode);

        $this->assertFalse($resolver->hasAccessToChild($user, $child));
        $this->assertFalse($resolver->guardiansFor($child)->contains($user));
    }

    #[Test]
    #[DataProvider('modes')]
    public function child_policy_follows_resolver(string $mode): void
    {
        [, $b, $child] = $this->coupleWithSharedChild();
        $grandma = $this->makeParent();
        $this->linkGuardian($child, $grandma, GuardianRelation::Grandparent);
        $stranger = $this->makeParent();
        $this->useResolver($mode);

        $this->assertTrue($b->can('manage', $child));
        $this->assertTrue($b->can('reportSick', $child));
        $this->assertTrue($b->can('answerFeedback', $child));
        $this->assertTrue($grandma->can('view', $child));
        $this->assertFalse($grandma->can('manage', $child));
        $this->assertFalse($grandma->can('answerFeedback', $child));
        $this->assertFalse($grandma->can('viewHealth', $child));
        $this->assertFalse($stranger->can('view', $child));
        $this->assertFalse($stranger->can('editGuardians', $child));
    }

    // ── Modus-spezifisches Verhalten ─────────────────────────────────────────

    #[Test]
    public function legacy_partner_inherits_children_without_direct_link(): void
    {
        [$a, $b, $child] = $this->coupleWithChildOfA();
        $resolver = $this->resolver(FamilyResolver::MODE_LEGACY);

        $this->assertTrue($resolver->hasAccessToChild($b, $child, GuardianRight::Manage));
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $resolver->guardiansFor($child)->modelKeys());
    }

    #[Test]
    public function child_centric_requires_direct_link(): void
    {
        [$a, $b, $child] = $this->coupleWithChildOfA();
        $resolver = $this->resolver(FamilyResolver::MODE_CHILD_CENTRIC);

        $this->assertFalse($resolver->hasAccessToChild($b, $child));
        $this->assertSame([$a->id], $resolver->guardiansFor($child)->modelKeys());
    }

    #[Test]
    public function child_centric_patchwork_keeps_children_apart(): void
    {
        $p = $this->patchwork();
        $resolver = $this->resolver(FamilyResolver::MODE_CHILD_CENTRIC);

        // B sieht nur das gemeinsame Kind X, nicht Y
        $this->assertEqualsCanonicalizing([$p['x']->id], $resolver->childrenFor($p['b'])->modelKeys());
        // C sieht Y (als Partner mit Verwaltungsrecht), aber kein Sorgerecht
        $this->assertTrue($resolver->hasAccessToChild($p['c'], $p['y'], GuardianRight::Manage));
        $this->assertFalse($resolver->hasAccessToChild($p['c'], $p['y'], GuardianRight::Custody));
        $this->assertFalse($resolver->hasAccessToChild($p['c'], $p['x']));
        // A sieht beide
        $this->assertEqualsCanonicalizing([$p['x']->id, $p['y']->id], $resolver->childrenFor($p['a'])->modelKeys());
        // Familien: {A, C} und {B}
        $this->assertEqualsCanonicalizing([$p['a']->id, $p['c']->id], $resolver->familyUserIds($p['a']));
        $this->assertSame([$p['b']->id], $resolver->familyUserIds($p['b']));
    }

    #[Test]
    public function child_centric_family_may_have_more_than_two_members(): void
    {
        $a = $this->makeParent();
        $b = $this->makeParent();
        $g = $this->makeParent();
        $family = $this->familyOf($a, $b, $g);
        $resolver = $this->resolver(FamilyResolver::MODE_CHILD_CENTRIC);

        $this->assertEqualsCanonicalizing([$a->id, $b->id, $g->id], $resolver->familyUserIds($g));
        $unit = $resolver->familyUnitFor($a);
        $this->assertSame($family->id, $unit->familyId);
        $this->assertSame($family->name, $unit->label);
        $this->assertSame($family->name, $resolver->familyLabel($b));
    }
}
