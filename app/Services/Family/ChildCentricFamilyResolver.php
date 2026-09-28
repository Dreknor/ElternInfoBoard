<?php

namespace App\Services\Family;

use App\Enums\GuardianRight;
use App\Model\Child;
use App\Model\Family;
use App\Model\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Kind-zentriertes Modell: Zugriff auf ein Kind nur über eine eigene,
 * gültige Beziehung (child_user) mit dem passenden Recht; Familie über
 * users.family_id (beliebig viele Mitglieder).
 */
class ChildCentricFamilyResolver extends AbstractFamilyResolver
{
    public function mode(): string
    {
        return self::MODE_CHILD_CENTRIC;
    }

    public function familyUserIds(User $user): array
    {
        if ($user->family_id === null) {
            return [$user->id];
        }

        $ids = User::query()
            ->where('family_id', $user->family_id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (! in_array($user->id, $ids, true)) {
            $ids[] = $user->id;
        }

        sort($ids);

        return $ids;
    }

    protected function childAccessUserIds(User $user): array
    {
        return [$user->id];
    }

    public function guardiansFor(Child $child, ?GuardianRight $right = null): EloquentCollection
    {
        return $this->directGuardiansRelation($child, $right)->get();
    }

    public function familyUnits(iterable $users): Collection
    {
        $users = collect($users)->unique('id')->sortBy('id');
        $familyIds = $users->pluck('family_id')->filter()->unique()->values();
        $families = Family::query()->whereIn('id', $familyIds)->get()->keyBy('id');

        return $users
            ->groupBy(fn (User $user) => $user->family_id !== null ? 'f'.$user->family_id : 'u'.$user->id)
            ->map(function (Collection $members, string $key) use ($families) {
                $first = $members->first();
                $family = $first->family_id !== null ? $families->get($first->family_id) : null;

                return new FamilyUnit(
                    key: $key,
                    familyId: $family?->id,
                    label: $family?->name ?? $first->name,
                    userIds: $members->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all(),
                );
            })
            ->values();
    }

    public function familyLabel(User $user): string
    {
        return $user->family?->name ?? $user->name;
    }
}
