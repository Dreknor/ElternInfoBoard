<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Auswertung eines Vereins-Imports: Personen ohne Zuordnung und Mitglieder,
 * die über das System voraussichtlich nicht erreicht werden.
 */
class VereinImportAuswertung implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    /**
     * @param  list<array{0: string, 1: ?int, 2: string, 3: string, 4: string}>  $rows
     */
    public function __construct(private readonly array $rows) {}

    public function title(): string
    {
        return 'Auswertung';
    }

    public function headings(): array
    {
        return ['Kategorie', 'Zeile in der Datei', 'Name', 'E-Mail', 'Grund'];
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'B45309']],
            ],
        ];
    }
}
