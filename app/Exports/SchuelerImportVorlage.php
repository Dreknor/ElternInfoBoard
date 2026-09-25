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
 * Vorlage für den kind-zentrierten Import: eine Zeile pro Kind (Konzept §8.1).
 */
class SchuelerImportVorlage implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    public function title(): string
    {
        return 'Schueler-Import';
    }

    public function headings(): array
    {
        $headings = ['Schueler-ID', 'Kind Vorname', 'Kind Nachname', 'Klassenstufe', 'Klasse', 'Gruppe', 'Weitere Gruppen'];

        foreach ([1, 2, 3] as $n) {
            array_push($headings, "B{$n} Vorname", "B{$n} Nachname", "B{$n} E-Mail", "B{$n} Beziehung", "B{$n} Sorgerecht");
        }

        return $headings;
    }

    public function array(): array
    {
        return [
            [
                'S-2026-0815',                   // Schüler-ID aus der Schulverwaltung (Pflicht)
                'Max', 'Mustermann',
                '1',                             // Klassenstufe → Gruppe „Klassenstufe 1“
                '1a',                            // Klasse (Gruppenname) → class_id
                'Hort Sonnenschein',             // Betreuungsgruppe (optional) → group_id
                'Elternrat;Förderverein',        // weitere (manuelle) Gruppen der Bezugspersonen
                'Erika', 'Mustermann', 'erika.mustermann@example.com', 'Mutter', 'J',
                'Hans', 'Mustermann', 'hans.mustermann@example.com', 'Vater', 'J',
                'Gisela', 'Muster', 'oma.gisela@example.com', 'Oma', 'N',
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
