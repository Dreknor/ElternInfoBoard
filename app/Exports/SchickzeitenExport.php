<?php

namespace App\Exports;

use App\Model\Schickzeiten;
use Illuminate\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\AfterSheet;

class SchickzeitenExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        $sheets = [];
        for ($x = 14; $x < 17; $x++) {
            $spreadsheet = new SchickzeitenStundenExport($x);
            $sheets[] = $spreadsheet;
        }

        return $sheets;
    }
}
