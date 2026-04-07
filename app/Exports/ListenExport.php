<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

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

        if ($this->liste->type == 'eintrag') {
            return collect($this->listentermine)->map(function ($eintrag) {
                return [
                    'Name' => $eintrag->user?->name,
                    'Email' => $eintrag->user?->email,
                    'Eintrag' => $eintrag->eintragung,
                ];
            });
        } else {
            return collect($this->listentermine)->map(function ($eintrag) {
                return [
                    'Datum' => $eintrag->termin->format('d.m.Y'),
                    'Uhrzeit' => $eintrag->termin->format('H:i').' - '.
                        $eintrag->termin->copy()->addMinutes($this->liste->duration)->format('H:i'),
                    'Familie' => $eintrag->eingetragenePerson?->name,
                    'Email' => $eintrag->eingetragenePerson?->email,
                    'Bemerkungen' => $eintrag->comment,
                ];
            });
        }

    }

    public function headings(): array
    {

        if ($this->liste->type == 'eintrag') {
            return [
                'Name',
                'Email',
                'Eintrag',
            ];
        } else {
            return [
                'Datum',
                'Uhrzeit',
                'Familie',
                'Email',
                'Bemerkungen',
            ];
        }
    }
}
