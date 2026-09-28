<?php

namespace App\Services\App;

use App\Enums\GuardianRight;
use App\Model\Child;
use App\Model\User;
use App\Services\Family\FamilyResolver;
use Illuminate\Support\Collection;

/**
 * Familienlogik der App-API an einer Stelle (B-80).
 *
 * Delegiert an den FamilyResolver: im Modus "legacy" gilt weiterhin die
 * sorg2-Verknüpfung, im Modus "child_centric" Familien (users.family_id) und
 * direkte Kind-Beziehungen mit Rechten (child_user). Die Controller bleiben
 * unverändert.
 */
class Family
{
    private static function resolver(): FamilyResolver
    {
        return app(FamilyResolver::class);
    }

    /** IDs aller Konten der Familie (für Buchungen, Rückmeldungen, Pflichtstunden). */
    public static function userIds(User $user): array
    {
        return $user->familyUserIds();
    }

    /** Alle Kinder, auf die der Nutzer zugreifen darf (optional nur mit einem Recht). */
    public static function children(User $user, ?GuardianRight $right = null): Collection
    {
        return self::resolver()->childrenFor($user, $right)->unique('id')->values();
    }

    public static function childIds(User $user, ?GuardianRight $right = null): array
    {
        return self::children($user, $right)->pluck('id')->all();
    }

    /** Irgendeine Beziehung zum Kind (Ansehen). */
    public static function ownsChild(User $user, Child|int $child): bool
    {
        return self::has($user, $child, null);
    }

    /** Krankmelden, Schickzeiten, Notizen, Vollmachten, AG-Anmeldung. */
    public static function mayManage(User $user, Child|int $child): bool
    {
        return self::has($user, $child, GuardianRight::Manage);
    }

    /**
     * Rechte des Nutzers am Kind – steuert in der App die Buttons
     * (Krankmeldung/Schickzeiten: manage, Krankmeldungs-Verlauf: view_health).
     */
    public static function rights(User $user, Child $child): array
    {
        $resolver = self::resolver();

        return [
            'manage' => $resolver->hasAccessToChild($user, $child, GuardianRight::Manage),
            'view_health' => $user->can('viewHealth', $child),
            'custody' => $resolver->hasAccessToChild($user, $child, GuardianRight::Custody),
            'information' => $resolver->hasAccessToChild($user, $child, GuardianRight::Information),
        ];
    }

    private static function has(User $user, Child|int $child, ?GuardianRight $right): bool
    {
        $child = $child instanceof Child ? $child : Child::find($child);

        return $child !== null && self::resolver()->hasAccessToChild($user, $child, $right);
    }
}
