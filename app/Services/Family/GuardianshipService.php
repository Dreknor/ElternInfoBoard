<?php

namespace App\Services\Family;

use App\Enums\GuardianRelation;
use App\Model\Child;
use App\Model\ChildGuardian;
use App\Model\User;
use Illuminate\Support\Facades\DB;

/**
 * Schreibt Beziehungen Kind ↔ Bezugsperson (child_user) und hält die daraus
 * abgeleiteten Gruppenmitgliedschaften aktuell.
 *
 * Beziehungen pflegt ausschließlich die Verwaltung (E6); Importe und UCS
 * nutzen denselben Service.
 *
 * @see docs/kind-zentriertes-familienmodell-konzept.md §5.2
 */
class GuardianshipService
{
    public function __construct(
        private readonly GroupMembershipService $groups,
        private readonly FamilyResolver $resolver,
    ) {}

    /**
     * Legt eine Beziehung an oder aktualisiert sie.
     *
     * Bei einer neuen Beziehung gelten die Standardrechte der Beziehungsart,
     * sofern $rights nichts anderes vorgibt. Bestehende Beziehungen behalten
     * ihre Rechte, außer sie werden explizit übergeben.
     *
     * @param  array{has_custody?: bool, receives_information?: bool, can_manage?: bool, valid_until?: ?string}  $rights
     */
    public function link(
        Child $child,
        User $user,
        GuardianRelation $relation = GuardianRelation::LegalGuardian,
        array $rights = [],
        string $source = ChildGuardian::SOURCE_MANUAL,
        bool $autoProvisioned = false,
        bool $syncGroups = true,
    ): ChildGuardian {
        $existing = $this->pivot($child, $user);

        if ($existing === null) {
            $child->parents()->attach($user->id, array_merge(
                $relation->defaultRights(),
                $rights,
                [
                    'relation' => $relation->value,
                    'source' => $source,
                    'is_auto_provisioned' => $autoProvisioned,
                    'synced_at' => $autoProvisioned ? now() : null,
                ],
            ));
        } elseif ($rights !== [] || $autoProvisioned) {
            $child->parents()->updateExistingPivot($user->id, array_merge(
                $rights,
                $autoProvisioned && $existing->is_auto_provisioned ? ['synced_at' => now()] : [],
            ));
        }

        $this->afterChange($user, $syncGroups);

        return $this->pivot($child, $user);
    }

    public function unlink(Child $child, User $user, bool $syncGroups = true): void
    {
        $child->parents()->detach($user->id);
        $this->afterChange($user, $syncGroups);
    }

    /**
     * Beziehungsart und/oder Rechte ändern.
     *
     * @param  array{relation?: string, has_custody?: bool, receives_information?: bool, can_manage?: bool, valid_until?: ?string}  $attributes
     */
    public function update(Child $child, User $user, array $attributes): ChildGuardian
    {
        $allowed = array_intersect_key($attributes, array_flip([
            'relation', 'has_custody', 'receives_information', 'can_manage', 'valid_until',
        ]));

        if (isset($allowed['relation'])) {
            $allowed['relation'] = GuardianRelation::fromInput($allowed['relation'])->value;
        }

        $child->parents()->updateExistingPivot($user->id, $allowed);
        $this->afterChange($user);

        return $this->pivot($child, $user);
    }

    /**
     * Setzt die Rechte auf die Standardrechte der aktuellen Beziehungsart zurück.
     */
    public function applyDefaultRights(Child $child, User $user): ChildGuardian
    {
        $pivot = $this->pivot($child, $user);

        return $this->update($child, $user, $pivot->relationType()->defaultRights());
    }

    /**
     * Übernommene Beziehung (E3) als geprüft markieren.
     */
    public function markReviewed(Child $child, User $user): void
    {
        $child->parents()->updateExistingPivot($user->id, ['reviewed_at' => now()]);
    }

    /**
     * Abgleich einer Quelle (UCS, Import): gewünschte Kinder verknüpfen; mit
     * $detach werden automatisch angelegte Beziehungen dieser Quelle entfernt,
     * die nicht mehr gewünscht sind. Manuelle Beziehungen bleiben unberührt.
     *
     * @param  list<int>  $desiredChildIds
     * @return array{attached: list<int>, detached: list<int>}
     */
    public function syncFromSource(User $user, array $desiredChildIds, string $source, bool $detach, bool $syncGroups = true): array
    {
        $existing = DB::table('child_user')
            ->where('user_id', $user->id)
            ->get(['child_id', 'is_auto_provisioned', 'source'])
            ->keyBy('child_id');

        $detached = [];
        if ($detach) {
            $detached = $existing
                ->filter(fn ($row) => (bool) $row->is_auto_provisioned && $row->source === $source
                    && ! in_array((int) $row->child_id, $desiredChildIds, true))
                ->keys()
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            if ($detached !== []) {
                DB::table('child_user')->where('user_id', $user->id)->whereIn('child_id', $detached)->delete();
            }
        }

        $attached = [];
        foreach (array_unique($desiredChildIds) as $childId) {
            $row = $existing->get($childId);
            if ($row === null) {
                $attached[] = $childId;
                DB::table('child_user')->insert(array_merge(
                    GuardianRelation::LegalGuardian->defaultRights(),
                    [
                        'child_id' => $childId,
                        'user_id' => $user->id,
                        'relation' => GuardianRelation::LegalGuardian->value,
                        'source' => $source,
                        'is_auto_provisioned' => true,
                        'synced_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ));
            } elseif ((bool) $row->is_auto_provisioned) {
                DB::table('child_user')->where('user_id', $user->id)->where('child_id', $childId)
                    ->update(['synced_at' => now()]);
            }
        }

        if ($attached !== [] || $detached !== []) {
            $this->afterChange($user, $syncGroups);
        }

        return ['attached' => $attached, 'detached' => $detached];
    }

    public function pivot(Child $child, User $user): ?ChildGuardian
    {
        return $child->parents()->where('users.id', $user->id)->first()?->pivot;
    }

    private function afterChange(User $user, bool $syncGroups = true): void
    {
        $user->forgetFamilyCache();

        if (! $syncGroups) {
            return;
        }

        $this->groups->syncDerivedGroups($user);

        // Legacy: der sorg2-Partner erbt die Kinder und damit auch die Gruppen
        if ($this->resolver->mode() === FamilyResolver::MODE_LEGACY && $user->sorg2) {
            $this->groups->syncUsers([(int) $user->sorg2]);
        }
    }
}
