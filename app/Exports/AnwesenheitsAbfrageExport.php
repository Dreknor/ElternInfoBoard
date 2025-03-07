<?php

namespace App\Exports;

use App\Model\ChildCheckIn;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AnwesenheitsAbfrageExport implements FromCollection, WithMapping, WithHeadings, ShouldAutoSize
{
    protected $date_start;
    protected $date_end;
    public $days = [];

    public function __construct($date_start, $date_end, $days)
    {
        $this->date_start = Carbon::parse($date_start);
        $this->date_end = Carbon::parse($date_end);
        $this->days = $days;


    }

    public function collection()
    {
        $checkIns = ChildCheckIn::query()
            ->whereBetween('date', [$this->date_start, $this->date_end])
            ->with('child')
            ->get();

        return $checkIns->groupBy('child_id');

    }

    public function map($childCheckIns): array
    {
        $days = $this->days->map(function ($day) use ($childCheckIns) {
            return $childCheckIns->where('date', $day)->where('should_be', 1)->first() ? 'X' : '';
        });

        return [
            $childCheckIns->first()->child->last_name.', '.$childCheckIns->first()->child->first_name,
            ...$days
        ];
    }

    public function headings(): array
    {
            $headings = ['Name'];



            foreach ($this->days as $day) {
                $headings[] = $day->format('d.m.Y');
            }

            return $headings;
    }


}
