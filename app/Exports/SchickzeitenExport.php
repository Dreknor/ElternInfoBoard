<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 *
 */
class SchickzeitenExport implements WithMultipleSheets
{
    /**
     * @return array
     */
    public function sheets(): array
    {
        $sheets = [];
        $von = Carbon::createFromFormat('H:i:s', config('schicken.ab'));
        $bis = Carbon::createFromFormat('H:i:s', config('schicken.max'));

        for ($x = $von->format('H'); $x <= $bis->format('H'); $x++) {
            $spreadsheet = new SchickzeitenStundenExport($x);
            $sheets[] = $spreadsheet;
        }

        return $sheets;
    }
}
