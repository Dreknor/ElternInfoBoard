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

    /** Bisherige Mitglieder, die nicht in der Datei stehen und Mitglied bleiben */
    public int $kept = 0;

    /** Anzahl der Mitglieder nach dem Import */
    public int $membersAfter = 0;

    /** Versandte Benachrichtigungen an bestehende Konten (Aufnahme / Entfernung) */
    public int $notifiedAdded = 0;

    public int $notifiedRemoved = 0;

    /** @var list<array{name: string, email: string, key: string, row: ?int}> bestehende Konten, die neu Vereinsmitglied werden */
    public array $linked = [];

    /** @var list<array{name: string, email: string, key: string, row: ?int}> bestehende Mitglieder, denen Gruppe oder Rolle fehlte */
    public array $completed = [];

    /** @var list<array{name: string, email: string, key: string, row: ?int}> neu anzulegende Konten */
    public array $created = [];

    /** @var list<array{name: string, email: string, key: string, row: ?int}> Mitglieder, die aus Gruppe und Rolle entfernt werden */
    public array $removed = [];

    /**
     * Klärungsfälle mit Entscheidungsmöglichkeiten (nur in der Vorschau).
     *
     * @var list<array{row: int, name: string, rawEmail: string, message: string, options: list<array{value: string, label: string}>}>
     */
    public array $cases = [];

    /** @var list<array{row: int, name: string, action: string}> bei der Bestätigung entschiedene Klärungsfälle */
    public array $resolved = [];

    /** @var list<array{row: int, person: string, message: string}> Hinweise ohne Entscheidungsbedarf */
    public array $notes = [];

    /** @var list<array{row: int, name: string, email: string, reason: string}> Personen aus der Datei ohne Zuordnung */
    public array $unassigned = [];

    /** @var list<array{name: string, email: string, reason: string}> Mitglieder, die über das System kaum erreichbar sind */
    public array $unreachable = [];

    /** @var list<array{name: string, email: string, password: string}> Zugangsdaten (nur PDF-Modus) */
    public array $newUsers = [];

    public function __construct(public readonly bool $dryRun) {}

    public function note(int $row, string $person, string $message): void
    {
        $this->notes[] = ['row' => $row, 'person' => $person, 'message' => $message];
    }

    /**
     * Zeilen für den Export „nicht zugeordnet / nicht erreichbar“.
     *
     * @return list<array{0: string, 1: ?int, 2: string, 3: string, 4: string}>
     */
    public function evaluationRows(): array
    {
        $rows = [];
        foreach ($this->unassigned as $entry) {
            $rows[] = ['Nicht zugeordnet', $entry['row'] ?: null, $entry['name'], $entry['email'], $entry['reason']];
        }
        foreach ($this->unreachable as $entry) {
            $rows[] = ['Nicht erreichbar', null, $entry['name'], $entry['email'], $entry['reason']];
        }

        return $rows;
    }
}
