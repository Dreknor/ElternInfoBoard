<?php

namespace App\Services\Family;

use App\Enums\GuardianRight;
use App\Model\Child;
use App\Model\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Einzige Stelle, die entscheidet, wer zu einer Familie gehört und wer auf
 * welches Kind zugreifen darf. Alle Aufrufer fragen nur diese Schnittstelle.
 *
 * @see docs/kind-zentriertes-familienmodell-konzept.md §5.1
 */
interface FamilyResolver
{
    public const MODE_LEGACY = 'legacy';

    public const MODE_CHILD_CENTRIC = 'child_centric';

    public function mode(): string;

    /**
     * Alle User-IDs der Familie von $user (inkl. $user selbst).
     *
     * @return list<int>
     */
    public function familyUserIds(User $user): array;

    public function isSameFamily(User $user, User|int|null $other): bool;

    /**
     * Query auf die Kinder, auf die $user Zugriff hat (für Eager Loading/Filter).
     */
    public function childrenQuery(User $user, ?GuardianRight $right = null): Builder;

    /**
     * Kinder, auf die $user Zugriff hat – optional nur mit einem bestimmten Recht.
     *
     * @return EloquentCollection<int, Child>
     */
    public function childrenFor(User $user, ?GuardianRight $right = null): EloquentCollection;

    public function hasAccessToChild(User $user, Child $child, ?GuardianRight $right = null): bool;

    /**
     * Personen mit Zugriff auf das Kind – optional nur mit einem bestimmten Recht.
     *
     * @return EloquentCollection<int, User>
     */
    public function guardiansFor(Child $child, ?GuardianRight $right = null): EloquentCollection;

    /**
     * Teilt die übergebenen User in Familien-Einheiten auf. Mitglieder, die nicht
     * in $users enthalten sind, werden nicht ergänzt.
     *
     * @param  iterable<User>  $users
     * @return Collection<int, FamilyUnit>
     */
    public function familyUnits(iterable $users): Collection;

    public function familyUnitFor(User $user): FamilyUnit;

    public function familyLabel(User $user): string;
}
