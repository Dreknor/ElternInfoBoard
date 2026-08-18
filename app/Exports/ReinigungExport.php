<?php

namespace App\Exports;

use App\Model\Reinigung;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ReinigungExport implements FromCollection, WithHeadings, WithMapping
{
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

        $query = Reinigung::whereDate('datum', '>=', $datum)->orderBy('datum');

        // Im gemeinsamen Modus (Reinigung::BEREICH_GESAMT) werden alle Datensätze
        // unabhängig vom ursprünglich gespeicherten Bereich exportiert.
        if ($this->bereich !== Reinigung::BEREICH_GESAMT) {
            $query->where('Bereich', $this->bereich);
        }

        return $query->get();
    }

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
