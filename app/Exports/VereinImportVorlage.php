<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VereinImportVorlage implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    public function title(): string
    {
        return 'Verein-Import';
    }

    public function headings(): array
    {
        return [
            'person_vorname_nachname',  // Vollständiger Name (Vorname Nachname) – wird als "name" gespeichert
            'person_e_mail_privat',     // E-Mail-Adresse; mehrere Adressen mit Semikolon trennen
        ];
    }

    public function array(): array
    {
        return [
            [
                'Maria Vereinsmitglied',
                'maria.vereinsmitglied@example.com',
            ],
            [
                'Thomas Mitglied',
                'thomas.mitglied@example.com;t.mitglied@work.example.com',
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
                    'startColor' => ['rgb' => 'B45309'],
                ],
            ],
            2 => [
                'font' => ['italic' => true, 'color' => ['rgb' => '6B7280']],
            ],
            3 => [
                'font' => ['italic' => true, 'color' => ['rgb' => '6B7280']],
            ],
        ];
    }
}

