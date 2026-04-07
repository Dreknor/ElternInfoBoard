<?php

namespace App\Exports;

use App\Model\Arbeitsgemeinschaft;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\BeforeSheet;

class ParticipantsExport implements FromCollection, WithCustomStartCell, WithEvents, WithHeadings
{
    protected $arbeitsgemeinschaft;

    public function __construct(Arbeitsgemeinschaft $arbeitsgemeinschaft)
    {
        $this->arbeitsgemeinschaft = $arbeitsgemeinschaft;
    }

    public function collection()
    {
        return $this->arbeitsgemeinschaft->participants->map(function ($participant) {
            return [
                'Name' => $participant->last_name,
                'Vorname' => $participant->first_name,
                'Klasse/Gruppe' => $participant->class?->name.'( '.$participant->group?->name.')',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Familienname, Vorname',
            'Vorname',
            'Klasse/Gruppe',
        ];
    }

    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function (BeforeSheet $event) {
                $sheet = $event->sheet;

                // AG-Informationen hinzufügen
                $sheet->setCellValue('A1', 'Arbeitsgemeinschaft: '.$this->arbeitsgemeinschaft->name);
                $sheet->setCellValue('A2', 'Verantwortlich: '.$this->arbeitsgemeinschaft->manager->name);
                $sheet->setCellValue('A3', 'Tag: '.$this->getWeekday());
                $sheet->setCellValue('A4', 'Zeit: '.$this->arbeitsgemeinschaft->start_time->format('H:i').
                    ' - '.$this->arbeitsgemeinschaft->end_time->format('H:i'));
                $sheet->setCellValue('A5', 'Teilnehmer: '.$this->arbeitsgemeinschaft->participants->count().
                    ' / '.$this->arbeitsgemeinschaft->max_participants);

                // Leerzeile vor Teilnehmerliste
                $sheet->setCellValue('A6', '');

                // Formatierung
                $sheet->getStyle('A1:D5')->getFont()->setBold(true);
                $sheet->getStyle('A7:D7')->getFont()->setBold(true);

                // Spaltenbreite automatisch anpassen
                $sheet->getColumnDimension('A')->setAutoSize(true);
                $sheet->getColumnDimension('B')->setAutoSize(true);
                $sheet->getColumnDimension('C')->setAutoSize(true);
                $sheet->getColumnDimension('D')->setAutoSize(true);
            },

        ];
    }

    protected function getWeekday()
    {
        $weekdays = [
            1 => 'Montag',
            2 => 'Dienstag',
            3 => 'Mittwoch',
            4 => 'Donnerstag',
            5 => 'Freitag',
            6 => 'Samstag',
            7 => 'Sonntag',
        ];

        return $weekdays[$this->arbeitsgemeinschaft->weekday] ?? '';
    }

    public function startCell(): string
    {
        return 'A6';
    }
}
