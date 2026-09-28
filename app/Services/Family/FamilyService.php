<?php

namespace App\Services\Family;

use App\Model\Family;
use App\Model\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Verwaltung von Familien (Anlegen, Mitglieder, Zusammenführen, Trennen).
 *
 * Während der Übergangsphase wird users.sorg2 für Familien mit zwei
 * Mitgliedern mitgepflegt (Dual-Write), damit FAMILY_RESOLVER=legacy als
 * Rollback funktioniert.
 *
 * @see docs/kind-zentriertes-familienmodell-konzept.md §5.3
 */
class FamilyService
{
    /**
     * @param  iterable<User>  $members
     */
    public function create(iterable $members, ?string $name = null, string $source = Family::SOURCE_MANUAL, bool $locked = false): Family
    {
        $members = collect($members)->unique('id')->values();

        return DB::transaction(function () use ($members, $name, $source, $locked) {
            $family = Family::create([
                'name' => $name ?: Family::suggestName($members),
                'source' => $source,
                'is_locked' => $locked,
            ]);

            foreach ($members as $member) {
                $this->moveUser($member, $family);
            }

            $this->syncLegacySorg2($family);

            return $family;
        });
    }

    public function addMember(Family $family, User $user): void
    {
        DB::transaction(function () use ($family, $user) {
            $this->moveUser($user, $family);
            $this->syncLegacySorg2($family);
        });
    }

    public function removeMember(User $user): void
    {
        DB::transaction(function () use ($user) {
            $this->moveUser($user, null);
        });
    }

    /**
     * Verknüpft zwei Personen als Familie (Ersatz für die frühere sorg2-Verknüpfung).
     */
    public function linkPair(User $a, User $b, string $source = Family::SOURCE_MANUAL): Family
    {
        if ($a->family_id !== null) {
            $family = Family::findOrFail($a->family_id);
            $this->addMember($family, $b);

            return $family->refresh();
        }

        if ($b->family_id !== null) {
            $family = Family::findOrFail($b->family_id);
            $this->addMember($family, $a);

            return $family->refresh();
        }

        return $this->create([$a, $b], null, $source);
    }

    /**
     * Führt $source in $target zusammen; das Ergebnis ist gesperrt (manuell gepflegt).
     */
    public function merge(Family $target, Family $source): Family
    {
        if ($target->is($source)) {
            return $target;
        }

        return DB::transaction(function () use ($target, $source) {
            foreach ($source->users()->get() as $member) {
                $this->moveUser($member, $target);
            }

            $target->update(['is_locked' => true, 'source' => Family::SOURCE_MANUAL]);
            $this->syncLegacySorg2($target);

            return $target->refresh();
        });
    }

    /**
     * Löst die angegebenen Mitglieder in eine neue Familie heraus; beide gesperrt.
     *
     * @param  list<int>  $userIds
     */
    public function split(Family $family, array $userIds, ?string $name = null): Family
    {
        return DB::transaction(function () use ($family, $userIds, $name) {
            $members = $family->users()->whereIn('id', $userIds)->get();
            $new = $this->create($members, $name, Family::SOURCE_MANUAL, true);

            $family->update(['is_locked' => true, 'source' => Family::SOURCE_MANUAL]);
            $this->syncLegacySorg2($family);

            return $new;
        });
    }

    public function setLocked(Family $family, bool $locked): void
    {
        $family->update(['is_locked' => $locked]);
    }

    public function rename(Family $family, string $name): void
    {
        $family->update(['name' => $name]);
    }

    /**
     * Setzt die Familie eines Users. Die alte Familie wird aufgeräumt
     * (leer → gelöscht, sonst sorg2 neu abgeglichen).
     */
    private function moveUser(User $user, ?Family $family): void
    {
        $oldFamilyId = $user->family_id;

        if ($oldFamilyId === $family?->id) {
            return;
        }

        $user->forceFill(['family_id' => $family?->id])->save();
        $user->forgetFamilyCache();

        // sorg2-Verweise auf/von diesem User lösen; werden unten neu gesetzt
        if (config('family.dual_write_sorg2')) {
            User::query()->where('sorg2', $user->id)->update(['sorg2' => null]);
            $user->forceFill(['sorg2' => null])->save();
        }

        if ($oldFamilyId !== null) {
            $old = Family::find($oldFamilyId);
            if ($old !== null) {
                if ($old->users()->count() === 0) {
                    $old->delete();
                } else {
                    $this->syncLegacySorg2($old);
                }
            }
        }
    }

    /**
     * Dual-Write: sorg2 spiegelt Familien mit genau zwei Mitgliedern; bei mehr
     * Mitgliedern werden die ersten beiden (nach ID) gekoppelt.
     */
    public function syncLegacySorg2(Family $family): void
    {
        if (! config('family.dual_write_sorg2')) {
            return;
        }

        /** @var Collection<int, User> $members */
        $members = $family->users()->orderBy('id')->get();
        $memberIds = $members->modelKeys();

        foreach ($members as $member) {
            // Verweise nach außen entfernen
            if ($member->sorg2 !== null && ! in_array((int) $member->sorg2, $memberIds, true)) {
                User::query()->where('id', $member->sorg2)->where('sorg2', $member->id)->update(['sorg2' => null]);
            }
        }

        User::query()->whereIn('id', $memberIds)->update(['sorg2' => null]);

        if ($members->count() >= 2) {
            [$first, $second] = [$members[0], $members[1]];
            User::query()->whereKey($first->id)->update(['sorg2' => $second->id]);
            User::query()->whereKey($second->id)->update(['sorg2' => $first->id]);
        }

        $members->each(fn (User $member) => $member->forgetFamilyCache());
    }
}
