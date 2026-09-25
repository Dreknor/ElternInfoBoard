<?php

namespace Tests\Concerns;

use App\Enums\GuardianRelation;
use App\Model\Child;
use App\Model\Family;
use App\Model\User;

/**
 * Szenario-Bausteine für Familien-Tests.
 *
 * @see docs/kind-zentriertes-familienmodell-konzept.md §13.1
 */
trait BuildsFamilies
{
    protected function makeParent(array $attributes = []): User
    {
        return User::factory()->create($attributes + ['password_changed_at' => now()]);
    }

    /**
     * Koppelt zwei Konten als Familie (heute: symmetrisches sorg2).
     */
    protected function linkPartners(User $a, User $b): void
    {
        $a->update(['sorg2' => $b->id]);
        $b->update(['sorg2' => $a->id]);
        $a->refresh();
        $b->refresh();
    }

    /**
     * Klassisches Elternpaar: A und B gekoppelt, Kind nur an A verknüpft
     * (typischer Altbestand – B „erbt“ das Kind über sorg2).
     *
     * @return array{0: User, 1: User, 2: Child}
     */
    protected function coupleWithChildOfA(array $childAttributes = []): array
    {
        $a = $this->makeParent();
        $b = $this->makeParent();
        $this->linkPartners($a, $b);
        $child = $this->childFor([$a], $childAttributes);

        return [$a, $b, $child];
    }

    protected function useResolver(string $mode): void
    {
        config(['family.resolver' => $mode]);
    }

    /**
     * Legt eine Familie an. Bei genau zwei Mitgliedern wird zusätzlich sorg2
     * gesetzt (Dual-Write), damit das Szenario in beiden Modi gleich gilt.
     */
    protected function familyOf(User ...$members): Family
    {
        $family = Family::factory()->create(['name' => Family::suggestName($members)]);
        foreach ($members as $member) {
            $member->update(['family_id' => $family->id]);
        }
        if (count($members) === 2) {
            $this->linkPartners($members[0], $members[1]);
        }
        foreach ($members as $member) {
            $member->refresh();
        }

        return $family;
    }

    /**
     * Elternpaar mit gemeinsamem Kind – in beiden Resolver-Modi gleichwertig
     * (Familie + sorg2, Kind direkt an beiden).
     *
     * @return array{0: User, 1: User, 2: Child, 3: Family}
     */
    protected function coupleWithSharedChild(array $childAttributes = []): array
    {
        $a = $this->makeParent();
        $b = $this->makeParent();
        $family = $this->familyOf($a, $b);
        $child = $this->childFor([$a, $b], $childAttributes);

        return [$a, $b, $child, $family];
    }

    /**
     * Patchwork (§1): A hat Kind X mit B (getrennt lebend) und Kind Y mit
     * Partner C. Familien: {A, C} und {B}.
     *
     * @return array{a: User, b: User, c: User, x: Child, y: Child, familyAC: Family, familyB: Family}
     */
    protected function patchwork(): array
    {
        $a = $this->makeParent();
        $b = $this->makeParent();
        $c = $this->makeParent();
        $familyAC = $this->familyOf($a, $c);
        $familyB = $this->familyOf($b);
        $x = $this->childFor([$a, $b]);
        $y = $this->childFor([$a]);
        $y->parents()->attach($c->id, ['relation' => GuardianRelation::Partner->value] + GuardianRelation::Partner->defaultRights());

        return ['a' => $a, 'b' => $b, 'c' => $c, 'x' => $x, 'y' => $y, 'familyAC' => $familyAC, 'familyB' => $familyB];
    }

    protected function linkGuardian(Child $child, User $user, GuardianRelation $relation = GuardianRelation::LegalGuardian, array $pivot = []): void
    {
        $child->parents()->attach($user->id, $pivot + ['relation' => $relation->value] + $relation->defaultRights());
    }

    /**
     * Care-Modul auf die angegebenen Gruppen/Klassen einschränken.
     */
    protected function configureCare(array $groupIds, array $classIds): void
    {
        \DB::table('settings')->where('group', 'care')->where('name', 'groups_list')
            ->update(['payload' => json_encode(array_values($groupIds))]);
        \DB::table('settings')->where('group', 'care')->where('name', 'class_list')
            ->update(['payload' => json_encode(array_values($classIds))]);
        $this->app->forgetScopedInstances();
    }

    /**
     * @param  list<User>  $guardians
     */
    protected function childFor(array $guardians, array $attributes = []): Child
    {
        $child = Child::factory()->create($attributes);
        foreach ($guardians as $guardian) {
            $child->parents()->attach($guardian->id);
        }

        return $child;
    }
}
