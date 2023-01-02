<?php

namespace App\Imports;

use App\Model\Losung;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class LosungenImport implements ToCollection, WithHeadingRow
{
    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        $newLosungenArray = [];

        foreach ($collection as $row) {
            $newLosungenArray[] = [
                'date' => Date::excelToDateTimeObject($row['datum'])->format('Y-m-d'),
                'Losungsvers' => $row['losungsvers'],
                'Losungstext' => Str::remove('/', $row['losungstext']),
                'Lehrtextvers' => $row['lehrtextvers'],
                'Lehrtext' => Str::remove('/', $row['lehrtext'])
            ];
        }
        Losung::insert($newLosungenArray);
    }
}
