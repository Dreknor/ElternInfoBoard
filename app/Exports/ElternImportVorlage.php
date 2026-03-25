<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ElternImportVorlage implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    public function title(): string
    {
        return 'Eltern-Import';
    }

    public function headings(): array
    {
        return [
            'Gruppenname',      // Spalte 1 (Standard: gruppen=1)
            'Klassenstufe',     // Spalte 2 (Standard: klassenstufe=2)
            'Lerngruppe',       // Spalte 3 (Standard: lerngruppe=3)
            'S1 Vorname',       // Spalte 4 (Standard: S1Vorname=4)
            'S1 Nachname',      // Spalte 5 (Standard: S1Nachname=5)
            'S1 E-Mail',        // Spalte 6 (Standard: S1Email=6)
            'S2 Vorname',       // Spalte 7 (Standard: S2Vorname=7)
            'S2 Nachname',      // Spalte 8 (Standard: S2Nachname=8)
            'S2 E-Mail',        // Spalte 9 (Standard: S2Email=9)
        ];
    }

    public function array(): array
    {
        return [
            [
                'Elternvertreter;Förderverein', // gruppen (Semikolon-getrennte Gruppennamen)
                '5',                             // klassenstufe  (z. B. "5" → Gruppe "Klassenstufe 5")
                'b5a',                           // lerngruppe (führendes Zeichen wird entfernt → Gruppe "5a")
                'Max',                           // S1 Vorname
                'Mustermann',                    // S1 Nachname
                'max.mustermann@example.com',    // S1 E-Mail
                'Erika',                         // S2 Vorname (optional)
                'Mustermann',                    // S2 Nachname (optional)
                'erika.mustermann@example.com',  // S2 E-Mail (optional)
            ],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2563EB'],
                ],
            ],
            2 => [
                'font' => ['italic' => true, 'color' => ['rgb' => '6B7280']],
            ],
        ];
    }
}

