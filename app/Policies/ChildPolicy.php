<?php

namespace App\Policies;

use App\Enums\GuardianRight;
use App\Model\Child;
use App\Model\User;
use App\Services\Family\FamilyResolver;

/**
 * Zugriff auf Kinder und kindbezogene Daten.
 *
 * Personal mit „edit schickzeiten“ (Care) darf alle Kinder sehen und verwalten.
 * Eltern/Bezugspersonen nur über ihre Beziehung – welche Beziehungen zählen,
 * entscheidet der FamilyResolver (legacy: inkl. sorg2-Partner).
 *
 * @see docs/kind-zentriertes-familienmodell-konzept.md §5.5
 */
class ChildPolicy
{
    public function __construct(private readonly FamilyResolver $resolver) {}

    public function view(User $user, Child $child): bool
    {
        return $this->isCareStaff($user) || $this->resolver->hasAccessToChild($user, $child);
    }

    /**
     * Schickzeiten, Anwesenheit, Vollmachten, Notizen, Benachrichtigungen.
     */
    public function manage(User $user, Child $child): bool
    {
        return $this->isCareStaff($user) || $this->resolver->hasAccessToChild($user, $child, GuardianRight::Manage);
    }

    public function reportSick(User $user, Child $child): bool
    {
        return $this->manage($user, $child);
    }

    /**
     * Gesundheitsdaten (Krankmeldungen) – Art. 9 DSGVO.
     */
    public function viewHealth(User $user, Child $child): bool
    {
        if ($this->isCareStaff($user) || $user->can('download krankmeldungen')) {
            return true;
        }

        return $this->resolver->hasAccessToChild($user, $child, GuardianRight::Custody)
            || $this->resolver->hasAccessToChild($user, $child, GuardianRight::Manage);
    }

    /**
     * Rückmeldung pro Kind: ausschließlich Sorgeberechtigte, kein Override (E7).
     */
    public function answerFeedback(User $user, Child $child): bool
    {
        return $this->resolver->hasAccessToChild($user, $child, GuardianRight::Custody);
    }

    /**
     * Beziehungen pflegen – nur Admin (E6).
     */
    public function editGuardians(User $user, Child $child): bool
    {
        return $user->can('manage families');
    }

    private function isCareStaff(User $user): bool
    {
        return $user->can('edit schickzeiten');
    }
}
