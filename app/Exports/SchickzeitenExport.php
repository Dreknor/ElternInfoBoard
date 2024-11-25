<?php

namespace App\Exports;

use App\Settings\SchickzeitenSetting;
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

        $einstellungen = new SchickzeitenSetting();

        $sheets = [];
        $von = Carbon::createFromFormat('H:i', $einstellungen->schicken_ab);
        $bis = Carbon::createFromFormat('H:i', $einstellungen->schicken_bis);

        for ($x = $von->format('H'); $x <= $bis->format('H'); $x++) {
            $spreadsheet = new SchickzeitenStundenExport($x);
            $sheets[] = $spreadsheet;
        }

        return $sheets;
    }
}
