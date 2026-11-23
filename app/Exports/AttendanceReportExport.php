<?php

namespace App\Exports;

use App\Model\Child;
use App\Services\Attendance\AttendanceCalculator;
use App\Services\Attendance\AttendanceDay;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Betreuungsumfang als Excel: Blatt „Übersicht“ (je Kind) und Blatt „Tage“ (je Kind und Tag).
 */
class AttendanceReportExport implements WithMultipleSheets
{
    /**
     * @param  Collection<int, Child>  $children
     * @param  Collection<int, Collection<int, AttendanceDay>>  $days
     */
    public function __construct(private Collection $children, private Collection $days) {}

    public function sheets(): array
    {
        return [
            new AttendanceReportSheet('Übersicht', $this->summaryHeadings(), $this->summaryRows()),
            new AttendanceReportSheet('Tage', $this->dayHeadings(), $this->dayRows()),
        ];
    }

    private static function hours(int $minutes): float
    {
        return round($minutes / 60, 2);
    }

    private function summaryHeadings(): array
    {
        return [
            'Nachname', 'Vorname', 'Gruppe', 'Klasse', 'Tage anwesend',
            'Gesamt (h:mm)', 'Gesamt (h)', 'Schulzeit (h)', 'Ferien (h)',
            'unvollständige Tage', 'automatische Anmeldungen', 'bearbeitete Tage',
            'krank gemeldet', 'laut Abfrage abgemeldet', 'keine Angabe',
        ];
    }

    private function summaryRows(): array
    {
        return $this->children->map(function (Child $child) {
            $s = AttendanceCalculator::summarize($this->days->get($child->id, collect()));

            return [
                $child->last_name,
                $child->first_name,
                $child->group?->name,
                $child->class?->name,
                $s['days_present'],
                AttendanceDay::formatMinutes($s['minutes_total']),
                self::hours($s['minutes_total']),
                self::hours($s['minutes_school']),
                self::hours($s['minutes_holiday']),
                $s['incomplete_days'],
                $s['automatic_days'],
                $s['edited_days'],
                $s['sick_days'],
                $s['not_expected_days'],
                $s['no_data_days'],
            ];
        })->values()->all();
    }

    private function dayHeadings(): array
    {
        return [
            'Nachname', 'Vorname', 'Datum', 'Wochentag', 'Ferien', 'Status',
            'Zeiten', 'Umfang (h:mm)', 'Umfang (h)', 'unvollständig', 'automatisch', 'bearbeitet',
        ];
    }

    private function dayRows(): array
    {
        $rows = [];
        foreach ($this->children as $child) {
            foreach ($this->days->get($child->id, collect()) as $day) {
                $rows[] = [
                    $child->last_name,
                    $child->first_name,
                    $day->date->format('d.m.Y'),
                    $day->date->locale('de')->isoFormat('dd'),
                    $day->holiday ? 'ja' : '',
                    $day->statusLabel(),
                    collect($day->intervals)->map(fn ($i) => $i['from']->format('H:i').'–'.$i['to']->format('H:i').($i['capped'] ? '*' : ''))->implode(', '),
                    $day->formattedDuration(),
                    self::hours($day->minutes),
                    $day->incomplete ? 'ja' : '',
                    $day->automatic ? 'ja' : '',
                    $day->edited ? 'ja' : '',
                ];
            }
        }

        return $rows;
    }
}
