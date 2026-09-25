<?php

namespace Tests\Concerns;

use App\Model\Child;
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
