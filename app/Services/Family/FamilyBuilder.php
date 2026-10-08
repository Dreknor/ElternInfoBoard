<?php

namespace App\Services\Family;

use App\Model\Family;
use App\Model\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Bildet Familien automatisch aus den Kind-Beziehungen (§7).
 *
 * Graph: Personen sind verbunden, wenn sie ein gemeinsames Kind haben.
 * Zusammenhangskomponenten werden nach diesen Regeln zu Familien:
 *
 * - 1 Person                          → eigene Familie
 * - 2 Personen                        → eine Familie
 * - ≥ 3 Personen, gleiche Kindermenge → eine Familie (z. B. Eltern + Großmutter)
 * - ≥ 3 Personen, sonst               → Klärungsfall (keine Änderung)
 *
 * Personen in gesperrten Familien (is_locked) werden nie verändert und
 * nehmen nicht an der Komponentenbildung teil.
 *
 * @see docs/kind-zentriertes-familienmodell-konzept.md §7
 */
class FamilyBuilder
{
    public function __construct(private readonly FamilyService $families) {}

    /**
     * @param  list<int>|null  $onlyUserIds  nur Komponenten, die einen dieser User enthalten
     * @param  bool  $onlyUnassigned  nur Personen ohne Familie zuordnen, bestehende Familien nicht umbauen
     */
    public function rebuild(?array $onlyUserIds = null, bool $onlyUnassigned = false, bool $dryRun = false, string $source = Family::SOURCE_AUTO): FamilyBuildReport
    {
        $report = new FamilyBuildReport($dryRun);

        foreach ($this->components() as $component) {
            if ($onlyUserIds !== null && array_intersect($component['userIds'], $onlyUserIds) === []) {
                continue;
            }

            $users = User::query()->whereIn('id', $component['userIds'])->get();

            if (! $this->isDecidable($component)) {
                $report->reviewCases[] = $component;

                continue;
            }

            $this->applyComponent($users, $onlyUnassigned, $dryRun, $source, $report);
        }

        return $report;
    }

    /**
     * Klärungsfälle: Komponenten mit ≥ 3 Personen und unterschiedlichen Kindermengen.
     *
     * @return list<array{userIds: list<int>, childIds: list<int>, childSets: array<int, list<int>>}>
     */
    public function reviewCases(): array
    {
        return array_values(array_filter($this->components(), fn (array $c) => ! $this->isDecidable($c)));
    }

    /**
     * Zusammenhangskomponenten über gemeinsame Kinder (gesperrte Familien ausgenommen).
     *
     * @return list<array{userIds: list<int>, childIds: list<int>, childSets: array<int, list<int>>}>
     */
    public function components(): array
    {
        $lockedUserIds = User::query()
            ->whereIn('family_id', Family::query()->where('is_locked', true)->select('id'))
            ->pluck('id')
            ->all();

        $links = DB::table('child_user')
            ->join('children', 'children.id', '=', 'child_user.child_id')
            ->join('users', 'users.id', '=', 'child_user.user_id')
            ->whereNull('children.deleted_at')
            ->whereNull('users.deleted_at')
            ->whereNotIn('child_user.user_id', $lockedUserIds)
            ->where(fn ($q) => $q->whereNull('child_user.valid_until')->orWhereDate('child_user.valid_until', '>=', today()))
            ->get(['child_user.user_id', 'child_user.child_id']);

        $childSets = [];
        $guardiansOfChild = [];
        foreach ($links as $link) {
            $childSets[(int) $link->user_id][] = (int) $link->child_id;
            $guardiansOfChild[(int) $link->child_id][] = (int) $link->user_id;
        }

        // Union-Find über Personen
        $parent = [];
        $find = function (int $id) use (&$parent, &$find): int {
            if (($parent[$id] ?? $id) === $id) {
                return $id;
            }

            return $parent[$id] = $find($parent[$id]);
        };
        foreach ($guardiansOfChild as $guardians) {
            $root = $find($guardians[0]);
            foreach (array_slice($guardians, 1) as $guardian) {
                $other = $find($guardian);
                if ($other !== $root) {
                    $parent[$other] = $root;
                }
            }
        }

        $grouped = [];
        foreach (array_keys($childSets) as $userId) {
            $grouped[$find($userId)][] = $userId;
        }

        $components = [];
        foreach ($grouped as $userIds) {
            sort($userIds);
            $sets = [];
            $allChildren = [];
            foreach ($userIds as $userId) {
                $set = array_values(array_unique($childSets[$userId]));
                sort($set);
                $sets[$userId] = $set;
                array_push($allChildren, ...$set);
            }
            $allChildren = array_values(array_unique($allChildren));
            sort($allChildren);

            $components[] = ['userIds' => $userIds, 'childIds' => $allChildren, 'childSets' => $sets];
        }

        usort($components, fn ($a, $b) => $a['userIds'][0] <=> $b['userIds'][0]);

        return $components;
    }

    private function isDecidable(array $component): bool
    {
        if (count($component['userIds']) <= 2) {
            return true;
        }

        return count(array_unique(array_map('serialize', $component['childSets']))) === 1;
    }

    /**
     * @param  Collection<int, User>  $users
     */
    private function applyComponent(Collection $users, bool $onlyUnassigned, bool $dryRun, string $source, FamilyBuildReport $report): void
    {
        $familyIds = $users->pluck('family_id')->filter()->unique()->values();

        // Bereits genau eine gemeinsame Familie → nichts zu tun
        if ($familyIds->count() === 1 && $users->every(fn (User $u) => $u->family_id === $familyIds->first())) {
            $report->unchanged[] = $users->modelKeys();

            return;
        }

        if ($onlyUnassigned && $familyIds->isNotEmpty()) {
            $unassigned = $users->whereNull('family_id');
            if ($unassigned->isEmpty()) {
                $report->unchanged[] = $users->modelKeys();

                return;
            }

            // Nur zuordnen, wenn die Komponente bisher genau eine Familie hat
            if ($familyIds->count() > 1) {
                $report->reviewCases[] = ['userIds' => $users->modelKeys(), 'childIds' => [], 'childSets' => []];

                return;
            }

            $report->assigned[] = $unassigned->modelKeys();
            if (! $dryRun) {
                $family = Family::findOrFail($familyIds->first());
                $unassigned->each(fn (User $user) => $this->families->addMember($family, $user));
            }

            return;
        }

        if ($familyIds->isEmpty()) {
            $report->created[] = $users->modelKeys();
            if (! $dryRun) {
                $this->families->create($users, null, $source);
            }

            return;
        }

        // Personen in unterschiedliche/teilweise Familien → in die erste vorhandene zusammenführen
        $report->merged[] = $users->modelKeys();
        if (! $dryRun) {
            $target = Family::findOrFail($familyIds->first());
            foreach ($users as $user) {
                if ($user->family_id !== $target->id) {
                    $this->families->addMember($target, $user);
                }
            }
        }
    }
}
