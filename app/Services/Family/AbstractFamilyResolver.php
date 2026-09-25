<?php

namespace App\Services\Family;

use App\Enums\GuardianRight;
use App\Model\Child;
use App\Model\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Gemeinsame Logik beider Resolver: Zugriff auf Kinder läuft immer über
 * child_user; die Resolver unterscheiden sich darin, wessen Verknüpfungen
 * zählen (nur eigene vs. eigene + sorg2-Partner).
 */
abstract class AbstractFamilyResolver implements FamilyResolver
{
    /**
     * User-IDs, deren Kind-Verknüpfungen $user Zugriff geben.
     *
     * @return list<int>
     */
    abstract protected function childAccessUserIds(User $user): array;

    public function isSameFamily(User $user, User|int|null $other): bool
    {
        if ($other === null) {
            return false;
        }

        $otherId = $other instanceof User ? $other->id : $other;

        return in_array($otherId, $this->familyUserIds($user), true);
    }

    /**
     * Query auf die Kinder, auf die $user Zugriff hat.
     */
    public function childrenQuery(User $user, ?GuardianRight $right = null): Builder
    {
        $userIds = $this->childAccessUserIds($user);

        return Child::query()->whereHas('parents', function (Builder $query) use ($userIds, $right) {
            $query->whereIn('users.id', $userIds);
            static::constrainPivot($query, $right);
        });
    }

    public function childrenFor(User $user, ?GuardianRight $right = null): EloquentCollection
    {
        return $this->childrenQuery($user, $right)->get();
    }

    public function hasAccessToChild(User $user, Child $child, ?GuardianRight $right = null): bool
    {
        return $this->childrenQuery($user, $right)->whereKey($child->getKey())->exists();
    }

    public function familyUnitFor(User $user): FamilyUnit
    {
        $members = User::query()->whereIn('id', $this->familyUserIds($user))->get();

        return $this->familyUnits($members->push($user)->unique('id'))
            ->first(fn (FamilyUnit $unit) => $unit->contains($user->id));
    }

    /**
     * Direkte Bezugspersonen des Kindes (mit Recht/Gültigkeit gefiltert).
     */
    protected function directGuardiansRelation(Child $child, ?GuardianRight $right): BelongsToMany
    {
        $relation = $child->parents();
        static::constrainPivot($relation, $right);

        return $relation;
    }

    /**
     * Filtert child_user auf gültige Beziehungen mit dem gewünschten Recht.
     */
    public static function constrainPivot(Builder|BelongsToMany $query, ?GuardianRight $right): void
    {
        $query->where(function ($q) {
            $q->whereNull('child_user.valid_until')
                ->orWhereDate('child_user.valid_until', '>=', today());
        });

        if ($right !== null) {
            $query->where('child_user.'.$right->column(), true);
        }
    }
}
