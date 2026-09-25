<?php

namespace App\Services\Family;

use App\Enums\GuardianRight;
use App\Model\Child;
use App\Model\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Bildet das bisherige Verhalten nach: Familie = User + users.sorg2,
 * der Partner erbt den Zugriff auf alle Kinder des verknüpften Kontos.
 *
 * Nur für die Übergangsphase und als Rollback-Pfad (FAMILY_RESOLVER=legacy).
 */
class LegacySorg2FamilyResolver extends AbstractFamilyResolver
{
    public function mode(): string
    {
        return self::MODE_LEGACY;
    }

    public function familyUserIds(User $user): array
    {
        $ids = [$user->id];

        if ($user->sorg2 !== null && (int) $user->sorg2 !== $user->id) {
            $ids[] = (int) $user->sorg2;
        }

        return $ids;
    }

    protected function childAccessUserIds(User $user): array
    {
        return $this->familyUserIds($user);
    }

    public function guardiansFor(Child $child, ?GuardianRight $right = null): EloquentCollection
    {
        $direct = $this->directGuardiansRelation($child, $right)->get();

        $partnerIds = $direct->pluck('sorg2')->filter()->map(fn ($id) => (int) $id)
            ->diff($direct->modelKeys())
            ->values();

        if ($partnerIds->isEmpty()) {
            return $direct;
        }

        return $direct->merge(User::query()->whereIn('id', $partnerIds)->get())->values();
    }

    public function familyUnits(iterable $users): Collection
    {
        $users = collect($users)->unique('id')->sortBy('id')->keyBy('id');
        $processed = [];
        $units = collect();

        foreach ($users as $user) {
            if (isset($processed[$user->id])) {
                continue;
            }

            $memberIds = [$user->id];
            $partnerId = $user->sorg2 !== null ? (int) $user->sorg2 : null;

            if ($partnerId !== null && $partnerId !== $user->id && $users->has($partnerId) && ! isset($processed[$partnerId])) {
                $memberIds[] = $partnerId;
                $processed[$partnerId] = true;
            }

            $processed[$user->id] = true;
            sort($memberIds);

            $units->push(new FamilyUnit(
                key: 'u'.implode('-', $memberIds),
                familyId: null,
                label: $user->name,
                userIds: $memberIds,
            ));
        }

        return $units->values();
    }

    public function familyLabel(User $user): string
    {
        return $user->name;
    }
}
