<?php

namespace App\Exports;

use App\Model\Schickzeiten;
use Carbon\Carbon;
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
        $von = Carbon::createFromFormat('h:i:s', config('schicken.ab'))->hour;
        $bis = Carbon::createFromFormat('h:i:s', config('schicken.max'))->hour;

        for ($x = $von; $x < $bis+1; $x++) {
            $spreadsheet = new SchickzeitenStundenExport($x);
            $sheets[] = $spreadsheet;
        }

        return $sheets;
    }
}
