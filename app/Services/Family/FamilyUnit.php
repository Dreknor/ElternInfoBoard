<?php

namespace App\Services\Family;

/**
 * Eine Familie als Einheit (Pflichtstunden, Reinigung, Rückmeldungen, Zählungen).
 *
 * Im Legacy-Modus ist $familyId null und $key aus den User-IDs gebildet.
 */
final class FamilyUnit
{
    /**
     * @param  list<int>  $userIds
     */
    public function __construct(
        public readonly string $key,
        public readonly ?int $familyId,
        public readonly string $label,
        public readonly array $userIds,
    ) {}

    public function contains(int $userId): bool
    {
        return in_array($userId, $this->userIds, true);
    }

    /**
     * Erstes Mitglied (stabil nach ID) – Repräsentant für Anzeigen.
     */
    public function primaryUserId(): int
    {
        return min($this->userIds);
    }
}
