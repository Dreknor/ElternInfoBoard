<?php

namespace App\Services\Family;

/**
 * Ergebnis eines FamilyBuilder-Laufs (auch im Dry-Run).
 */
final class FamilyBuildReport
{
    /** @var list<list<int>> neu angelegte Familien (User-IDs) */
    public array $created = [];

    /** @var list<list<int>> in bestehende Familie zusammengeführt */
    public array $merged = [];

    /** @var list<list<int>> Personen ohne Familie einer bestehenden zugeordnet */
    public array $assigned = [];

    /** @var list<list<int>> bereits korrekt */
    public array $unchanged = [];

    /** @var list<array{userIds: list<int>, childIds: list<int>, childSets: array<int, list<int>>}> */
    public array $reviewCases = [];

    public function __construct(public readonly bool $dryRun) {}

    public function summary(): array
    {
        return [
            'neu' => count($this->created),
            'zusammengeführt' => count($this->merged),
            'zugeordnet' => count($this->assigned),
            'unverändert' => count($this->unchanged),
            'Klärungsfälle' => count($this->reviewCases),
        ];
    }
}
