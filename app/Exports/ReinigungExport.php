<?php

namespace App\Exports;

use App\Model\Reinigung;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 *
 */
class ReinigungExport implements FromCollection, WithMapping, WithHeadings
{

    private $bereich;



    public function __construct($bereich)
    {
        $this->bereich = $bereich;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $datum = Carbon::now()->startOfWeek()->startOfDay();

        return Reinigung::where('Bereich', $this->bereich)->whereDate('datum', '>=', $datum)
            ->orderBy('datum')
            ->get();
    }

    public function map($reinigung): array
    {
        return [
            $reinigung->datum->format('d.m.').' - '. $reinigung->datum->endOfWeek()->format('d.m.Y') ,
            'Familie '.$reinigung->user->familie_name,
            $reinigung->aufgabe,
            $reinigung->kommentar,
        ];
    }

    public function headings(): array
    {
        return [
          'Datum',
          'Familie',
          'Aufgabe',
          'Anmerkung'
        ];
    }
}
