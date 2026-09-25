<?php

namespace App\Services\Pflichtstunden;

use App\Model\User;
use Illuminate\Support\Collection;

/**
 * Pflichtstunden-Einheit: eine Familie – oder bei „combined“ mehrere Familien,
 * die ein Kind teilen – mit Soll, Ist und Beitrag.
 *
 * @see docs/kind-zentriertes-familienmodell-konzept.md §6.2
 */
final class PflichtstundenUnit
{
    /**
     * @param  list<int>  $userIds
     * @param  Collection<int, User>  $members
     * @param  list<string>  $familyKeys
     * @param  list<int>  $childIds
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly array $userIds,
        public readonly Collection $members,
        public readonly array $familyKeys,
        public readonly array $childIds,
        public readonly float $childShare,
        public readonly int $requiredMinutes,
        public readonly int $doneMinutes,
        public readonly float $hourlyRate,
    ) {}

    public function contains(int $userId): bool
    {
        return in_array($userId, $this->userIds, true);
    }

    public function openMinutes(): int
    {
        return max(0, $this->requiredMinutes - $this->doneMinutes);
    }

    public function percent(): float
    {
        if ($this->requiredMinutes <= 0) {
            return 100.0;
        }

        return (float) min(100, round(($this->doneMinutes / $this->requiredMinutes) * 100, 2));
    }

    public function beitrag(): float
    {
        return round(($this->openMinutes() / 60) * $this->hourlyRate, 2);
    }

    public function isCompleted(): bool
    {
        return $this->doneMinutes >= $this->requiredMinutes;
    }

    public function primaryMember(): ?User
    {
        return $this->members->sortBy('id')->first();
    }

    /**
     * Weitere Mitglieder (ohne Hauptperson) – für Anzeigen „Name + Partner“.
     *
     * @return Collection<int, User>
     */
    public function otherMembers(): Collection
    {
        $primaryId = $this->primaryMember()?->id;

        return $this->members->reject(fn (User $user) => $user->id === $primaryId)->sortBy('name')->values();
    }

    public function memberNames(): string
    {
        return $this->members->sortBy('name')->pluck('name')->implode(' / ');
    }
}
