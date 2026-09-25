<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Liest die Schüler-Importdatei zeilenweise mit Überschriften ein
 * (Verarbeitung: App\Services\Import\SchuelerImportService).
 */
class SchuelerImportRows implements WithHeadingRow
{
}
