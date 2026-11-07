<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Liest die Vereins-Importdatei zeilenweise mit Überschriften ein
 * (Verarbeitung: App\Services\Import\VereinImportService).
 */
class VereinImportRows implements WithHeadingRow
{
}
