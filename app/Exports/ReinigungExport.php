<?php

namespace App\Exports;

use App\Model\Reinigung;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Exportiert den Reinigungsplan als druckbare Abhakliste: je Einsatz eine Zeile mit
 * Kästchen zum Abhaken ("Erledigt") sowie den Bemerkungen, die - je Zeile bzw. durch
 * Semikolon getrennt - ebenfalls als einzelne abhakbare Punkte ausgegeben werden.
 */
class ReinigungExport implements FromCollection, WithColumnWidths, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public const CHECKBOX = '☐';

    private $bereich;

    public function __construct(string $bereich)
    {
        $this->bereich = $bereich;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $datum = Carbon::now()->startOfWeek()->startOfDay();

        $query = Reinigung::with('user')->whereDate('datum', '>=', $datum)->orderBy('datum')->orderBy('aufgabe');

        // Im gemeinsamen Modus (Reinigung::BEREICH_GESAMT) werden alle Datensätze
        // unabhängig vom ursprünglich gespeicherten Bereich exportiert.
        if ($this->bereich !== Reinigung::BEREICH_GESAMT) {
            $query->where('bereich', $this->bereich);
        }

        return $query->get();
    }

    public function map($reinigung): array
    {
        return [
            self::CHECKBOX,
            $reinigung->datum->copy()->startOfWeek()->format('d.m.').' - '.$reinigung->datum->copy()->endOfWeek()->format('d.m.Y'),
            $reinigung->user ? 'Familie '.$reinigung->user->name : '',
            $reinigung->aufgabe,
            self::checklist($reinigung),
        ];
    }

    /**
     * Bemerkungen als Abhakliste (ein Kästchen je Punkt, siehe Reinigung::bemerkungPunkte()).
     */
    public static function checklist(Reinigung $reinigung): string
    {
        return collect($reinigung->bemerkungPunkte())
            ->map(fn ($punkt) => self::CHECKBOX.' '.$punkt)
            ->implode("\n");
    }

    /**
     * @return string[]
     */
    public function headings(): array
    {
        return [
            'Erledigt',
            'Woche',
            'Familie',
            'Aufgabe',
            'Bemerkungen',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,
            'B' => 22,
            'C' => 30,
            'D' => 30,
            'E' => 50,
        ];
    }

    public function title(): string
    {
        return 'Reinigungsplan';
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = max($sheet->getHighestRow(), 1);
        $range = 'A1:E'.$lastRow;

        $sheet->getStyle($range)->getAlignment()
            ->setVertical(Alignment::VERTICAL_TOP)
            ->setWrapText(true);
        $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('A1:A'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A2:A'.$lastRow)->getFont()->setSize(16);

        // Kopfzeile auf jeder gedruckten Seite wiederholen, Querformat, auf Seitenbreite skalieren
        $sheet->freezePane('A2');
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0)
            ->setRowsToRepeatAtTopByStartAndEnd(1, 1);

        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE0E0E0']],
            ],
        ];
    }
}
