<?php

namespace App\Exports;

use App\Model\Schickzeiten;
use Illuminate\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

/**
 *
 */
class SchickzeitenStundenExport implements FromView, WithTitle, WithEvents
{
    use RegistersEventListeners;

    /**
     * @var int
     */
    private $stunde;

    /**
     * @param int $stunde
     */
    public function __construct(int $stunde)
    {
        $this->stunde = $stunde;
    }

    /**
     * @return View
     */
    public function view(): View
    {
        $stunde = $this->stunde + 1 .':00:00';

        return view('export.schickzeiten', [
            'schickzeiten' => Schickzeiten::query()
                ->where(function ($query) use ($stunde) {
                    $query->where('time', '<', $stunde)
                        ->orWhere('time_ab', '<', $stunde)
                        ->orWhere('time_spaet', '<', $stunde);
                })
                ->orderBy('time')
                ->orderBy('type')
                ->with('child')->get(),
            'stunde' => $this->stunde,
        ]);
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'ab '.$this->stunde.' Uhr';
    }

    /**
     * @param AfterSheet $event
     * @return void
     */
    public static function afterSheet(AfterSheet $event)
    {
        $styleArrayHeading = [
            'borders' => [
                'outline' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => [
                        'rgb' => '#000000',
                    ],
                ],
            ],
        ];

        $event->sheet->getDelegate()->getStyle('A1:F1')->applyFromArray($styleArrayHeading);
        $event->sheet->getDelegate()->getStyle('A2:F2')->applyFromArray($styleArrayHeading);
        $event->sheet->getDelegate()->getStyle('A3:F3')->applyFromArray($styleArrayHeading);
        $event->sheet->getDelegate()->getStyle('A1:F35')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
        $event->sheet->getDelegate()->getStyle('A1:F35')->getFont()->setSize(9);
        $event->sheet->getDelegate()->getStyle('A1:F35')->getFont()->setName('Arial');
        $event->sheet->getDelegate()->getColumnDimension('A')->setWidth(18);
        $event->sheet->getDelegate()->getColumnDimension('B')->setWidth(20);
        $event->sheet->getDelegate()->getColumnDimension('C')->setWidth(20);
        $event->sheet->getDelegate()->getColumnDimension('D')->setWidth(20);
        $event->sheet->getDelegate()->getColumnDimension('E')->setWidth(20);
        $event->sheet->getDelegate()->getColumnDimension('F')->setWidth(20);
        $event->sheet->getDelegate()->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
    }
}
