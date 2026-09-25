<?php

namespace App\Services\App;

use App\Model\Child;
use App\Model\User;
use Illuminate\Support\Collection;

/**
 * Familienlogik der App-API an einer Stelle.
 *
 * Heute: Familie = Nutzer + verknüpfter Sorgeberechtigter (`users.sorg2`).
 * Nach dem Merge von `feat/familienmodell` wird hier auf den FamilyResolver umgestellt
 * (B-80) – die Controller bleiben unverändert.
 */
class Family
{
    /** IDs aller Konten der Familie (für Buchungen, Rückmeldungen, Pflichtstunden). */
    public static function userIds(User $user): array
    {
        return array_values(array_unique(array_filter([$user->id, $user->sorg2])));
    }

    /** Alle Kinder, auf die der Nutzer zugreifen darf. */
    public static function children(User $user): Collection
    {
        return collect($user->children() ?? [])->unique('id')->values();
    }

    public static function childIds(User $user): array
    {
        return self::children($user)->pluck('id')->all();
    }

    public static function ownsChild(User $user, Child|int $child): bool
    {
        $id = $child instanceof Child ? $child->id : $child;

        return in_array($id, self::childIds($user), true);
    }

    /**
     * Rechte des Nutzers am Kind. Heute haben alle verknüpften Eltern alle Rechte;
     * mit dem Familienmodell kommen hier `relation`/`has_custody` ins Spiel.
     */
    public static function rights(User $user, Child $child): array
    {
        return ['manage' => true, 'view_health' => true];
    }
}
