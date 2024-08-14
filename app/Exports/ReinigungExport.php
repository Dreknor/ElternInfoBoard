<?php

namespace App\Exports;

use App\Model\Reinigung;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 *
 */
class ReinigungExport implements FromCollection, WithMapping, WithHeadings
{
    /**
     * @var
     */
    private $bereich;


    /**
     * @param String $bereich
     */
    public function __construct(String $bereich)
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

    /**
     * @param $reinigung
     * @return array
     */
    public function map($reinigung): array
    {
        return [
            $reinigung->datum->format('d.m.').' - '.$reinigung->datum->endOfWeek()->format('d.m.Y'),
            'Familie '.$reinigung->user->name,
            $reinigung->aufgabe,
            $reinigung->kommentar,
        ];
    }

    /**
     * @return string[]
     */
    public function headings(): array
    {
        return [
            'Datum',
            'Familie',
            'Aufgabe',
            'Anmerkung',
        ];
    }
}
