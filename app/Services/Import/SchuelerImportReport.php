<?php

namespace App\Services\Import;

/**
 * Ergebnis eines Schüler-Imports (auch im Dry-Run).
 */
final class SchuelerImportReport
{
    public int $rows = 0;

    public int $childrenCreated = 0;

    public int $childrenUpdated = 0;

    /** Bestandskinder, denen erstmals eine Schüler-ID zugeordnet wurde */
    public int $childrenMatched = 0;

    public int $usersCreated = 0;

    public int $linksCreated = 0;

    public int $familiesCreated = 0;

    public int $familiesExtended = 0;

    /** @var list<array{row: int, message: string}> */
    public array $errors = [];

    /** @var list<array{row: int, message: string}> Klärungsfälle (keine Änderung) */
    public array $review = [];

    /** @var list<string> Namen der als Abgänger markierten Kinder */
    public array $leavers = [];

    public function __construct(public readonly bool $dryRun) {}

    public function error(int $row, string $message): void
    {
        $this->errors[] = ['row' => $row, 'message' => $message];
    }

    public function review(int $row, string $message): void
    {
        $this->review[] = ['row' => $row, 'message' => $message];
    }

    public function summary(): array
    {
        return [
            'Zeilen' => $this->rows,
            'Kinder neu' => $this->childrenCreated,
            'Kinder aktualisiert' => $this->childrenUpdated,
            'Bestandskinder zugeordnet' => $this->childrenMatched,
            'Konten neu' => $this->usersCreated,
            'Beziehungen neu' => $this->linksCreated,
            'Familien neu' => $this->familiesCreated,
            'Familien ergänzt' => $this->familiesExtended,
            'Abgänger' => count($this->leavers),
            'Fehler' => count($this->errors),
            'Klärungsfälle' => count($this->review),
        ];
    }

    public function toArray(): array
    {
        return [
            'dryRun' => $this->dryRun,
            'summary' => $this->summary(),
            'errors' => $this->errors,
            'review' => $this->review,
            'leavers' => $this->leavers,
        ];
    }
}
