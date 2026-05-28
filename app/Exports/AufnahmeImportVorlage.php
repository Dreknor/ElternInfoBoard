<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AufnahmeImportVorlage implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    public function title(): string
    {
        return 'Aufnahme-Import';
    }

    public function headings(): array
    {
        return [
            'Gruppe (GS/OS)',   // Spalte 1 (Standard: gruppen=1) – Wert muss "GS" oder "OS" enthalten
            '(nicht verwendet)', // Spalte 2 – Klassenstufe (im Formular sichtbar, für Aufnahme irrelevant)
            '(nicht verwendet)', // Spalte 3 – Lerngruppe  (im Formular sichtbar, für Aufnahme irrelevant)
            'S1 Vorname',       // Spalte 4 (Standard: S1Vorname=4)
            'S1 Nachname',      // Spalte 5 (Standard: S1Nachname=5)
            'S1 E-Mail',        // Spalte 6 (Standard: S1Email=6)
            'S2 Vorname',       // Spalte 7 (Standard: S2Vorname=7, optional)
            'S2 Nachname',      // Spalte 8 (Standard: S2Nachname=8, optional)
            'S2 E-Mail',        // Spalte 9 (Standard: S2Email=9, optional)
        ];
    }

    public function array(): array
    {
        return [
            [
                'GS',                            // Gruppe → "Aufnahme GS" (oder "OS" → "Aufnahme OS")
                '',                              // nicht verwendet
                '',                              // nicht verwendet
                'Lena',                          // S1 Vorname
                'Beispiel',                      // S1 Nachname
                'lena.beispiel@example.com',     // S1 E-Mail
                'Stefan',                        // S2 Vorname (optional)
                'Beispiel',                      // S2 Nachname (optional)
                'stefan.beispiel@example.com',   // S2 E-Mail (optional)
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
                    'startColor' => ['rgb' => '059669'],
                ],
            ],
            2 => [
                'font' => ['italic' => true, 'color' => ['rgb' => '6B7280']],
            ],
        ];
    }
}

