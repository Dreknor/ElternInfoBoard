<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Carbon\Carbon;

class ListenExport implements FromCollection, WithHeadings
{
    protected $listentermine;
    protected $liste;

    public function __construct($listentermine, $liste)
    {
        $this->listentermine = $listentermine;
        $this->liste = $liste;
    }

    public function collection()
    {
        return collect($this->listentermine)->map(function ($eintrag) {
            return [
                'Datum' => $eintrag->termin->format('d.m.Y'),
                'Uhrzeit' => $eintrag->termin->format('H:i') . ' - ' .
                    $eintrag->termin->copy()->addMinutes($this->liste->duration)->format('H:i'),
                'Familie' => $eintrag->eingetragenePerson?->name,
                'Email' => $eintrag->eingetragenePerson?->email,
                'Bemerkungen' => $eintrag->comment
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Datum',
            'Uhrzeit',
            'Familie',
            'Email',
            'Bemerkungen'
        ];
    }
}
