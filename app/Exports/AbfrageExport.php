<?php

namespace App\Exports;

use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;


/**
 *
 */
class AbfrageExport implements withHeadings, withMapping, FromCollection, ShouldAutoSize
{

    public $rueckmeldungen;
    public $options;

    /**
     * @param $rueckmeldung
     */
    public function __construct($options, $rueckmeldungen)
    {
        $this->rueckmeldungen = $rueckmeldungen;
        $this->options = $options;
    }


    public function headings(): array
    {
        $options = ['Benutzer', 'Zeitpunkt'];

        foreach ($this->options as $option) {
            $options[] = "$option->option";
        };

        return $options;
    }


    public function map($userrueckmeldung): array
    {
        $answers = $userrueckmeldung->answers;
        $row = [];
        $row[] = $userrueckmeldung->user->name;
        $row[] = $userrueckmeldung->created_at;
        foreach ($this->options as $option) {
            $answer = $answers->where('option_id', $option->id)->first()?->answer;
            if ($answer == null) {
                $answer = '';
            }
            $answer = Str::replace('<p>', '', $answer);
            $answer = Str::replace('</p>', '', $answer);
            $answer = Str::replace('<br>', '', $answer);
            $answer = Str::replace('<br/>', '', $answer);
            $answer = Str::replace('<br />', '', $answer);

            $row[] = $answer;
        };

        return $row;

    }

    public function collection()
    {
        //dump($this->rueckmeldungen);
        return $this->rueckmeldungen;
    }



}
