<?php

namespace App\Services\Family;

use App\Enums\GuardianRight;
use App\Model\Child;
use App\Model\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

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

    /**
     * Sammelabfrage: Kind-IDs je User (für Statistiken ohne N+1).
     *
     * @param  iterable<User>  $users
     * @return array<int, list<int>> userId => childIds
     */
    public function childIdsByUser(iterable $users, ?GuardianRight $right = null): array
    {
        $users = collect($users);
        $accessIds = $users->mapWithKeys(fn (User $user) => [$user->id => $this->childAccessUserIds($user)]);
        $allIds = $accessIds->flatten()->unique()->values()->all();

        if ($allIds === []) {
            return [];
        }

        $query = DB::table('child_user')
            ->join('children', 'children.id', '=', 'child_user.child_id')
            ->whereNull('children.deleted_at')
            ->whereIn('child_user.user_id', $allIds);
        static::constrainPivot($query, $right);

        $childIdsByLinkUser = $query->get(['child_user.user_id', 'child_user.child_id'])
            ->groupBy('user_id')
            ->map(fn ($rows) => $rows->pluck('child_id')->map(fn ($id) => (int) $id)->all());

        $result = [];
        foreach ($accessIds as $userId => $ids) {
            $childIds = [];
            foreach ($ids as $id) {
                array_push($childIds, ...($childIdsByLinkUser->get($id) ?? []));
            }
            $result[$userId] = array_values(array_unique($childIds));
        }

        return $result;
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
    public static function constrainPivot(Builder|BelongsToMany|QueryBuilder $query, ?GuardianRight $right): void
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
