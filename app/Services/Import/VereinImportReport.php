<?php

namespace App\Services\Import;

/**
 * Ergebnis eines Vereins-Imports (auch im Dry-Run).
 */
final class VereinImportReport
{
    public int $rows = 0;

    /** Zeilen, die einem bestehenden Konto zugeordnet wurden */
    public int $matchedRows = 0;

    /** Bestehende Mitglieder, bei denen alles bereits stimmt */
    public int $unchanged = 0;

    /** Nicht mehr in der Datei, aber behalten (Abgleich ausgeschaltet) */
    public int $kept = 0;

    /** @var list<array{name: string, email: string}> bestehende Konten, die neu Vereinsmitglied werden */
    public array $linked = [];

    /** @var list<array{name: string, email: string}> bestehende Mitglieder, denen Gruppe oder Rolle fehlte */
    public array $completed = [];

    /** @var list<array{name: string, email: string}> neu anzulegende Konten */
    public array $created = [];

    /** @var list<array{name: string, email: string}> Mitglieder, die aus Gruppe und Rolle entfernt werden */
    public array $removed = [];

    /** @var list<array{row: int, person: string, message: string}> Klärungsfälle (keine Änderung) */
    public array $review = [];

    /** @var list<array{name: string, email: string, password: string}> Zugangsdaten (nur PDF-Modus) */
    public array $newUsers = [];

    public function __construct(public readonly bool $dryRun) {}

    public function review(int $row, string $person, string $message): void
    {
        $this->review[] = ['row' => $row, 'person' => $person, 'message' => $message];
    }

    /** Anzahl der Mitglieder nach dem Import */
    public function membersAfter(): int
    {
        return $this->unchanged + count($this->completed) + count($this->linked) + count($this->created) + $this->kept;
    }

    public function summary(): array
    {
        return array_filter([
            'Zeilen' => $this->rows,
            'Bestehenden Konten zugeordnet' => $this->matchedRows,
            'Bereits Mitglied, unverändert' => $this->unchanged,
            'Bestehende Konten neu im Verein' => count($this->linked),
            'Gruppe/Rolle ergänzt' => count($this->completed),
            'Konten neu' => count($this->created),
            'Aus dem Verein entfernt' => count($this->removed),
            'Nicht in der Datei, behalten' => $this->kept,
            'Klärungsfälle' => count($this->review),
        ], fn ($value, $label) => $value > 0 || in_array($label, ['Zeilen', 'Konten neu', 'Klärungsfälle'], true), ARRAY_FILTER_USE_BOTH);
    }
}
